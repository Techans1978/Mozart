<?php
require_once __DIR__ . '/JsonUtil.php';
require_once __DIR__ . '/ConnectorRegistry.php';

class DsEngine {

  public static function run(array $config, array $params): array {
    $type = $config['type'] ?? 'connector';

    if ($type === 'connector') {
      return self::runConnector($config, $params);
    }
    if ($type === 'script') {
      return self::runScript($config, $params);
    }

    throw new Exception("Dataset type inválido: ".$type);
  }

  private static function runConnector(array $config, array $params): array {
    $connector = $config['connector'] ?? null;
    if (!$connector) throw new Exception("Config sem connector");

    $request = self::applyTemplate($config['request'] ?? [], $params);
    $mapping = $config['mapping'] ?? [];

    $conn = ConnectorRegistry::create($connector);
    $raw  = $conn->execute($request);

    // mapping -> rows
    $rows = self::mapToRows($raw, $mapping);

    // transform opcional (script de pós-processamento)
    if (!empty($config['transform']['enabled'])) {
      $rows = self::applyTransform($rows, $params, $config['transform']);
    }

    return [
      'rows' => $rows,
      'meta' => [
        'connector_type' => $connector['type'] ?? null
      ]
    ];
  }

  private static function runScript(array $config, array $params): array {
    $code = $config['code'] ?? '';
    if (!$code) throw new Exception("Script vazio");

    // v1: script PHP inline (cuidado com segurança; por enquanto admin only)
    // O script deve setar $rows e $meta
    $rows = [];
    $meta = [];

    // helpers básicos
    $helpers = [
      'now' => fn()=>date('Y-m-d H:i:s'),
      'params' => $params
    ];

    try {
      // isolando o escopo
      (function() use (&$rows, &$meta, $params, $helpers, $code){
        eval("?>".$code);
      })();
    } catch (Throwable $e) {
      throw new Exception("Erro no script: ".$e->getMessage());
    }

    if (!is_array($rows)) throw new Exception("Script deve retornar \$rows como array");

    return [ 'rows'=>$rows, 'meta'=>$meta ];
  }

  private static function applyTemplate($obj, array $params) {
    // substitui {{param}} em strings simples
    if (is_string($obj)) {
      return preg_replace_callback('/\{\{([a-zA-Z0-9_.-]+)\}\}/', function($m) use ($params){
        $k = $m[1];
        return (string)self::getPath($params, $k, '');
      }, $obj);
    }
    if (is_array($obj)) {
      $out = [];
      foreach ($obj as $k=>$v) $out[$k] = self::applyTemplate($v, $params);
      return $out;
    }
    return $obj;
  }

  private static function getPath(array $arr, string $path, $default=null){
    $cur = $arr;
    foreach (explode('.', $path) as $p) {
      if (!is_array($cur) || !array_key_exists($p, $cur)) return $default;
      $cur = $cur[$p];
    }
    return $cur;
  }

  private static function mapToRows(array $raw, array $mapping): array {
    // mapping:
    // - mode: 'jsonpath' (v1 simples) ou 'mysql'
    // - rows_from: 'json.data.items' etc
    // - fields: { "col":"json.path" }
    $mode = $mapping['mode'] ?? '';

    // MySQL connector já retorna rows
    if (isset($raw['rows']) && is_array($raw['rows'])) {
      return $raw['rows'];
    }

    // HTTP JSON -> extrair rows_from
    $json = $raw['json'] ?? null;
    if (!$json) return [];

    $rowsFrom = $mapping['rows_from'] ?? '';
    $rows = $rowsFrom ? self::getPath($json, $rowsFrom, []) : ($json['rows'] ?? []);

    if (!is_array($rows)) return [];

    $fields = $mapping['fields'] ?? null;
    if (!$fields || !is_array($fields)) {
      // sem fields: retorna rows brutas
      return array_values($rows);
    }

    $out = [];
    foreach ($rows as $r) {
      $row = [];
      foreach ($fields as $col=>$path) {
        $row[$col] = self::getPath($r, $path, null);
      }
      $out[] = $row;
    }
    return $out;
  }

  private static function applyTransform(array $rows, array $params, array $transformCfg): array {
    $code = $transformCfg['code'] ?? '';
    if (!$code) return $rows;

    $meta = [];
    try {
      (function() use (&$rows, &$meta, $params, $code){
        eval("?>".$code);
      })();
    } catch (Throwable $e) {
      throw new Exception("Transform erro: ".$e->getMessage());
    }

    if (!is_array($rows)) throw new Exception("Transform deve manter \$rows como array");
    return $rows;
  }
}

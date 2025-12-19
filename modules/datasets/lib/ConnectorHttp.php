<?php
class ConnectorHttp {
  private array $cfg;
  public function __construct(array $cfg){ $this->cfg=$cfg; }

  public function execute(array $req): array {
    $method = strtoupper($req['method'] ?? 'GET');
    $url    = $req['url'] ?? '';
    $headers= $req['headers'] ?? [];
    $body   = $req['body'] ?? null;
    $timeout= (int)($req['timeout_sec'] ?? 20);

    if (!$url) throw new Exception("HTTP url ausente");

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

    $h = [];
    foreach ($headers as $k=>$v) $h[] = $k.": ".$v;
    if ($h) curl_setopt($ch, CURLOPT_HTTPHEADER, $h);

    if ($body !== null) {
      $payload = is_string($body) ? $body : json_encode($body);
      curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    }

    $resp = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);

    if ($resp === false) throw new Exception("HTTP erro: ".$err);

    $json = json_decode($resp, true);
    $isJson = (json_last_error()===JSON_ERROR_NONE);

    return [
      'status' => $code,
      'raw'    => $resp,
      'json'   => $isJson ? $json : null
    ];
  }
}

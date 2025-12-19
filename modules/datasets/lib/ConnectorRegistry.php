<?php
require_once __DIR__ . '/ConnectorHttp.php';
require_once __DIR__ . '/ConnectorMySql.php';

class ConnectorRegistry {

  public static function create(array $connector): object {
    $type = strtolower($connector['type'] ?? '');
    if ($type === 'http')  return new ConnectorHttp($connector);
    if ($type === 'mysql') return new ConnectorMySql($connector);

    throw new Exception("Connector type não suportado: {$type}");
  }
}

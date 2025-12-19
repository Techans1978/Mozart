<?php
class ConnectorMySql {
  private array $cfg;
  public function __construct(array $cfg){ $this->cfg=$cfg; }

  public function execute(array $req): array {
    $host = $this->cfg['host'] ?? '127.0.0.1';
    $user = $this->cfg['user'] ?? '';
    $pass = $this->cfg['pass'] ?? '';
    $db   = $this->cfg['db'] ?? '';
    $port = (int)($this->cfg['port'] ?? 3306);

    $sql  = $req['sql'] ?? '';
    $bind = $req['bind'] ?? [];

    if (!$sql) throw new Exception("SQL ausente");
    if (!$user || !$db) throw new Exception("MySQL connector incompleto");

    $mysqli = @new mysqli($host, $user, $pass, $db, $port);
    if ($mysqli->connect_error) throw new Exception("MySQL connect: ".$mysqli->connect_error);
    $mysqli->set_charset("utf8mb4");

    // v1: binds simples por substituição segura com prepared? (melhor)
    // Aqui faremos prepared com tipos inferidos (i/d/s)
    $stmt = $mysqli->prepare($sql);
    if (!$stmt) throw new Exception("Prepare: ".$mysqli->error);

    if ($bind) {
      $types = '';
      $vals = [];
      foreach ($bind as $v) {
        if (is_int($v)) $types .= 'i';
        else if (is_float($v)) $types .= 'd';
        else $types .= 's';
        $vals[] = $v;
      }
      $stmt->bind_param($types, ...$vals);
    }

    if (!$stmt->execute()) throw new Exception("Execute: ".$stmt->error);

    $res = $stmt->get_result();
    $rows = [];
    if ($res) while($r=$res->fetch_assoc()) $rows[]=$r;

    $stmt->close();
    $mysqli->close();

    return [ 'rows' => $rows ];
  }
}

<?php
require_once __DIR__ . '/../../../config.php';
require_once ROOT_PATH . '/system/config/autenticacao.php';
require_once ROOT_PATH . '/system/config/connect.php';
if (session_status()===PHP_SESSION_NONE) session_start();
proteger_pagina();

$flowId = (int)($_GET['flow_id'] ?? 0);
$verId  = (int)($_GET['ver_id'] ?? 0);
if($flowId<=0 || $verId<=0) die('parametros invalidos');

$stmt = $conn->prepare("UPDATE bpmsai_flow SET active_version_id=? WHERE id=? LIMIT 1");
$stmt->bind_param("ii", $verId, $flowId);
$stmt->execute();
$stmt->close();

header('Location: /modules/bpm/bpmsai-versoes.php?flow_id='.$flowId);
exit;

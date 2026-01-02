<?php
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 3).'/config.php';
require_once ROOT_PATH.'/system/config/autenticacao.php';
require_once ROOT_PATH.'/system/config/connect.php';
proteger_pagina();

function out($arr,$c=200){ http_response_code($c); echo json_encode($arr, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES); exit; }

$processId = (int)($_POST['process_id'] ?? ($_GET['process_id'] ?? 0));
if ($processId <= 0) out(['error'=>'process_id required'], 400);

$conn->begin_transaction();

try {
  // pega versão atual
  $stmt = $conn->prepare("SELECT current_version, current_version_id FROM bpm_process WHERE id = ? LIMIT 1");
  $stmt->bind_param("i", $processId);
  $stmt->execute();
  $rs = $stmt->get_result();
  $p = $rs->fetch_assoc();
  $stmt->close();
  if (!$p) out(['error'=>'process not found'], 404);

  $curV = (int)$p['current_version'];
  $newV = max(1, $curV + 1);

  // carrega conteúdo da versão atual pelo ponteiro
  $stmt = $conn->prepare("SELECT bpmn_xml, snapshot_json FROM bpm_process_version WHERE id = ? LIMIT 1");
  $stmt->bind_param("i", $p['current_version_id']);
  $stmt->execute();
  $rs = $stmt->get_result();
  $vcur = $rs->fetch_assoc();
  $stmt->close();
  if (!$vcur) out(['error'=>'current version not found'], 404);

  $xml = $vcur['bpmn_xml'];
  $snap = $vcur['snapshot_json'];

  // cria nova versão como draft
  $stmt = $conn->prepare("INSERT INTO bpm_process_version
    (process_id, version, status, bpmn_xml, snapshot_json, created_at, updated_at)
    VALUES (?, ?, 'draft', ?, ?, NOW(), NOW())");
  $stmt->bind_param("iiss", $processId, $newV, $xml, $snap);
  $stmt->execute();
  $newVersionId = (int)$stmt->insert_id;
  $stmt->close();

  // atualiza ponteiros do processo
  $stmt = $conn->prepare("UPDATE bpm_process
                          SET current_version = ?, current_version_id = ?, status='draft', updated_at=NOW()
                          WHERE id = ?");
  $stmt->bind_param("iii", $newV, $newVersionId, $processId);
  $stmt->execute();
  $stmt->close();

  $conn->commit();
  out(['ok'=>true,'process_id'=>$processId,'version'=>$newV,'version_id'=>$newVersionId,'status'=>'draft']);
} catch (Throwable $e) {
  $conn->rollback();
  out(['error'=>'db_error','message'=>$e->getMessage()], 500);
}

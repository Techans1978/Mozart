<?php
// modules/forms/lib/audit.php
function forms_audit(mysqli $conn, array $e): void {
  $form_id = isset($e['form_id']) ? (int)$e['form_id'] : null;
  $form_code = isset($e['form_code']) ? (string)$e['form_code'] : null;
  $action = (string)($e['action'] ?? 'unknown');
  $details = $e['details'] ?? null;
  $user_id = isset($e['user_id']) ? (int)$e['user_id'] : null;

  $detailsJson = is_array($details) ? json_encode($details, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : null;
  if ($detailsJson === false) $detailsJson = null;

  $stmt = $conn->prepare("INSERT INTO forms_audit_log (form_id, form_code, action, details_json, user_id)
                          VALUES (?,?,?,?,?)");
  $stmt->bind_param(
    "isssi",
    $form_id,
    $form_code,
    $action,
    $detailsJson,
    $user_id
  );
  $stmt->execute();
  $stmt->close();
}

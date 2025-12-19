<?php
// modules/dmn/includes/dmn_helpers.php

function dmn_json($arr, int $code=200): void {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($arr, JSON_UNESCAPED_UNICODE);
  exit;
}

function dmn_body_json(): array {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

function dmn_slug(string $s): string {
  $s = trim(mb_strtolower($s, 'UTF-8'));
  $s = preg_replace('/[^\p{L}\p{N}]+/u', '-', $s);
  $s = trim($s, '-');
  return $s ?: ('cat-' . date('YmdHis'));
}

function dmn_checksum(string $xml): string {
  return hash('sha256', $xml);
}

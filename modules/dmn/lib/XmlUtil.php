<?php
// modules/dmn/lib/XmlUtil.php

class XmlUtil {

  /** Cria um DMN vazio (DRD sem shapes) */
  public static function emptyDmn(string $name='Mozart-DMN', string $namespace='http://mozart.local/dmn'): string {
    $id = 'Definitions_' . time();
    $safeName = htmlspecialchars($name, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $safeNs = htmlspecialchars($namespace, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

    return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<definitions xmlns="https://www.omg.org/spec/DMN/20191111/MODEL/"
             xmlns:dmndi="https://www.omg.org/spec/DMN/20191111/DMNDI/"
             xmlns:di="http://www.omg.org/spec/DMN/20191111/DI/"
             xmlns:dc="http://www.omg.org/spec/DMN/20180521/DC/"
             id="{$id}"
             name="{$safeName}"
             namespace="{$safeNs}">
  <dmndi:DMNDI>
    <dmndi:DMNDiagram id="DRD_1"/>
  </dmndi:DMNDI>
</definitions>
XML;
  }

  /** Checksum SHA-256 (igual usamos no banco) */
  public static function checksum(string $xml): string {
    return hash('sha256', $xml);
  }

  /** Validação mínima: checa se parece DMN */
  public static function looksLikeDmn(string $xml): bool {
    $x = trim($xml);
    if ($x === '') return false;
    // bem permissivo pro v1
    return (stripos($x, '<definitions') !== false) && (stripos($x, 'dmndi:DMNDiagram') !== false || stripos($x, 'DMNDiagram') !== false);
  }

  /** Normaliza filename seguro */
  public static function safeFilename(string $name, string $ext='dmn'): string {
    $name = trim($name);
    if ($name === '') $name = 'diagram';
    $name = mb_strtolower($name, 'UTF-8');
    $name = preg_replace('/[^\p{L}\p{N}\-_\.]+/u', '-', $name);
    $name = trim($name, '-');
    $name = preg_replace('/-+/', '-', $name);
    $name = $name ?: 'diagram';
    $name = preg_replace('/\.(dmn|xml|html)$/i', '', $name);
    return $name . '.' . $ext;
  }

  /** Tenta extrair o name="" do <definitions ...> (se existir) */
  public static function extractDefinitionsName(string $xml): ?string {
    if (preg_match('/<definitions[^>]*\sname="([^"]+)"/i', $xml, $m)) {
      return html_entity_decode($m[1], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    return null;
  }

  /** Resposta de download de XML */
  public static function outputXmlDownload(string $xml, string $filename='diagram.dmn'): void {
    header('Content-Type: application/xml; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.$filename.'"');
    header('X-Content-Type-Options: nosniff');
    echo $xml;
    exit;
  }
}

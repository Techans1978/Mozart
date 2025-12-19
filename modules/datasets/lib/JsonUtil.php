<?php
class JsonUtil {
  public static function decode(string $json, $fallback=null){
    $x = json_decode($json, true);
    return (json_last_error()===JSON_ERROR_NONE) ? $x : $fallback;
  }
  public static function encode($data): string {
    return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  }
  public static function checksum($dataOrJson): string {
    $json = is_string($dataOrJson) ? $dataOrJson : self::encode($dataOrJson);
    return hash('sha256', $json);
  }
}

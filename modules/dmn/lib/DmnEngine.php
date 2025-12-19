<?php
// modules/dmn/lib/DmnEngine.php
// DMN Engine v1 (PHP) - suporta Decision Table (hitPolicy FIRST)
// Suporte básico de expressões: =, <, <=, >, >=, in(...), matches(...), true/false, string/number

class DmnEngine {

  public function evaluateDecisionTable(string $dmnXml, array $context, ?string $decisionId = null): array {
    $doc = new DOMDocument();
    $doc->preserveWhiteSpace = false;
    $doc->loadXML($dmnXml);

    $xp = new DOMXPath($doc);
    $xp->registerNamespace('dmn', 'https://www.omg.org/spec/DMN/20191111/MODEL/');

    // Seleciona a Decision
    if ($decisionId) {
      $decision = $xp->query("//dmn:decision[@id='{$decisionId}']")->item(0);
      if (!$decision) throw new Exception("Decision id '{$decisionId}' não encontrada.");
    } else {
      $decision = $xp->query("//dmn:decision")->item(0);
      if (!$decision) throw new Exception("Nenhuma <decision> encontrada.");
    }

    $table = $xp->query(".//dmn:decisionTable", $decision)->item(0);
    if (!$table) throw new Exception("Decision não possui decisionTable.");

    $hitPolicy = strtoupper(trim($table->getAttribute('hitPolicy') ?: 'FIRST'));
    if ($hitPolicy !== 'FIRST') {
      // v1: FIRST apenas (mais previsível)
      throw new Exception("hitPolicy '{$hitPolicy}' ainda não suportada no engine v1. Use FIRST.");
    }

    // Inputs: pega o text da inputExpression
    $inputs = [];
    foreach ($xp->query("./dmn:input", $table) as $in) {
      $expr = $xp->query("./dmn:inputExpression/dmn:text", $in)->item(0);
      $label = $in->getAttribute('label') ?: '';
      $inputs[] = [
        'label' => $label,
        'expr'  => $expr ? trim($expr->textContent) : '',
      ];
    }

    // Outputs
    $outputs = [];
    foreach ($xp->query("./dmn:output", $table) as $out) {
      $name = $out->getAttribute('name') ?: 'result';
      $typeRef = $out->getAttribute('typeRef') ?: 'string';
      $outputs[] = ['name'=>$name, 'typeRef'=>$typeRef];
    }

    // Regras
    $rules = $xp->query("./dmn:rule", $table);
    foreach ($rules as $rule) {

      $ok = true;

      // cada inputEntry casa com inputs[i]
      $entries = $xp->query("./dmn:inputEntry/dmn:text", $rule);
      for ($i=0; $i<count($inputs); $i++) {
        $cond = ($entries->item($i)) ? trim($entries->item($i)->textContent) : '';
        $path = $inputs[$i]['expr'];

        $value = $this->getByPath($context, $path);

        if (!$this->matchCondition($cond, $value, $context)) {
          $ok = false;
          break;
        }
      }

      if (!$ok) continue;

      // Regra casou -> monta saída
      $outEntries = $xp->query("./dmn:outputEntry/dmn:text", $rule);

      $result = [];
      for ($j=0; $j<count($outputs); $j++) {
        $raw = ($outEntries->item($j)) ? trim($outEntries->item($j)->textContent) : '';
        $result[$outputs[$j]['name']] = $this->coerceValue($raw, $outputs[$j]['typeRef'], $context);
      }

      return [
        'matched' => true,
        'result'  => $result
      ];
    }

    return [
      'matched' => false,
      'result'  => null
    ];
  }

  private function getByPath(array $ctx, string $path) {
    $path = trim($path);
    if ($path === '') return null;
    $parts = explode('.', $path);
    $cur = $ctx;
    foreach ($parts as $p) {
      $p = trim($p);
      if ($p === '') continue;
      if (is_array($cur) && array_key_exists($p, $cur)) $cur = $cur[$p];
      else return null;
    }
    return $cur;
  }

  private function matchCondition(string $cond, $value, array $ctx): bool {
    $cond = trim($cond);

    // vazio ou "-" => sempre true
    if ($cond === '' || $cond === '-') return true;

    // true/false literal
    if ($cond === 'true' || $cond === 'false') {
      return (bool)$value === ($cond === 'true');
    }

    // in (a,b,c)
    if (preg_match('/^in\s*\((.*)\)$/i', $cond, $m)) {
      $list = $this->parseList($m[1]);
      foreach ($list as $it) {
        if ($this->looseEqual($value, $it)) return true;
      }
      return false;
    }

    // matches("regex")
    if (preg_match('/^matches\s*\(\s*"(.+)"\s*\)$/i', $cond, $m)) {
      $re = $m[1];
      return is_string($value) ? (preg_match('/'.$re.'/', $value) === 1) : false;
    }

    // operadores numéricos
    if (preg_match('/^(<=|>=|<|>)\s*(.+)$/', $cond, $m)) {
      $op = $m[1];
      $rhs = $this->parseScalar($m[2]);
      $lhs = $this->toNumber($value);
      $rhsN = $this->toNumber($rhs);
      if ($lhs === null || $rhsN === null) return false;
      return match($op) {
        '<'  => $lhs <  $rhsN,
        '<=' => $lhs <= $rhsN,
        '>'  => $lhs >  $rhsN,
        '>=' => $lhs >= $rhsN,
      };
    }

    // igualdade: se vier com '=' remove
    if (preg_match('/^=\s*(.+)$/', $cond, $m)) {
      $rhs = $this->parseScalar($m[1]);
      return $this->looseEqual($value, $rhs);
    }

    // padrão: valor literal => igualdade
    $rhs = $this->parseScalar($cond);
    return $this->looseEqual($value, $rhs);
  }

  private function parseList(string $s): array {
    // separa por vírgula respeitando aspas simples/dobras (básico)
    $out = [];
    $buf = '';
    $inQ = false;
    $qChar = '';
    $len = strlen($s);
    for ($i=0; $i<$len; $i++) {
      $ch = $s[$i];
      if (($ch === '"' || $ch === "'") ) {
        if (!$inQ) { $inQ=true; $qChar=$ch; $buf.=$ch; continue; }
        if ($inQ && $qChar === $ch) { $inQ=false; $buf.=$ch; continue; }
      }
      if ($ch === ',' && !$inQ) {
        $out[] = $this->parseScalar(trim($buf));
        $buf = '';
      } else {
        $buf .= $ch;
      }
    }
    if (trim($buf) !== '') $out[] = $this->parseScalar(trim($buf));
    return $out;
  }

  private function parseScalar(string $s) {
    $s = trim($s);
    if ($s === 'null') return null;
    if ($s === 'true') return true;
    if ($s === 'false') return false;

    // string "..."
    if (preg_match('/^"(.*)"$/s', $s, $m)) return stripcslashes($m[1]);
    if (preg_match("/^'(.*)'$/s", $s, $m)) return $m[1];

    // número
    if (is_numeric($s)) {
      return (str_contains($s, '.') ? (float)$s : (int)$s);
    }

    return $s; // fallback string
  }

  private function toNumber($v): ?float {
    if ($v === null) return null;
    if (is_int($v) || is_float($v)) return (float)$v;
    if (is_string($v) && is_numeric($v)) return (float)$v;
    return null;
  }

  private function looseEqual($a, $b): bool {
    // tenta casar número com número, senão string
    $an = $this->toNumber($a); $bn = $this->toNumber($b);
    if ($an !== null && $bn !== null) return $an == $bn;
    return (string)$a === (string)$b;
  }

  private function coerceValue(string $raw, string $typeRef, array $ctx) {
    // v1: literal simples (sem funções ainda)
    $val = $this->parseScalar($raw);

    $typeRef = strtolower($typeRef);
    if ($typeRef === 'number' || $typeRef === 'integer' || $typeRef === 'double') {
      return $this->toNumber($val);
    }
    if ($typeRef === 'boolean') return (bool)$val;
    return $val; // string
  }
}

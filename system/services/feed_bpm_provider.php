<?php
// system/services/feed_bpm_provider.php
// Provider opcional de itens de Feed para eventos BPM (instâncias/tarefas).
if (!function_exists('feed_fetch_bpm_items')) {
  function feed_fetch_bpm_items(mysqli $conn, int $uid, int $limit = 10, int $offset = 0): array {
    $items = [];

    // helper: existe tabela?
    $exists = function(string $t) use ($conn): bool {
      $t = $conn->real_escape_string($t);
      $rs = $conn->query("SHOW TABLES LIKE '$t'");
      return $rs && $rs->num_rows > 0;
    };
    // Preferência do Feed do usuário: mostrar tarefas abertas (o que ele pode agir agora)
    if (!$exists('bpm_task') || !$exists('bpm_instance')) return $items;

    $uid_sql = (int)$uid;
    $hasNew  = $exists('bpm_process_version') && $exists('bpm_process');
    $hasElm  = $hasNew && $exists('bpm_process_version_element');

    $q = "
      SELECT
        t.id AS task_id,
        t.instance_id,
        t.node_id,
        t.status AS task_status,
        t.assignee_user_id,
        t.candidate_group,
        i.started_at,
        i.status AS instance_status,
        ".($hasNew ? "p.name AS process_name, p.category AS process_category, pv.semver AS semver" : "NULL AS process_name, NULL AS process_category, NULL AS semver")."
        ".($hasElm ? ", e.name AS step_name" : ", NULL AS step_name")."
      FROM bpm_task t
      JOIN bpm_instance i ON i.id=t.instance_id
      ".($hasNew ? "JOIN bpm_process_version pv ON pv.id=i.version_id\nJOIN bpm_process p ON p.id=pv.process_id\n" : "")."
      ".($hasElm ? "LEFT JOIN bpm_process_version_element e ON e.process_version_id=pv.id AND e.element_id=t.node_id\n" : "")."
      WHERE t.status IN ('ready','claimed','in_progress','error')
        AND (t.assignee_user_id IS NULL OR t.assignee_user_id = $uid_sql)
      ORDER BY i.started_at DESC, t.created_at DESC
      LIMIT $limit OFFSET $offset
    ";

    if ($rs = $conn->query($q)) {
      while ($r = $rs->fetch_assoc()) {
        $proc = (string)($r['process_name'] ?? 'BPM');
        $inst = (int)($r['instance_id'] ?? 0);
        $tid  = (int)($r['task_id'] ?? 0);
        $time = (string)($r['started_at'] ?? '');
        $step = (string)($r['step_name'] ?? $r['node_id'] ?? '');

        $parts = [];
        if (!empty($r['assignee_user_id'])) $parts[] = 'Usuário #'.((int)$r['assignee_user_id']);
        if (!empty($r['candidate_group']))  $parts[] = 'Grupo: '.((string)$r['candidate_group']);
        $participantes = $parts ? implode(' · ', $parts) : 'Disponível (sem responsável)';

        $titulo = 'BPM · '.$proc;
        $resumo = 'Etapa: '.$step.' · Participantes: '.$participantes;

        $items[] = [
          'id'       => $tid,
          'tipo'     => 'bpm',
          'titulo'   => $titulo,
          'resumo'   => $resumo,
          'dt_ref'   => $time,
          'featured' => 0,
          'link'     => BASE_URL.'/pages/bpm_task.php?id='.$tid,
          'extra'    => [
            'instance_id' => $inst,
            'started_at'  => $time,
            'process_name'=> $proc,
            'step_name'   => $step,
            'participants'=> $participantes,
            'task_id'     => $tid,
          ]
        ];
      }
    }
    return $items;
  }
}

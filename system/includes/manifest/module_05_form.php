<?php
// system/includes/manifest/module_05_form.php
// Manifesto do módulo de Formulários (Forms AI / Helix) do Mozart

return [
  'slug'        => 'forms',
  'name'        => 'Formulários',
  'area'        => ['back' => true, 'front' => false],
  'version'     => '2.0.0',
  'description' => 'Criador de formulários por IA (Wizard Helix), reutilizável em BPM, Helpdesk e outros módulos.',

  // === Capabilities ===
  'capabilities' => [
    // Catálogo
    'forms:read'   => 'Visualizar formulários',

    // Criação / edição
    'forms:create' => 'Criar formulários (Wizard / IA)',
    'forms:edit'   => 'Editar formulários',
    'forms:design' => 'Desenhar seções e campos',

    // Gestão (guard-chuva)
    'forms:manage' => 'Gerenciar formulários (versões/clone/categorias)',

    // Versionamento / publicação
    'forms:publish' => 'Publicar/Arquivar versões',
    'forms:block'   => 'Bloquear/Desbloquear formulários',
    'forms:clone'   => 'Clonar formulários',
    'forms:delete'  => 'Excluir formulários',

    // Categorias (moz_form_category)
    'forms:categories:manage' => 'Gerenciar categorias de formulários',

    // Submissões
    'forms:submissions:read'    => 'Ver submissões',
    'forms:submissions:manage'  => 'Gerenciar submissões (status/excluir)',
    'forms:submissions:export'  => 'Exportar submissões',
    'forms:submissions:reprocess'=> 'Reprocessar submissões',

    // Observabilidade
    'forms:audit:read'      => 'Ver auditoria',
    'forms:dashboard:read'  => 'Ver dashboard',

    // Hooks
    'forms:hooks:manage'    => 'Gerenciar hooks de reprocessamento',
  ],

  // === MENU lateral (backend) ===
  'menu' => [
    'back' => [
      [
        'label' => 'Formulários',
        'icon'  => 'fa fa-wpforms',
        'children' => [

          [
            'label'    => 'Listar Formulários',
            'url'      => BASE_URL . '/public/modules/forms/forms_listar.php',
            'requires' => ['forms:read'],
          ],

          [
            'label'    => 'Novo Formulário (IA)',
            'url'      => BASE_URL . '/public/modules/forms/wizard/1.php',
            'requires' => ['forms:create'],
          ],

          [
            'label'    => 'Gerenciar (Escolher)',
            'url'      => BASE_URL . '/public/modules/forms/forms_gerenciar_picker.php',
            'requires' => ['forms:manage'],
          ],

          [
            'label'    => 'Submissões',
            'url'      => BASE_URL . '/public/modules/forms/submissions_listar.php',
            'requires' => ['forms:submissions:read'],
          ],

          [
            'label'    => 'Dashboard',
            'url'      => BASE_URL . '/public/modules/forms/dashboard.php',
            'requires' => ['forms:dashboard:read'],
          ],

          [
            'label'    => 'Auditoria',
            'url'      => BASE_URL . '/public/modules/forms/audit_listar.php',
            'requires' => ['forms:audit:read'],
          ],

          [
            'label'    => 'Hooks (Reprocessar)',
            'url'      => BASE_URL . '/public/modules/forms/hooks_listar.php',
            'requires' => ['forms:hooks:manage'],
          ],

          [
            'label'    => 'Categorias de Formulário',
            'url'      => BASE_URL . '/public/modules/forms/categorias_form_listar.php',
            'requires' => ['forms:categories:manage'],
          ],
        ],
      ],
    ],
    'front' => [],
  ],

  // === Rotas para RBAC (usa SCRIPT_NAME, sem BASE_URL) ===
  'routes' => [
    // Catálogo
    [ 'path' => '/public/modules/forms/forms_listar.php',  'requires' => ['forms:read'] ],
    [ 'path' => '/public/modules/forms/forms_editar.php',  'requires' => ['forms:edit'] ],

    // Wizard
    [ 'path' => '/public/modules/forms/wizard/1.php',       'requires' => ['forms:create'] ],
    [ 'path' => '/public/modules/forms/wizard/2.php',       'requires' => ['forms:design'] ],
    [ 'path' => '/public/modules/forms/wizard/3.php',       'requires' => ['forms:design'] ],
    [ 'path' => '/public/modules/forms/wizard/preview.php', 'requires' => ['forms:read'] ],
    [ 'path' => '/public/modules/forms/wizard/4.php',       'requires' => ['forms:design'] ],
    [ 'path' => '/public/modules/forms/wizard/5.php',       'requires' => ['forms:design'] ],
    [ 'path' => '/public/modules/forms/wizard/5_preview.php','requires' => ['forms:design'] ],

    // Wizard actions
    [ 'path' => '/modules/forms/actions/form_ai_step1_generate.php', 'requires' => ['forms:create'] ],
    [ 'path' => '/modules/forms/actions/form_wizard_step1_save.php', 'requires' => ['forms:create'] ],

    [ 'path' => '/modules/forms/actions/forms_sections_save.php',     'requires' => ['forms:design'] ],
    [ 'path' => '/modules/forms/actions/forms_sections_delete.php',   'requires' => ['forms:design'] ],
    [ 'path' => '/modules/forms/actions/forms_sections_move.php',     'requires' => ['forms:design'] ],
    [ 'path' => '/modules/forms/actions/forms_fields_save.php',       'requires' => ['forms:design'] ],
    [ 'path' => '/modules/forms/actions/forms_fields_delete.php',     'requires' => ['forms:design'] ],
    [ 'path' => '/modules/forms/actions/forms_fields_move.php',       'requires' => ['forms:design'] ],

    [ 'path' => '/modules/forms/actions/forms_rules_save.php',        'requires' => ['forms:design'] ],
    [ 'path' => '/modules/forms/actions/forms_rules_delete.php',      'requires' => ['forms:design'] ],
    [ 'path' => '/modules/forms/actions/forms_rules_move.php',        'requires' => ['forms:design'] ],

    [ 'path' => '/modules/forms/actions/forms_datasets_save.php',     'requires' => ['forms:design'] ],
    [ 'path' => '/modules/forms/actions/forms_datasets_delete.php',   'requires' => ['forms:design'] ],
    [ 'path' => '/modules/forms/actions/forms_datasets_move.php',     'requires' => ['forms:design'] ],

    // Submissions
    [ 'path' => '/public/modules/forms/submissions_listar.php', 'requires' => ['forms:submissions:read'] ],
    [ 'path' => '/public/modules/forms/submissions_ver.php',    'requires' => ['forms:submissions:read'] ],
    [ 'path' => '/modules/forms/actions/submissions_status.php','requires' => ['forms:submissions:manage'] ],
    [ 'path' => '/modules/forms/actions/submissions_delete.php','requires' => ['forms:submissions:manage'] ],
    [ 'path' => '/modules/forms/actions/submissions_export.php','requires' => ['forms:submissions:export'] ],
    [ 'path' => '/modules/forms/actions/submissions_reprocess.php','requires' => ['forms:submissions:reprocess'] ],

    // Gestão/versões
    [ 'path' => '/public/modules/forms/forms_gerenciar.php',        'requires' => ['forms:manage'] ],
    [ 'path' => '/public/modules/forms/forms_gerenciar_picker.php', 'requires' => ['forms:manage'] ],
    [ 'path' => '/modules/forms/actions/forms_publish.php',         'requires' => ['forms:publish'] ],
    [ 'path' => '/modules/forms/actions/forms_archive_version.php', 'requires' => ['forms:publish'] ],
    [ 'path' => '/modules/forms/actions/forms_new_version.php',     'requires' => ['forms:publish'] ],
    [ 'path' => '/modules/forms/actions/forms_toggle_block.php',    'requires' => ['forms:block'] ],
    [ 'path' => '/modules/forms/actions/forms_clone.php',           'requires' => ['forms:clone'] ],
    [ 'path' => '/modules/forms/actions/forms_delete.php',          'requires' => ['forms:delete'] ],
    [ 'path' => '/modules/forms/actions/forms_set_categories.php',  'requires' => ['forms:manage'] ],

    // Observabilidade
    [ 'path' => '/public/modules/forms/audit_listar.php', 'requires' => ['forms:audit:read'] ],
    [ 'path' => '/public/modules/forms/dashboard.php',    'requires' => ['forms:dashboard:read'] ],

    // Hooks
    [ 'path' => '/public/modules/forms/hooks_listar.php', 'requires' => ['forms:hooks:manage'] ],
    [ 'path' => '/public/modules/forms/hooks_form.php',   'requires' => ['forms:hooks:manage'] ],
    [ 'path' => '/modules/forms/actions/hooks_save.php',  'requires' => ['forms:hooks:manage'] ],
    [ 'path' => '/modules/forms/actions/hooks_toggle.php','requires' => ['forms:hooks:manage'] ],
    [ 'path' => '/modules/forms/actions/hooks_delete.php','requires' => ['forms:hooks:manage'] ],
    [ 'path' => '/modules/forms/actions/hooks_test.php',  'requires' => ['forms:hooks:manage'] ],
  ],

  // === Perfis padrão (role_defaults) ===
  'role_defaults' => [
    'superadmin' => ['*'],
    'admin_forms' => ['forms:*'],
    'designer_forms' => [
      'forms:read','forms:create','forms:edit','forms:design','forms:publish','forms:manage',
    ],
    'operador_forms' => ['forms:read'],
  ],
];

<?php
// /system/includes/manifest/module_06_dmn.php
return [
    'slug'        => 'dmn',
    'name'        => 'DMN / Decisões',
    'area'        => ['back' => true, 'front' => false],
    'version'     => '1.0.0',
    'description' => 'Biblioteca de decisões DMN, editor visual, categorias, versões e runner.',

    // === Capabilities ===
    'capabilities' => [
        // Biblioteca / leitura
        'dmn:library:read'        => 'Ver biblioteca de decisões DMN',
        'dmn:decision:read'       => 'Ver decisão DMN',

        // Gestão
        'dmn:decision:manage'     => 'Criar/editar decisões DMN',
        'dmn:decision:publish'    => 'Publicar versões DMN',
        'dmn:categories:manage'   => 'Gerenciar categorias DMN',
        'dmn:versions:read'       => 'Ver versões/exports',
        'dmn:testcases:manage'    => 'Gerenciar testcases (runner)',

        // Execução (API para BPM)
        'dmn:evaluate:run'        => 'Executar/evaluar DMN (API)',
    ],

    // === MENU lateral (backend) ===
    'menu' => [
        'back' => [
            [
                'label' => 'DMN / Decisões',
                'icon'  => 'fa fa-sitemap',
                'children' => [
                    [
                        'label'    => 'Biblioteca',
                        'url'      => BASE_URL . '/modules/dmn/dmn_list.php',
                        'requires' => ['dmn:library:read'],
                    ],
                    [
                        'label'    => 'Nova decisão',
                        'url'      => BASE_URL . '/modules/dmn/dmn_editor.php',
                        'requires' => ['dmn:decision:manage'],
                    ],
                    [
                        'label'    => 'Categorias',
                        'url'      => BASE_URL . '/modules/dmn/dmn_categories.php',
                        'requires' => ['dmn:categories:manage'],
                    ],
                ],
            ],
        ],
        'front' => [],
    ],

    // === Rotas para RBAC (usa SCRIPT_NAME, sem BASE_URL) ===
    'routes' => [
        // UI
        [ 'path' => '/modules/dmn/index.php',           'requires' => ['dmn:library:read'] ],
        [ 'path' => '/modules/dmn/dmn_list.php',       'requires' => ['dmn:library:read'] ],
        [ 'path' => '/modules/dmn/dmn_editor.php',     'requires' => ['dmn:decision:manage'] ],
        [ 'path' => '/modules/dmn/dmn_categories.php', 'requires' => ['dmn:categories:manage'] ],
        [ 'path' => '/modules/dmn/dmn_versions.php',   'requires' => ['dmn:versions:read'] ],

        // APIs (se seu RBAC também valida /modules/*/api/*.php)
        [ 'path' => '/modules/dmn/api/categories.php',       'requires' => ['dmn:categories:manage'] ],
        [ 'path' => '/modules/dmn/api/decision_list.php',    'requires' => ['dmn:library:read'] ],
        [ 'path' => '/modules/dmn/api/decision_get.php',     'requires' => ['dmn:decision:read'] ],
        [ 'path' => '/modules/dmn/api/decision_save.php',    'requires' => ['dmn:decision:manage'] ],
        [ 'path' => '/modules/dmn/api/decision_publish.php', 'requires' => ['dmn:decision:publish'] ],
        [ 'path' => '/modules/dmn/api/version_list.php',     'requires' => ['dmn:versions:read'] ],
        [ 'path' => '/modules/dmn/api/version_get_xml.php',  'requires' => ['dmn:versions:read'] ],
        [ 'path' => '/modules/dmn/api/testcase_list.php',    'requires' => ['dmn:testcases:manage'] ],
        [ 'path' => '/modules/dmn/api/testcase_save.php',    'requires' => ['dmn:testcases:manage'] ],
        [ 'path' => '/modules/dmn/api/evaluate.php',         'requires' => ['dmn:evaluate:run'] ],
    ],

    // === Perfis padrão ===
    'role_defaults' => [
        'superadmin' => ['*'],

        'admin_dmn'  => ['dmn:*'],

        'gestor_dmn' => [
            'dmn:library:read',
            'dmn:decision:read',
            'dmn:decision:manage',
            'dmn:decision:publish',
            'dmn:categories:manage',
            'dmn:versions:read',
            'dmn:testcases:manage',
            'dmn:evaluate:run',
        ],

        'leitor_dmn' => [
            'dmn:library:read',
            'dmn:decision:read',
            'dmn:versions:read',
        ],

        // Perfil mínimo pro BPM “chamar” decisions via API (se separar)
        'runner_dmn' => [
            'dmn:evaluate:run',
        ],
    ],
];

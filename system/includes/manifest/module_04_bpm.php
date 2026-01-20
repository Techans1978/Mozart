<?php
return [
    'slug'        => 'bpm',
    'name'        => 'BPM / Orquestração de Processos',
    'area'        => ['back' => true, 'front' => false],
    'version'     => '1.0.0',
    'description' => 'Designer BPMN, decisão, datasets, substitutos e orquestração de processos.',

    // === Capabilities ===
    'capabilities' => [
        // Processos & Diagramas
        'bpm:processos:read'          => 'Ver processos',
        'bpm:processos:design'        => 'Desenhar processos / diagramas',
        'bpm:processos:deploy'        => 'Publicar processos',
        'bpm:processos:update'        => 'Editar metadados de processos',

        // Instâncias
        'bpm:instancias:read'         => 'Ver instâncias',
        'bpm:instancias:operate'      => 'Operar instâncias (avançar, cancelar)',

        // Decision / DMN
        'bpm:decision:manage'         => 'Gerenciar decisões (Decision)',

        // DataSets
        'bpm:datasets:manage'         => 'Gerenciar datasets BPM',

        // Substitutos / Transferências / Códigos adicionais
        'bpm:substitutos:manage'      => 'Gerenciar substitutos',
        'bpm:pendencias:transfer'     => 'Transferir pendências',
        'bpm:addcode:manage'          => 'Gerenciar códigos adicionais',

        // Conectores / API (mantido para futuro uso)
        'bpm:connectors:manage'       => 'Gerenciar conectores/API',

        // BPMs AI
        'bpm:bpmsai:read'    => 'Ver BPMs AI',
        'bpm:bpmsai:manage'  => 'Criar/editar BPMs AI',
        'bpm:bpmsai:publish' => 'Publicar versões BPMs AI',
        'bpm:bpmsai:test'    => 'Testar BPMs AI (IA/Humano)',

    ],

    // === MENU lateral (backend) ===
    'menu' => [
        'back' => [
            [
                'label' => 'BPM / Processos',
                'icon'  => 'fa fa-tasks',
                'children' => [
                    [
                    'label'    => 'BPMs AI',
                    'url'      => BASE_URL . '/modules/bpm/bpmsai-listar.php',
                    'requires' => ['bpm:bpmsai:read'],
                    ],
                    [
                    'label'    => 'Wizard BPMs AI',
                    'url'      => BASE_URL . '/modules/bpm/bpmsai-wizard.php',
                    'requires' => ['bpm:bpmsai:manage'],
                    ],
                    [
                        'label'    => 'Wizard BPM',
                        'url'      => BASE_URL . '/modules/bpm/wizard_bpm.php',
                        'requires' => ['bpm:processos:design'],
                    ],
                    [
                        'label'    => 'Diagramas',
                        'url'      => BASE_URL . '/modules/bpm/processos-listar.php',
                        'requires' => ['bpm:processos:design'],
                    ],
                    [
                        'label'    => 'Categorias',
                        'url'      => BASE_URL . '/modules/bpm/categorias_bpm_listar.php',
                        'requires' => ['bpm:processos:update'],
                    ],
                    [
                        'label'    => 'Decision',
                        'url'      => BASE_URL . '/modules/bpm/decision_listar.php',
                        'requires' => ['bpm:decision:manage'],
                    ],
                    [
                        'label'    => 'Data Sets',
                        'url'      => BASE_URL . '/modules/bpm/dataset_listar.php',
                        'requires' => ['bpm:datasets:manage'],
                    ],
                    [
                        'label'    => 'Substitutos',
                        'url'      => BASE_URL . '/modules/bpm/substitutos_listar.php',
                        'requires' => ['bpm:substitutos:manage'],
                    ],
                    [
                        'label'    => 'Transferir Pendências',
                        'url'      => BASE_URL . '/modules/bpm/tranfpendencias_listar.php',
                        'requires' => ['bpm:pendencias:transfer'],
                    ],
                    [
                        'label'    => 'Códigos Adicionais',
                        'url'      => BASE_URL . '/modules/bpm/addcode_listar.php',
                        'requires' => ['bpm:addcode:manage'],
                    ],
                ],
            ],
        ],
        'front' => [],
    ],

    // === Rotas para RBAC (usa SCRIPT_NAME, sem BASE_URL) ===
    'routes' => [
        // UI
        [ 'path' => '/modules/bpm/wizard_bpm.php',             'requires' => ['bpm:processos:design'] ],
        [ 'path' => '/modules/bpm/processos-listar.php',       'requires' => ['bpm:processos:design'] ],

        // (mantém caso exista no projeto)
        [ 'path' => '/modules/bpm/bpm_designer-listar.php',    'requires' => ['bpm:processos:design'] ],

        [ 'path' => '/modules/bpm/categorias_bpm_listar.php',  'requires' => ['bpm:processos:update'] ],
        [ 'path' => '/modules/bpm/decision_listar.php',        'requires' => ['bpm:decision:manage'] ],
        [ 'path' => '/modules/bpm/dataset_listar.php',         'requires' => ['bpm:datasets:manage'] ],
        [ 'path' => '/modules/bpm/substitutos_listar.php',     'requires' => ['bpm:substitutos:manage'] ],
        [ 'path' => '/modules/bpm/tranfpendencias_listar.php', 'requires' => ['bpm:pendencias:transfer'] ],
        [ 'path' => '/modules/bpm/addcode_listar.php',         'requires' => ['bpm:addcode:manage'] ],

        // API (Fase 6 Runtime MVP)
        [ 'path' => '/modules/bpm/api/instance_start.php',     'requires' => ['bpm:instancias:operate'] ],
        [ 'path' => '/modules/bpm/api/task_complete.php',      'requires' => ['bpm:instancias:operate'] ],

        // BPMs AI
        [ 'path' => '/modules/bpm/bpmsai-listar.php',  'requires' => ['bpm:bpmsai:read'] ],
        [ 'path' => '/modules/bpm/bpmsai-wizard.php',  'requires' => ['bpm:bpmsai:manage'] ],
        [ 'path' => '/modules/bpm/bpmsai-versoes.php', 'requires' => ['bpm:bpmsai:read'] ],

        [ 'path' => '/modules/bpm/api/bpmsai_flow_save.php',    'requires' => ['bpm:bpmsai:manage'] ],
        [ 'path' => '/modules/bpm/api/bpmsai_flow_publish.php', 'requires' => ['bpm:bpmsai:publish'] ],
        [ 'path' => '/modules/bpm/api/bpmsai_test_ai.php',      'requires' => ['bpm:bpmsai:test'] ],
        [ 'path' => '/modules/bpm/api/bpmsai_test_human.php',   'requires' => ['bpm:bpmsai:test'] ],
    ],

    // === Perfis padrão ===
    'role_defaults' => [
        'superadmin'      => ['*'],

        'admin_bpm'       => ['bpm:*'],

        'designer_bpm'    => [
            'bpm:processos:read',
            'bpm:processos:design',
            'bpm:processos:deploy',
            'bpm:processos:update',
            'bpm:decision:manage',
            'bpm:datasets:manage',
            'bpm:substitutos:manage',
            'bpm:addcode:manage',
            'bpm:connectors:manage',
            'bpm:bpmsai:read',
            'bpm:bpmsai:manage',
            'bpm:bpmsai:publish',
            'bpm:bpmsai:test',
        ],

        'operador_bpm'    => [
            'bpm:instancias:read',
            'bpm:instancias:operate',
        ],
    ],
];

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

        // Formulários
        'bpm:forms:manage'            => 'Gerenciar formulários BPM',

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
    ],

    // === MENU lateral (backend) ===
    'menu' => [
        'back' => [
            [
                'label' => 'BPM / Processos',
                'icon'  => 'fa fa-tasks', // ou 'ti ti-flowchart' se preferir Tabler
                'children' => [
                    [
                        'label'    => 'Wizard BPM',
                        'url'      => BASE_URL . '/modules/bpm/wizard_bpm.php',
                        'requires' => ['bpm:processos:design'],
                    ],
                    [
                        'label'    => 'Diagramas',
                        'url'      => BASE_URL . '/modules/bpm/bpm_designer-listar.php',
                        'requires' => ['bpm:processos:design'],
                    ],
                    [
                        'label'    => 'Formulários',
                        'url'      => BASE_URL . '/modules/bpm/forms/forms_designer.php',
                        'requires' => ['bpm:forms:manage'],
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
        [ 'path' => '/modules/bpm/wizard_bpm.php',             'requires' => ['bpm:processos:design'] ],
        [ 'path' => '/modules/bpm/bpm_designer-listar.php',    'requires' => ['bpm:processos:design'] ],
        [ 'path' => '/modules/bpm/forms/forms_designer.php',   'requires' => ['bpm:forms:manage'] ],
        [ 'path' => '/modules/bpm/categorias_bpm_listar.php',  'requires' => ['bpm:processos:update'] ],
        [ 'path' => '/modules/bpm/decision_listar.php',        'requires' => ['bpm:decision:manage'] ],
        [ 'path' => '/modules/bpm/dataset_listar.php',         'requires' => ['bpm:datasets:manage'] ],
        [ 'path' => '/modules/bpm/substitutos_listar.php',     'requires' => ['bpm:substitutos:manage'] ],
        [ 'path' => '/modules/bpm/tranfpendencias_listar.php', 'requires' => ['bpm:pendencias:transfer'] ],
        [ 'path' => '/modules/bpm/addcode_listar.php',         'requires' => ['bpm:addcode:manage'] ],
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
            'bpm:forms:manage',
            'bpm:decision:manage',
            'bpm:datasets:manage',
            'bpm:substitutos:manage',
            'bpm:addcode:manage',
            'bpm:connectors:manage',
        ],

        'operador_bpm'    => [
            'bpm:instancias:read',
            'bpm:instancias:operate',
            // se quiser que operador veja datasets/decision, dá pra incluir aqui depois
        ],
    ],
];

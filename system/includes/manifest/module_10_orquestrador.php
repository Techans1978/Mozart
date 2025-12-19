<?php
return [
    'slug'        => 'orquestrador_api',
    'name'        => 'Orquestrador de API',
    'area'        => ['back' => true, 'front' => false],
    'version'     => '1.0.0',
    'description' => 'Criação guiada de conectores, orquestração de flows e integrações entre sistemas.',

    // === Capabilities ===
    'capabilities' => [
        'orqapi:connectors:read'    => 'Ver conectores',
        'orqapi:connectors:manage'  => 'Criar/editar conectores',
        'orqapi:flows:read'         => 'Ver flows',
        'orqapi:flows:manage'       => 'Criar/editar flows',
        'orqapi:flows:execute'      => 'Executar flows manualmente',
    ],

    // === MENU lateral (backend) ===
    'menu' => [
        'back' => [
            [
                'label' => 'Orquestrador de API',
                'icon'  => 'fa fa-arrows',
                'children' => [
                    [
                        'label'    => 'Criação Guiada',
                        'url'      => BASE_URL . '/modules/orquestrador_api/conectores.php',
                        'requires' => ['orqapi:connectors:manage'],
                    ],
                    [
                        'label'    => 'Flows',
                        'url'      => BASE_URL . '/modules/orquestrador_api/flows-listar.php',
                        'requires' => ['orqapi:flows:read'],
                    ],
                    [
                        'label'    => 'Conectores',
                        'url'      => BASE_URL . '/modules/orquestrador_api/connectors-builder.php',
                        'requires' => ['orqapi:connectors:read'],
                    ],
                ],
            ],
        ],
        'front' => [],
    ],

    // === Rotas para RBAC (SCRIPT_NAME) ===
    'routes' => [
        [ 'path' => '/modules/orquestrador_api/conectores.php',        'requires' => ['orqapi:connectors:manage'] ],
        [ 'path' => '/modules/orquestrador_api/flows-listar.php',      'requires' => ['orqapi:flows:read'] ],
        [ 'path' => '/modules/orquestrador_api/connectors-builder.php','requires' => ['orqapi:connectors:read'] ],
    ],

    // === Perfis padrão ===
    'role_defaults' => [
        'superadmin'          => ['*'],

        'admin_orquestrador'  => [
            'orqapi:*',
        ],

        'designer_integracao' => [
            'orqapi:connectors:read',
            'orqapi:connectors:manage',
            'orqapi:flows:read',
            'orqapi:flows:manage',
        ],

        'operador_integracao' => [
            'orqapi:flows:read',
            'orqapi:flows:execute',
        ],
    ],
];

<?php
return [
    'slug'        => 'orquestrador',
    'name'        => 'Orquestrador de API',
    'area'        => ['back' => true, 'front' => false],
    'version'     => '1.0.0',
    'description' => 'Conectores, fluxos e orquestração de APIs externas.',

    'capabilities' => [
        'orq:connectors:read'   => 'Ver conectores',
        'orq:connectors:manage' => 'Criar/editar conectores',
        'orq:flows:read'        => 'Ver fluxos',
        'orq:flows:manage'      => 'Criar/editar fluxos',
        'orq:flows:deploy'      => 'Publicar fluxos',
        'orq:flows:run'         => 'Disparar fluxos manualmente',
        'orq:secrets:manage'    => 'Gerenciar credenciais e segredos',
    ],

    'menu' => [
        'back' => [
            [
                'label' => '<i class="ti ti-api"></i> Orquestrador de API',
                'children' => [
                    [
                        'label' => 'Conectores',
                        'route' => BASE_URL.'/modules/orquestrador/connectors-listar.php',
                        'requires' => ['orq:connectors:read'],
                    ],
                    [
                        'label' => 'Criar Conector',
                        'route' => BASE_URL.'/modules/orquestrador/connectors-form.php',
                        'requires' => ['orq:connectors:manage'],
                    ],
                    [
                        'label' => 'Fluxos',
                        'route' => BASE_URL.'/modules/orquestrador/flows-listar.php',
                        'requires' => ['orq:flows:read'],
                    ],
                    [
                        'label' => 'Criar Fluxo',
                        'route' => BASE_URL.'/modules/orquestrador/flows-form.php',
                        'requires' => ['orq:flows:manage'],
                    ],
                    [
                        'label' => 'Credenciais',
                        'route' => BASE_URL.'/modules/orquestrador/secrets-listar.php',
                        'requires' => ['orq:secrets:manage'],
                    ],
                ],
            ],
        ],
        'front' => [],
    ],

    'routes' => [
        [ 'path' => '/modules/orquestrador/connectors-listar.php', 'requires' => ['orq:connectors:read'] ],
        [ 'path' => '/modules/orquestrador/connectors-form.php',   'requires' => ['orq:connectors:manage'] ],
        [ 'path' => '/modules/orquestrador/flows-listar.php',      'requires' => ['orq:flows:read'] ],
        [ 'path' => '/modules/orquestrador/flows-form.php',        'requires' => ['orq:flows:manage'] ],
        [ 'path' => '/modules/orquestrador/secrets-listar.php',    'requires' => ['orq:secrets:manage'] ],
    ],

    'role_defaults' => [
        'superadmin'       => ['*'],
        'admin_orq'        => ['orq:*'],
        'dev_orq'          => [
            'orq:connectors:*',
            'orq:flows:*',
        ],
    ],
];

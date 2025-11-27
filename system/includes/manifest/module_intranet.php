<?php
return [
    'slug'        => 'intranet',
    'name'        => 'Intranet',
    'area'        => ['back' => true, 'front' => true],
    'version'     => '1.0.0',
    'description' => 'Notícias internas, comunicados, documentos e agenda.',

    'capabilities' => [
        'intranet:news:read'      => 'Ver notícias',
        'intranet:news:manage'    => 'Gerenciar notícias',
        'intranet:docs:read'      => 'Ver documentos',
        'intranet:docs:manage'    => 'Gerenciar documentos',
        'intranet:events:read'    => 'Ver eventos/agenda',
        'intranet:events:manage'  => 'Gerenciar eventos/agenda',
    ],

    'menu' => [
        'back' => [
            [
                'label' => '<i class="ti ti-planet"></i> Intranet',
                'children' => [
                    [
                        'label' => 'Notícias',
                        'route' => BASE_URL.'/modules/intranet/news-listar.php',
                        'requires' => ['intranet:news:read'],
                    ],
                    [
                        'label' => 'Documentos',
                        'route' => BASE_URL.'/modules/intranet/docs-listar.php',
                        'requires' => ['intranet:docs:read'],
                    ],
                    [
                        'label' => 'Agenda / Eventos',
                        'route' => BASE_URL.'/modules/intranet/events-listar.php',
                        'requires' => ['intranet:events:read'],
                    ],
                ],
            ],
        ],
        'front' => [
            [
                'label' => '<i class="ti ti-planet"></i> Intranet',
                'children' => [
                    [
                        'label' => 'Notícias',
                        'route' => BASE_URL.'/modules/intranet/news-listar.php',
                        'requires' => ['intranet:news:read'],
                    ],
                    [
                        'label' => 'Documentos',
                        'route' => BASE_URL.'/modules/intranet/docs-listar.php',
                        'requires' => ['intranet:docs:read'],
                    ],
                    [
                        'label' => 'Agenda / Eventos',
                        'route' => BASE_URL.'/modules/intranet/events-listar.php',
                        'requires' => ['intranet:events:read'],
                    ],
                ],
            ],
        ],
    ],

    'routes' => [
        [ 'path' => '/modules/intranet/news-listar.php',    'requires' => ['intranet:news:read'] ],
        [ 'path' => '/modules/intranet/docs-listar.php',    'requires' => ['intranet:docs:read'] ],
        [ 'path' => '/modules/intranet/events-listar.php',  'requires' => ['intranet:events:read'] ],
    ],

    'role_defaults' => [
        'superadmin'       => ['*'],
        'editor_intranet'  => ['intranet:*'],
        'colaborador'      => [
            'intranet:news:read',
            'intranet:docs:read',
            'intranet:events:read',
        ],
    ],
];

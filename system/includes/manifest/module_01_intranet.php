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
                'label' => 'Intranet',
                'icon'  => 'fa fa-comments',   // **icone separado**
                'children' => [
                    [
                        'label'    => 'Artigos',
                        'url'      => BASE_URL.'/pages/conteudo_listar.php',
                        'requires' => ['intranet:news:read'],
                    ],
                    [
                        'label'    => 'Categorias de Artigos',
                        'url'      => BASE_URL.'/pages/conteudo_categorias.php',
                        'requires' => ['intranet:news:read'],
                    ],
                    [
                        'label'    => 'Dicas',
                        'url'      => BASE_URL.'/pages/dicas_listar.php',
                        'requires' => ['intranet:news:read'],
                    ],
                    [
                        'label'    => 'Mídia Feed',
                        'url'      => BASE_URL.'/pages/media_listar.php',
                        'requires' => ['intranet:news:read'],
                    ],
                    [
                        'label'    => 'Documentos',
                        'url'      => BASE_URL.'/pages/docs_listar.php',
                        'requires' => ['intranet:news:read'],
                    ],
                    [
                        'label'    => 'Calendário de Eventos',
                        'url'      => BASE_URL.'/pages/event_listar.php',
                        'requires' => ['intranet:news:read'],
                    ],
                ],
            ],
        ],

        'front' => [
            [
                'label' => 'Intranet',
                'icon'  => 'fa fa-comments',
                'children' => [
                    [
                        'label'    => 'Notícias',
                        'url'      => BASE_URL.'/modules/intranet/news-listar.php',
                        'requires' => ['intranet:news:read'],
                    ],
                    [
                        'label'    => 'Documentos',
                        'url'      => BASE_URL.'/modules/intranet/docs-listar.php',
                        'requires' => ['intranet:docs:read'],
                    ],
                    [
                        'label'    => 'Agenda / Eventos',
                        'url'      => BASE_URL.'/modules/intranet/events-listar.php',
                        'requires' => ['intranet:events:read'],
                    ],
                ],
            ],
        ],
    ],

    // Rotas para RBAC (path = exatamente o que vem em $_SERVER['SCRIPT_NAME'])
    'routes' => [
        // Backend (páginas em /pages)
        [ 'path' => '/pages/conteudo_listar.php',      'requires' => ['intranet:news:read'] ],
        [ 'path' => '/pages/conteudo_categorias.php',  'requires' => ['intranet:news:read'] ],
        [ 'path' => '/pages/dicas_listar.php',         'requires' => ['intranet:news:read'] ],
        [ 'path' => '/pages/media_listar.php',         'requires' => ['intranet:news:read'] ],
        [ 'path' => '/pages/docs_listar.php',          'requires' => ['intranet:docs:read'] ],
        [ 'path' => '/pages/event_listar.php',         'requires' => ['intranet:events:read'] ],

        // Frontend (módulo intranet)
        [ 'path' => '/modules/intranet/news-listar.php',   'requires' => ['intranet:news:read'] ],
        [ 'path' => '/modules/intranet/docs-listar.php',   'requires' => ['intranet:docs:read'] ],
        [ 'path' => '/modules/intranet/events-listar.php', 'requires' => ['intranet:events:read'] ],
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

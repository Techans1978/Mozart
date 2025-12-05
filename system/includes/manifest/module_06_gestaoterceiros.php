<?php
return [
    'slug'        => 'gestao_terceiros',
    'name'        => 'Gestão de Terceiros',
    'area'        => ['back' => true, 'front' => true],
    'version'     => '1.0.0',
    'description' => 'Gestão de fornecedores, terceiros, categorias, QR Codes e portal do terceiro.',

    'capabilities' => [
        // Fornecedores
        'gestao_terceiros:fornecedor:read'   => 'Ver fornecedores',
        'gestao_terceiros:fornecedor:manage' => 'Gerenciar fornecedores (cadastrar, importar, editar, excluir)',

        // Terceiros
        'gestao_terceiros:terceiro:read'     => 'Ver terceiros',
        'gestao_terceiros:terceiro:manage'   => 'Gerenciar terceiros (cadastrar, importar/exportar, editar, excluir)',

        // Cadastros (categorias, QRCode, política)
        'gestao_terceiros:cadastro:categorias' => 'Gerenciar categorias de prestador',
        'gestao_terceiros:cadastro:qrcode'     => 'Gerenciar QRCodes de terceiros',
        'gestao_terceiros:cadastro:politica'   => 'Gerenciar política de privacidade dos terceiros',

        // Portal do terceiro (front)
        'gestao_terceiros:portal:access'    => 'Acessar portal do terceiro (editar perfil, senha, QR Code, política)',
    ],

    'menu' => [
        'back' => [
            [
                'label' => 'Gestão de Terceiros',
                'icon'  => 'fa fa-suitcase', // ajusta o ícone se quiser
                'children' => [
                    // Fornecedores
                    [
                        'label'    => 'Dashboard',
                        'url'      => BASE_URL.'/modules/gestao_terceiros/admin/dashboard.php',
                        'requires' => ['gestao_terceiros:fornecedor:read'],
                    ],
                    [
                        'label'    => 'Fornecedores - Listar',
                        'url'      => BASE_URL.'/modules/gestao_terceiros/admin/edit/listar_fornecedores.php',
                        'requires' => ['gestao_terceiros:fornecedor:read'],
                    ],
                    [
                        'label'    => 'Fornecedores - Importar',
                        'url'      => BASE_URL.'/modules/gestao_terceiros/admin/edit/importar_fornecedores.php',
                        'requires' => ['gestao_terceiros:fornecedor:manage'],
                    ],

                    // Terceiros
                    [
                        'label'    => 'Terceiros - Listar',
                        'url'      => BASE_URL.'/modules/gestao_terceiros/admin/edit/listar_terceiros.php',
                        'requires' => ['gestao_terceiros:terceiro:read'],
                    ],
                    [
                        'label'    => 'Terceiros - Importar/Exportar',
                        'url'      => BASE_URL.'/modules/gestao_terceiros/admin/edit/exportar_terceiros.php',
                        'requires' => ['gestao_terceiros:terceiro:manage'],
                    ],

                    // Cadastros
                    [
                        'label'    => 'Categorias do Prestador',
                        'url'      => BASE_URL.'/modules/gestao_terceiros/admin/edit/listar_categorias.php',
                        'requires' => ['gestao_terceiros:cadastro:categorias'],
                    ],
                    [
                        'label'    => 'Lista de QRCodes',
                        'url'      => BASE_URL.'/modules/gestao_terceiros/admin/edit/listar_qrcode.php',
                        'requires' => ['gestao_terceiros:cadastro:qrcode'],
                    ],
                    [
                        'label'    => 'Política de Privacidade',
                        'url'      => BASE_URL.'/modules/gestao_terceiros/admin/edit/politicadeprivacidade.php',
                        'requires' => ['gestao_terceiros:cadastro:politica'],
                    ],
                ],
            ],
        ],

        'front' => [
            [
                'label' => 'Gestão de Terceiros',
                'icon'  => 'fa fa-handshake',
                'children' => [
                    [
                        'label'    => 'Editar Perfil',
                        'url'      => BASE_URL.'/modules/gestao_terceiros/terceiro/editar_terceiro.php',
                        'requires' => ['gestao_terceiros:portal:access'],
                    ],
                    [
                        'label'    => 'Baixar QR Code',
                        'url'      => BASE_URL.'/modules/gestao_terceiros/terceiro/gerar_qrcode.php',
                        'requires' => ['gestao_terceiros:portal:access'],
                    ],
                    [
                        'label'    => 'Alterar senha',
                        'url'      => BASE_URL.'/modules/gestao_terceiros/terceiro/terceiro_senha.php',
                        'requires' => ['gestao_terceiros:portal:access'],
                    ],
                    // Aqui assumi o arquivo politicadeprivacidade.php na área do terceiro
                    [
                        'label'    => 'Política de Privacidade',
                        'url'      => BASE_URL.'/modules/gestao_terceiros/terceiro/politicadeprivacidade.php',
                        'requires' => ['gestao_terceiros:portal:access'],
                    ],
                    // Logout genérico (ajusta a URL se o seu logout for outro path)
                    [
                        'label'    => 'Sair',
                        'url'      => BASE_URL.'/logout.php',
                        'requires' => ['gestao_terceiros:portal:access'],
                    ],
                ],
            ],
        ],
    ],

    'routes' => [
        // Backend
        [ 'path' => '/modules/gestao_terceiros/admin/dashboard.php',   'requires' => ['gestao_terceiros:fornecedor:read']   ],
        [ 'path' => '/modules/gestao_terceiros/admin/edit/listar_fornecedores.php',   'requires' => ['gestao_terceiros:fornecedor:read']   ],
        [ 'path' => '/modules/gestao_terceiros/admin/edit/importar_fornecedores.php', 'requires' => ['gestao_terceiros:fornecedor:manage'] ],

        [ 'path' => '/modules/gestao_terceiros/admin/edit/listar_terceiros.php',      'requires' => ['gestao_terceiros:terceiro:read']     ],
        [ 'path' => '/modules/gestao_terceiros/admin/edit/exportar_terceiros.php',    'requires' => ['gestao_terceiros:terceiro:manage']   ],

        [ 'path' => '/modules/gestao_terceiros/admin/edit/listar_categorias.php',     'requires' => ['gestao_terceiros:cadastro:categorias'] ],
        [ 'path' => '/modules/gestao_terceiros/admin/edit/listar_qrcode.php',         'requires' => ['gestao_terceiros:cadastro:qrcode']     ],
        [ 'path' => '/modules/gestao_terceiros/admin/edit/politicadeprivacidade.php', 'requires' => ['gestao_terceiros:cadastro:politica']   ],

        // Front (portal do terceiro)
        [ 'path' => '/modules/gestao_terceiros/terceiro/editar_terceiro.php',        'requires' => ['gestao_terceiros:portal:access'] ],
        [ 'path' => '/modules/gestao_terceiros/terceiro/gerar_qrcode.php',           'requires' => ['gestao_terceiros:portal:access'] ],
        [ 'path' => '/modules/gestao_terceiros/terceiro/terceiro_senha.php',         'requires' => ['gestao_terceiros:portal:access'] ],
        [ 'path' => '/modules/gestao_terceiros/terceiro/politicadeprivacidade.php',  'requires' => ['gestao_terceiros:portal:access'] ],
        // se quiser controlar RBAC no logout, descomenta e ajusta o path:
        // [ 'path' => '/logout.php', 'requires' => ['gestao_terceiros:portal:access'] ],
    ],

    'role_defaults' => [
        'superadmin' => ['*'],

        // Admin completo da gestão de terceiros
        'gestao_terceiros_admin' => [
            'gestao_terceiros:fornecedor:*',
            'gestao_terceiros:terceiro:*',
            'gestao_terceiros:cadastro:*',
        ],

        // Perfil padrão do terceiro para acessar o próprio portal
        'gestao_terceiros_portal' => [
            'gestao_terceiros:portal:access',
        ],
    ],
];

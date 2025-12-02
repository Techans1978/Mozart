<?php
return [
    'slug'        => 'system',
    'name'        => 'Gerenciamento do Sistema',
    'area'        => ['back' => true, 'front' => false],
    'version'     => '1.0.0',
    'description' => 'Funções centrais: dashboard, usuários, empresas, permissões.',

    'capabilities' => [
        // Dashboard
        'system:dashboard:view'       => 'Ver dashboard principal',

        // Usuários
        'auth:users:read'             => 'Ver usuários',
        'auth:users:create'           => 'Criar usuários',
        'auth:users:update'           => 'Editar usuários',
        'auth:users:delete'           => 'Excluir usuários',

        // Grupos
        'auth:groups:read'            => 'Ver grupos de usuários',
        'auth:groups:manage'          => 'Gerenciar grupos de usuários',

        // Perfis (perfis lógicos do sistema)
        'auth:profiles:read'          => 'Ver perfis',
        'auth:profiles:manage'        => 'Gerenciar perfis',

        // Níveis de acesso (RBAC / níveis)
        'auth:roles:read'             => 'Ver níveis/perfis de acesso',
        'auth:roles:update'           => 'Editar níveis/perfis de acesso',

        // Auditoria
        'auth:audit:read'             => 'Ver auditoria',
        'auth:audit:export'           => 'Exportar auditoria',

        // Empresas
        'empresas:empresas:read'      => 'Ver empresas',
        'empresas:empresas:create'    => 'Criar empresas',
        'empresas:empresas:update'    => 'Editar empresas',
        'empresas:empresas:delete'    => 'Excluir empresas',
    ],

    'menu' => [
        'back' => [
            [
                'label'    => 'Início',
                'icon'     => 'fa fa-home',
                'url'      => BASE_URL.'/pages/dashboard.php',
                'requires' => ['system:dashboard:view'],
            ],
            [
                'label' => 'Usuários & Acesso',
                'icon'  => 'fa fa-users',
                'children' => [
                    [
                        'label'    => 'Usuários',
                        'url'      => BASE_URL.'/pages/listar_usuarios.php',
                        'requires' => ['auth:users:read'],
                    ],
                    [
                        'label'    => 'Grupos',
                        'url'      => BASE_URL.'/pages/grupos_listar.php',
                        'requires' => ['auth:groups:read'],
                    ],
                    [
                        'label'    => 'Perfis',
                        'url'      => BASE_URL.'/pages/perfis_listar.php',
                        'requires' => ['auth:profiles:read'],
                    ],
                    [
                        'label'    => 'Níveis de Acesso',
                        'url'      => BASE_URL.'/pages/niveis_listar.php',
                        'requires' => ['auth:roles:read'],
                    ],
                    [
                        'label'    => 'Auditoria',
                        'url'      => BASE_URL.'/pages/auditoria_listar.php',
                        'requires' => ['auth:audit:read'],
                    ],
                ],
            ],
            [
                'label' => 'Empresas',
                'icon'  => 'fa fa-building',
                'children' => [
                    [
                        'label'    => 'Empresas e Coligadas',
                        'url'      => BASE_URL.'/pages/empresas_listar.php',
                        'requires' => ['empresas:empresas:read'],
                    ],
                    [
                        'label'    => 'Criar Empresas e Coligadas',
                        // se você tiver um form separado, troca aqui:
                        // 'url' => BASE_URL.'/pages/empresas_form.php',
                        'url'      => BASE_URL.'/pages/empresas_listar.php',
                        'requires' => ['empresas:empresas:create'],
                    ],
                ],
            ],
        ],
        'front' => [],
    ],

    'routes' => [
        // Dashboard
        [ 'path' => '/pages/dashboard.php',          'requires' => ['system:dashboard:view'] ],

        // Usuários & Acesso
        [ 'path' => '/pages/listar_usuarios.php',    'requires' => ['auth:users:read'] ],
        [ 'path' => '/pages/grupos_listar.php',      'requires' => ['auth:groups:read'] ],
        [ 'path' => '/pages/perfis_listar.php',      'requires' => ['auth:profiles:read'] ],
        [ 'path' => '/pages/niveis_listar.php',      'requires' => ['auth:roles:read'] ],
        [ 'path' => '/pages/auditoria_listar.php',   'requires' => ['auth:audit:read'] ],

        // Empresas
        [ 'path' => '/pages/empresas_listar.php',    'requires' => ['empresas:empresas:read'] ],
        // se tiver form separado:
        // [ 'path' => '/pages/empresas_form.php',   'requires' => ['empresas:empresas:create'] ],
    ],

    'role_defaults' => [
        'superadmin'      => ['*'],
        'admin_sistema'   => [
            'system:*',
            'auth:*',
            'empresas:*',
        ],
    ],
];

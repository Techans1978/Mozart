<?php
return [
    'slug'        => 'system',
    'name'        => 'Gerenciamento do Sistema',
    'area'        => ['back' => true, 'front' => false],
    'version'     => '1.0.0',
    'description' => 'Funções centrais: dashboard, usuários, empresas, permissões.',

    'capabilities' => [
        'system:dashboard:view'     => 'Ver dashboard principal',
        'auth:users:read'           => 'Ver usuários',
        'auth:users:create'         => 'Criar usuários',
        'auth:users:update'         => 'Editar usuários',
        'auth:users:delete'         => 'Excluir usuários',
        'auth:roles:read'           => 'Ver níveis/perfis de acesso',
        'auth:roles:update'         => 'Editar níveis/perfis de acesso',
        'auth:audit:read'           => 'Ver auditoria',
        'auth:audit:export'         => 'Exportar auditoria',
        'empresas:empresas:read'    => 'Ver empresas',
        'empresas:empresas:create'  => 'Criar empresas',
        'empresas:empresas:update'  => 'Editar empresas',
        'empresas:empresas:delete'  => 'Excluir empresas',
    ],

    'menu' => [
        'back' => [
            [
                'label' => '<i class="ti ti-home"></i> Início',
                'route' => BASE_URL.'/dashboard.php',
                'requires' => ['system:dashboard:view'],
            ],
            [
                'label' => '<i class="ti ti-users"></i> Usuários & Acesso',
                'children' => [
                    [
                        'label' => 'Usuários',
                        'route' => BASE_URL.'/pages/usuarios_listar.php',
                        'requires' => ['auth:users:read'],
                    ],
                    [
                        'label' => 'Cadastrar Usuário',
                        'route' => BASE_URL.'/pages/usuarios_form.php',
                        'requires' => ['auth:users:create'],
                    ],
                    [
                        'label' => 'Níveis de Acesso',
                        'route' => BASE_URL.'/pages/niveis_form.php',
                        'requires' => ['auth:roles:read'],
                    ],
                    [
                        'label' => 'Auditoria',
                        'route' => BASE_URL.'/pages/auditoria_listar.php',
                        'requires' => ['auth:audit:read'],
                    ],
                ],
            ],
            [
                'label' => '<i class="ti ti-building"></i> Empresas',
                'children' => [
                    [
                        'label' => 'Empresas',
                        'route' => BASE_URL.'/pages/empresas_listar.php',
                        'requires' => ['empresas:empresas:read'],
                    ],
                    [
                        'label' => 'Cadastrar Empresa',
                        'route' => BASE_URL.'/pages/empresas_form.php',
                        'requires' => ['empresas:empresas:create'],
                    ],
                ],
            ],
        ],
        'front' => [],
    ],

    'routes' => [
        [ 'path' => '/dashboard.php',                       'requires' => ['system:dashboard:view'] ],
        [ 'path' => '/pages/usuarios_listar.php',          'requires' => ['auth:users:read'] ],
        [ 'path' => '/pages/usuarios_form.php',            'requires' => ['auth:users:create'] ],
        [ 'path' => '/pages/niveis_form.php',              'requires' => ['auth:roles:read'] ],
        [ 'path' => '/pages/auditoria_listar.php',         'requires' => ['auth:audit:read'] ],
        [ 'path' => '/pages/empresas_listar.php',          'requires' => ['empresas:empresas:read'] ],
        [ 'path' => '/pages/empresas_form.php',            'requires' => ['empresas:empresas:create'] ],
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

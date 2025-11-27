<?php
return [
    'slug'        => 'ativos',
    'name'        => 'Gestão de Ativos',
    'area'        => ['back' => true, 'front' => true],
    'version'     => '1.0.0',
    'description' => 'Cadastro, controle e rastreio de ativos.',

    'capabilities' => [
        'ativos:ativos:read'      => 'Ver ativos',
        'ativos:ativos:create'    => 'Criar ativos',
        'ativos:ativos:update'    => 'Editar ativos',
        'ativos:ativos:delete'    => 'Excluir ativos',
        'ativos:ativos:import'    => 'Importar ativos',
        'ativos:marcas:read'      => 'Ver marcas',
        'ativos:marcas:create'    => 'Criar marcas',
        'ativos:marcas:update'    => 'Editar marcas',
        'ativos:marcas:delete'    => 'Excluir marcas',
        'ativos:modelos:manage'   => 'Gerenciar modelos',
        'ativos:fornecedores:read'=> 'Ver fornecedores de ativos',
    ],

    'menu' => [
        'back' => [
            [
                'label' => '<i class="ti ti-devices"></i> Ativos',
                'children' => [
                    [
                        'label' => 'Listar Ativos',
                        'route' => BASE_URL.'/modules/gestao_ativos/ativos-listar.php',
                        'requires' => ['ativos:ativos:read'],
                    ],
                    [
                        'label' => 'Cadastrar Ativo',
                        'route' => BASE_URL.'/modules/gestao_ativos/ativos-form.php',
                        'requires' => ['ativos:ativos:create'],
                    ],
                    [
                        'label' => 'Importar Ativos',
                        'route' => BASE_URL.'/modules/gestao_ativos/ativos-importar.php',
                        'requires' => ['ativos:ativos:import'],
                    ],
                    [ 'divider' => true ],
                    [
                        'label' => 'Cadastros',
                        'children' => [
                            [
                                'label' => 'Marcas',
                                'route' => BASE_URL.'/modules/gestao_ativos/cadastros/marcas.php',
                                'requires' => ['ativos:marcas:read'],
                            ],
                            [
                                'label' => 'Modelos',
                                'route' => BASE_URL.'/modules/gestao_ativos/cadastros/modelos.php',
                                'requires' => ['ativos:modelos:manage'],
                            ],
                            [
                                'label' => 'Fornecedores',
                                'route' => BASE_URL.'/modules/gestao_ativos/cadastros/fornecedores.php',
                                'requires' => ['ativos:fornecedores:read'],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'front' => [
            [
                'label' => '<i class="ti ti-qrcode"></i> Portal do Ativo',
                'children' => [
                    [
                        'label' => 'Meus Ativos',
                        'route' => BASE_URL.'/modules/gestao_ativos/portal/meus_ativos.php',
                        'requires' => ['ativos:ativos:read'],
                    ],
                ],
            ],
        ],
    ],

    'routes' => [
        [ 'path' => '/modules/gestao_ativos/ativos-listar.php',             'requires' => ['ativos:ativos:read'] ],
        [ 'path' => '/modules/gestao_ativos/ativos-form.php',               'requires' => ['ativos:ativos:create'] ],
        [ 'path' => '/modules/gestao_ativos/ativos-importar.php',           'requires' => ['ativos:ativos:import'] ],
        [ 'path' => '/modules/gestao_ativos/cadastros/marcas.php',          'requires' => ['ativos:marcas:read'] ],
        [ 'path' => '/modules/gestao_ativos/cadastros/modelos.php',         'requires' => ['ativos:modelos:manage'] ],
        [ 'path' => '/modules/gestao_ativos/cadastros/fornecedores.php',    'requires' => ['ativos:fornecedores:read'] ],
    ],

    'role_defaults' => [
        'superadmin'      => ['*'],
        'admin_ativos'    => ['ativos:*'],
        'gestor_ativos'   => [
            'ativos:ativos:*',
            'ativos:marcas:*',
            'ativos:modelos:manage',
            'ativos:fornecedores:read',
        ],
        'tecnico_ativos'  => [
            'ativos:ativos:read',
            'ativos:ativos:create',
            'ativos:ativos:update',
            'ativos:ativos:import',
        ],
    ],
];

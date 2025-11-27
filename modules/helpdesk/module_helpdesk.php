<?php
return [
    'slug'        => 'helpdesk',
    'name'        => 'Help Desk',
    'area'        => ['back' => true, 'front' => true],
    'version'     => '1.0.0',
    'description' => 'Gestão de chamados, filas, SLA e formulários.',

    'capabilities' => [
        'helpdesk:tickets:read'       => 'Ver chamados',
        'helpdesk:tickets:create'     => 'Abrir chamados',
        'helpdesk:tickets:update'     => 'Editar chamados',
        'helpdesk:tickets:delete'     => 'Excluir chamados',
        'helpdesk:tickets:assign'     => 'Assumir/atribuir chamados',
        'helpdesk:tickets:close'      => 'Fechar chamados',
        'helpdesk:inbox:agent'        => 'Ver caixa de trabalho (agente)',
        'helpdesk:categorias:manage'  => 'Gerenciar categorias',
        'helpdesk:formularios:manage' => 'Gerenciar formulários',
        'helpdesk:sla:manage'         => 'Gerenciar SLA e regras',
    ],

    'menu' => [
        'back' => [
            [
                'label' => '<i class="fa fa-phone"></i> Help Desk',
                'children' => [
                    [
                        'label' => 'Dashboard',
                        'route' => BASE_URL.'/modules/helpdesk/pages/dashboard.php',
                        'requires' => ['helpdesk:tickets:read'],
                    ],
                    [
                        'label' => 'Listar Chamados',
                        'route' => BASE_URL.'/modules/helpdesk/pages/tickets_listar.php',
                        'requires' => ['helpdesk:tickets:read'],
                    ],
                    [
                        'label' => 'Abrir Chamado',
                        'route' => BASE_URL.'/modules/helpdesk/pages/ticket_novo.php',
                        'requires' => ['helpdesk:tickets:create'],
                    ],
                    [
                        'label' => 'Minha Caixa (Agente)',
                        'route' => BASE_URL.'/modules/helpdesk/pages/inbox.php',
                        'requires' => ['helpdesk:inbox:agent'],
                    ],
                    [ 'divider' => true ],
                    [
                        'label' => 'Cadastros',
                        'children' => [
                            [
                                'label' => 'Categorias',
                                'route' => BASE_URL.'/modules/helpdesk/pages/admin/categorias.php',
                                'requires' => ['helpdesk:categorias:manage'],
                            ],
                            [
                                'label' => 'Formulários',
                                'route' => BASE_URL.'/modules/helpdesk/pages/admin/formularios.php',
                                'requires' => ['helpdesk:formularios:manage'],
                            ],
                            [
                                'label' => 'SLA',
                                'route' => BASE_URL.'/modules/helpdesk/pages/admin/sla.php',
                                'requires' => ['helpdesk:sla:manage'],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'front' => [
            [
                'label' => '<i class="fa fa-ticket"></i> Meus Chamados',
                'children' => [
                    [
                        'label' => 'Dashboard',
                        'route' => BASE_URL.'/modules/helpdesk/pages/dashboard.php',
                        'requires' => ['helpdesk:tickets:read'],
                    ],
                    [
                        'label' => 'Listar Chamados',
                        'route' => BASE_URL.'/modules/helpdesk/pages/tickets_listar.php',
                        'requires' => ['helpdesk:tickets:read'],
                    ],
                    [
                        'label' => 'Abrir Chamado',
                        'route' => BASE_URL.'/modules/helpdesk/pages/ticket_novo.php',
                        'requires' => ['helpdesk:tickets:create'],
                    ],
                    [
                        'label' => 'Minha Caixa (Usuário)',
                        'route' => BASE_URL.'/modules/helpdesk/pages/inbox.php',
                        'requires' => ['helpdesk:tickets:read'],
                    ],
                ],
            ],
        ],
    ],

    'routes' => [
        [ 'path' => '/modules/helpdesk/pages/dashboard.php',          'requires' => ['helpdesk:tickets:read'] ],
        [ 'path' => '/modules/helpdesk/pages/tickets_listar.php',     'requires' => ['helpdesk:tickets:read'] ],
        [ 'path' => '/modules/helpdesk/pages/ticket_novo.php',        'requires' => ['helpdesk:tickets:create'] ],
        [ 'path' => '/modules/helpdesk/pages/inbox.php',              'requires' => ['helpdesk:inbox:agent','helpdesk:tickets:read'] ],
        [ 'path' => '/modules/helpdesk/pages/admin/categorias.php',   'requires' => ['helpdesk:categorias:manage'] ],
        [ 'path' => '/modules/helpdesk/pages/admin/formularios.php',  'requires' => ['helpdesk:formularios:manage'] ],
        [ 'path' => '/modules/helpdesk/pages/admin/sla.php',          'requires' => ['helpdesk:sla:manage'] ],
    ],

    'role_defaults' => [
        'superadmin'        => ['*'],
        'admin_helpdesk'    => ['helpdesk:*'],
        'tecnico_helpdesk'  => [
            'helpdesk:tickets:read',
            'helpdesk:tickets:create',
            'helpdesk:tickets:update',
            'helpdesk:tickets:assign',
            'helpdesk:tickets:close',
            'helpdesk:inbox:agent',
        ],
        'colaborador'       => [
            'helpdesk:tickets:read',
            'helpdesk:tickets:create',
        ],
    ],
];

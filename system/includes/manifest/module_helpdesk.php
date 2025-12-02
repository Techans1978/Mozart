<?php
return [
    'slug'        => 'helpdesk',
    'name'        => 'Help Desk',
    'area'        => ['back' => true, 'front' => true],
    'version'     => '1.0.0',
    'description' => 'Gestão de chamados, filas, SLA, relatórios e formulários.',

    // === Capabilities ===
    'capabilities' => [
        // Chamados
        'helpdesk:tickets:read'        => 'Ver chamados',
        'helpdesk:tickets:create'      => 'Abrir chamados',
        'helpdesk:tickets:update'      => 'Editar chamados',
        'helpdesk:tickets:delete'      => 'Excluir chamados',
        'helpdesk:tickets:assign'      => 'Assumir/atribuir chamados',
        'helpdesk:tickets:close'       => 'Fechar chamados',

        // Caixa de trabalho
        'helpdesk:inbox:agent'         => 'Ver caixa de trabalho (agente)',

        // Relatórios
        'helpdesk:reports:basic'       => 'Ver relatórios básicos',
        'helpdesk:reports:pro'         => 'Ver relatórios avançados',

        // Cadastros
        'helpdesk:categorias:manage'   => 'Gerenciar categorias',
        'helpdesk:servicos:manage'     => 'Gerenciar serviços',
        'helpdesk:status:manage'       => 'Gerenciar tipos/status',
        'helpdesk:entidades:manage'    => 'Gerenciar entidades/lojas',
        'helpdesk:origens:manage'      => 'Gerenciar origens',
        'helpdesk:tecnicos:manage'     => 'Gerenciar técnicos e filas',
        'helpdesk:formularios:manage'  => 'Gerenciar formulários',
        'helpdesk:templates:manage'    => 'Gerenciar templates de e-mail',
        'helpdesk:macros:manage'       => 'Gerenciar macros',

        // SLA & Regras
        'helpdesk:sla:manage'          => 'Gerenciar SLA',
        'helpdesk:regras:manage'       => 'Gerenciar regras de automação',
        'helpdesk:oncall:manage'       => 'Gerenciar on-call / plantões',

        // Segurança (escopo Help Desk)
        'helpdesk:rbac:manage'         => 'Gerenciar RBAC do Help Desk',
        'helpdesk:audit:read'          => 'Ver auditoria do Help Desk',
    ],

    // === MENU lateral (backend) ===
    'menu' => [
        'back' => [
            [
                'label' => 'Help Desk',
                'icon'  => 'fa fa-phone',
                'children' => [
                    [
                        'label'    => 'Dashboard',
                        'url'      => BASE_URL . '/modules/helpdesk/pages/dashboard.php',
                        'requires' => ['helpdesk:tickets:read'],
                    ],
                    [
                        'label'    => 'Listar Chamados',
                        'url'      => BASE_URL . '/modules/helpdesk/pages/tickets_listar.php',
                        'requires' => ['helpdesk:tickets:read'],
                    ],
                    [
                        'label'    => 'Abrir Chamado',
                        'url'      => BASE_URL . '/modules/helpdesk/pages/ticket_novo.php',
                        'requires' => ['helpdesk:tickets:create'],
                    ],
                    [
                        'label'    => 'Minha Caixa (Agente)',
                        'url'      => BASE_URL . '/modules/helpdesk/pages/inbox.php',
                        'requires' => ['helpdesk:inbox:agent'],
                    ],

                    [ 'divider' => true ],

                    [
                        'label'    => 'Relatórios & Agendamentos',
                        'url'      => BASE_URL . '/modules/helpdesk/pages/reports_one.php',
                        'requires' => ['helpdesk:reports:basic'],
                    ],
                    [
                        'label'    => 'Relatórios & Agendamentos (Pro)',
                        'url'      => BASE_URL . '/modules/helpdesk/pages/reports_pro.php',
                        'requires' => ['helpdesk:reports:pro'],
                    ],

                    [ 'divider' => true ],

                    // Cadastros
                    [
                        'label'    => 'Cadastros',
                        'children' => [
                            [
                                'label'    => 'Categorias',
                                'url'      => BASE_URL . '/modules/helpdesk/pages/admin/categorias.php',
                                'requires' => ['helpdesk:categorias:manage'],
                            ],
                            [
                                'label'    => 'Serviços',
                                'url'      => BASE_URL . '/modules/helpdesk/pages/admin/servicos.php',
                                'requires' => ['helpdesk:servicos:manage'],
                            ],
                            [
                                'label'    => 'Tipos / Status',
                                'url'      => BASE_URL . '/modules/helpdesk/pages/admin/tipos_status.php',
                                'requires' => ['helpdesk:status:manage'],
                            ],
                            [
                                'label'    => 'Entidades / Lojas',
                                'url'      => BASE_URL . '/modules/helpdesk/pages/admin/entidades.php',
                                'requires' => ['helpdesk:entidades:manage'],
                            ],
                            [
                                'label'    => 'Origens',
                                'url'      => BASE_URL . '/modules/helpdesk/pages/admin/origens.php',
                                'requires' => ['helpdesk:origens:manage'],
                            ],
                            [
                                'label'    => 'Técnicos & Filas',
                                'url'      => BASE_URL . '/modules/helpdesk/pages/admin/tecnicos_filas.php',
                                'requires' => ['helpdesk:tecnicos:manage'],
                            ],
                            [
                                'label'    => 'Formulários',
                                'url'      => BASE_URL . '/modules/helpdesk/pages/admin/formularios.php',
                                'requires' => ['helpdesk:formularios:manage'],
                            ],
                            [
                                'label'    => 'Templates de E-mail',
                                'url'      => BASE_URL . '/modules/helpdesk/pages/admin/templates_email.php',
                                'requires' => ['helpdesk:templates:manage'],
                            ],
                            [
                                'label'    => 'Macros',
                                'url'      => BASE_URL . '/modules/helpdesk/pages/admin/macros.php',
                                'requires' => ['helpdesk:macros:manage'],
                            ],
                        ],
                    ],

                    // SLA & Regras
                    [
                        'label'    => 'SLA & Regras',
                        'children' => [
                            [
                                'label'    => 'SLA',
                                'url'      => BASE_URL . '/modules/helpdesk/pages/admin/sla.php',
                                'requires' => ['helpdesk:sla:manage'],
                            ],
                            [
                                'label'    => 'Regras de Automação',
                                'url'      => BASE_URL . '/modules/helpdesk/pages/admin/regras.php',
                                'requires' => ['helpdesk:regras:manage'],
                            ],
                            [
                                'label'    => 'On-call / Plantões',
                                'url'      => BASE_URL . '/modules/helpdesk/pages/admin/oncall.php',
                                'requires' => ['helpdesk:oncall:manage'],
                            ],
                        ],
                    ],

                    // Segurança
                    [
                        'label'    => 'Segurança',
                        'children' => [
                            [
                                'label'    => 'RBAC',
                                'url'      => BASE_URL . '/modules/helpdesk/pages/admin/rbac.php',
                                'requires' => ['helpdesk:rbac:manage'],
                            ],
                            [
                                'label'    => 'Auditoria',
                                'url'      => BASE_URL . '/modules/helpdesk/pages/admin/auditoria.php',
                                'requires' => ['helpdesk:audit:read'],
                            ],
                        ],
                    ],
                ],
            ],
        ],

        // Front (portal do usuário) – pode ser refinado depois
        'front' => [
            [
                'label' => 'Meus Chamados',
                'icon'  => 'fa fa-ticket',
                'children' => [
                    [
                        'label'    => 'Dashboard',
                        'url'      => BASE_URL . '/modules/helpdesk/pages/dashboard.php',
                        'requires' => ['helpdesk:tickets:read'],
                    ],
                    [
                        'label'    => 'Listar Chamados',
                        'url'      => BASE_URL . '/modules/helpdesk/pages/tickets_listar.php',
                        'requires' => ['helpdesk:tickets:read'],
                    ],
                    [
                        'label'    => 'Abrir Chamado',
                        'url'      => BASE_URL . '/modules/helpdesk/pages/ticket_novo.php',
                        'requires' => ['helpdesk:tickets:create'],
                    ],
                    [
                        'label'    => 'Minha Caixa (Usuário)',
                        'url'      => BASE_URL . '/modules/helpdesk/pages/inbox.php',
                        'requires' => ['helpdesk:tickets:read'],
                    ],
                ],
            ],
        ],
    ],

    // === Rotas para RBAC (SCRIPT_NAME) ===
    'routes' => [
        // principais
        [ 'path' => '/modules/helpdesk/pages/dashboard.php',          'requires' => ['helpdesk:tickets:read'] ],
        [ 'path' => '/modules/helpdesk/pages/tickets_listar.php',     'requires' => ['helpdesk:tickets:read'] ],
        [ 'path' => '/modules/helpdesk/pages/ticket_novo.php',        'requires' => ['helpdesk:tickets:create'] ],
        [ 'path' => '/modules/helpdesk/pages/inbox.php',              'requires' => ['helpdesk:inbox:agent', 'helpdesk:tickets:read'] ],

        // relatórios
        [ 'path' => '/modules/helpdesk/pages/reports_one.php',        'requires' => ['helpdesk:reports:basic'] ],
        [ 'path' => '/modules/helpdesk/pages/reports_pro.php',        'requires' => ['helpdesk:reports:pro'] ],

        // cadastros
        [ 'path' => '/modules/helpdesk/pages/admin/categorias.php',   'requires' => ['helpdesk:categorias:manage'] ],
        [ 'path' => '/modules/helpdesk/pages/admin/servicos.php',     'requires' => ['helpdesk:servicos:manage'] ],
        [ 'path' => '/modules/helpdesk/pages/admin/tipos_status.php', 'requires' => ['helpdesk:status:manage'] ],
        [ 'path' => '/modules/helpdesk/pages/admin/entidades.php',    'requires' => ['helpdesk:entidades:manage'] ],
        [ 'path' => '/modules/helpdesk/pages/admin/origens.php',      'requires' => ['helpdesk:origens:manage'] ],
        [ 'path' => '/modules/helpdesk/pages/admin/tecnicos_filas.php','requires'=> ['helpdesk:tecnicos:manage'] ],
        [ 'path' => '/modules/helpdesk/pages/admin/formularios.php',  'requires' => ['helpdesk:formularios:manage'] ],
        [ 'path' => '/modules/helpdesk/pages/admin/templates_email.php','requires'=> ['helpdesk:templates:manage'] ],
        [ 'path' => '/modules/helpdesk/pages/admin/macros.php',       'requires' => ['helpdesk:macros:manage'] ],

        // SLA & Regras
        [ 'path' => '/modules/helpdesk/pages/admin/sla.php',          'requires' => ['helpdesk:sla:manage'] ],
        [ 'path' => '/modules/helpdesk/pages/admin/regras.php',       'requires' => ['helpdesk:regras:manage'] ],
        [ 'path' => '/modules/helpdesk/pages/admin/oncall.php',       'requires' => ['helpdesk:oncall:manage'] ],

        // Segurança
        [ 'path' => '/modules/helpdesk/pages/admin/rbac.php',         'requires' => ['helpdesk:rbac:manage'] ],
        [ 'path' => '/modules/helpdesk/pages/admin/auditoria.php',    'requires' => ['helpdesk:audit:read'] ],
    ],

    // === Perfis padrão ===
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

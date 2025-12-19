<?php
return [
    'slug'        => 'wpp_chat',
    'name'        => 'WPP Chat',
    'area'        => ['back' => true, 'front' => false],
    'version'     => '1.0.0',
    'description' => 'Integração com WhatsApp, instâncias, campanhas e chat.',

    // === Capabilities ===
    'capabilities' => [
        // Instâncias
        'whatsapp:instances:read'      => 'Ver instâncias',
        'whatsapp:instances:manage'    => 'Criar/editar instâncias',

        // Mensagens / Conversas
        'whatsapp:messages:send'       => 'Enviar mensagens manuais',
        'whatsapp:messages:read'       => 'Ver histórico de mensagens',

        // Campanhas
        'whatsapp:campaigns:read'      => 'Ver campanhas',
        'whatsapp:campaigns:manage'    => 'Criar/editar campanhas',

        // Webhook / Templates
        'whatsapp:webhook:configure'   => 'Configurar webhooks',
        'whatsapp:templates:manage'    => 'Gerenciar templates',
    ],

    // === MENU lateral (backend) ===
    'menu' => [
        'back' => [
            [
                'label' => 'WPP Chat',
                'icon'  => 'fa fa-whatsapp',
                'children' => [
                    [
                        'label'    => 'Dashboard',
                        'url'      => BASE_URL . '/modules/wpp_chat/dashboard.php',
                        'requires' => ['whatsapp:messages:read'],
                    ],
                    [
                        'label'    => 'Conversas',
                        'url'      => BASE_URL . '/modules/wpp_chat/pages/conversas.php',
                        'requires' => ['whatsapp:messages:read'],
                    ],
                    [
                        'label'    => 'Clientes',
                        'url'      => BASE_URL . '/modules/wpp_chat/pages/clientes-list.php',
                        'requires' => ['whatsapp:messages:read'],
                    ],
                    [
                        'label'    => 'Campanhas',
                        'url'      => BASE_URL . '/modules/wpp_chat/pages/campanhas-list.php',
                        'requires' => ['whatsapp:campaigns:read'],
                    ],
                    [
                        'label'    => 'Scripts',
                        'url'      => BASE_URL . '/modules/wpp_chat/pages/scripts-list.php',
                        'requires' => ['whatsapp:templates:manage'],
                    ],
                    [
                        'label'    => 'Instâncias',
                        'url'      => BASE_URL . '/modules/wpp_chat/instancias.php',
                        'requires' => ['whatsapp:instances:read'],
                    ],
                    [
                        'label'    => 'Configurações',
                        'url'      => BASE_URL . '/modules/wpp_chat/configuracoes.php',
                        'requires' => ['whatsapp:webhook:configure'],
                    ],
                ],
            ],
        ],
        'front' => [],
    ],

    // === Rotas para RBAC (usa SCRIPT_NAME, sem BASE_URL) ===
    'routes' => [
        [ 'path' => '/modules/wpp_chat/dashboard.php',            'requires' => ['whatsapp:messages:read'] ],
        [ 'path' => '/modules/wpp_chat/pages/conversas.php',      'requires' => ['whatsapp:messages:read'] ],
        [ 'path' => '/modules/wpp_chat/pages/clientes-list.php',  'requires' => ['whatsapp:messages:read'] ],
        [ 'path' => '/modules/wpp_chat/pages/campanhas-list.php', 'requires' => ['whatsapp:campaigns:read'] ],
        [ 'path' => '/modules/wpp_chat/pages/scripts-list.php',   'requires' => ['whatsapp:templates:manage'] ],
        [ 'path' => '/modules/wpp_chat/instancias.php',           'requires' => ['whatsapp:instances:read'] ],
        [ 'path' => '/modules/wpp_chat/configuracoes.php',        'requires' => ['whatsapp:webhook:configure'] ],
    ],

    // === Perfis padrão ===
    'role_defaults' => [
        'superadmin'   => ['*'],
        'admin_wpp'    => [
            'whatsapp:*',
        ],
        'operador_wpp' => [
            'whatsapp:messages:send',
            'whatsapp:messages:read',
            'whatsapp:instances:read',
            'whatsapp:campaigns:read',
        ],
    ],
];

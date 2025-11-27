<?php
return [
    'slug'        => 'wpp_chat',
    'name'        => 'WPP Chat',
    'area'        => ['back' => true, 'front' => false],
    'version'     => '1.0.0',
    'description' => 'Integração com WhatsApp, instâncias, campanhas e chat.',

    'capabilities' => [
        'whatsapp:instances:read'    => 'Ver instâncias',
        'whatsapp:instances:manage'  => 'Criar/editar instâncias',
        'whatsapp:messages:send'     => 'Enviar mensagens manuais',
        'whatsapp:messages:read'     => 'Ver histórico de mensagens',
        'whatsapp:campaigns:read'    => 'Ver campanhas',
        'whatsapp:campaigns:manage'  => 'Criar/editar campanhas',
        'whatsapp:webhook:configure' => 'Configurar webhooks',
        'whatsapp:templates:manage'  => 'Gerenciar templates',
    ],

    'menu' => [
        'back' => [
            [
                'label' => '<i class="ti ti-brand-whatsapp"></i> WPP Chat',
                'children' => [
                    [
                        'label' => 'Dashboard',
                        'route' => BASE_URL.'/modules/wpp_chat/dashboard.php',
                        'requires' => ['whatsapp:messages:read'],
                    ],
                    [
                        'label' => 'Instâncias',
                        'route' => BASE_URL.'/modules/wpp_chat/instancias_listar.php',
                        'requires' => ['whatsapp:instances:read'],
                    ],
                    [
                        'label' => 'Nova Instância',
                        'route' => BASE_URL.'/modules/wpp_chat/instancia-form.php',
                        'requires' => ['whatsapp:instances:manage'],
                    ],
                    [
                        'label' => 'QR Code / Sessão',
                        'route' => BASE_URL.'/modules/wpp_chat/instancia-qr.php',
                        'requires' => ['whatsapp:instances:manage'],
                    ],
                    [ 'divider' => true ],
                    [
                        'label' => 'Campanhas',
                        'children' => [
                            [
                                'label' => 'Listar Campanhas',
                                'route' => BASE_URL.'/modules/wpp_chat/campanhas_listar.php',
                                'requires' => ['whatsapp:campaigns:read'],
                            ],
                            [
                                'label' => 'Nova Campanha',
                                'route' => BASE_URL.'/modules/wpp_chat/campanha-form.php',
                                'requires' => ['whatsapp:campaigns:manage'],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'front' => [],
    ],

    'routes' => [
        [ 'path' => '/modules/wpp_chat/dashboard.php',           'requires' => ['whatsapp:messages:read'] ],
        [ 'path' => '/modules/wpp_chat/instancias_listar.php',   'requires' => ['whatsapp:instances:read'] ],
        [ 'path' => '/modules/wpp_chat/instancia-form.php',      'requires' => ['whatsapp:instances:manage'] ],
        [ 'path' => '/modules/wpp_chat/instancia-qr.php',        'requires' => ['whatsapp:instances:manage'] ],
        [ 'path' => '/modules/wpp_chat/campanhas_listar.php',    'requires' => ['whatsapp:campaigns:read'] ],
        [ 'path' => '/modules/wpp_chat/campanha-form.php',       'requires' => ['whatsapp:campaigns:manage'] ],
    ],

    'role_defaults' => [
        'superadmin'        => ['*'],
        'admin_wpp'         => ['whatsapp:*'],
        'operador_wpp'      => [
            'whatsapp:messages:send',
            'whatsapp:messages:read',
            'whatsapp:instances:read',
            'whatsapp:campaigns:read',
        ],
    ],
];

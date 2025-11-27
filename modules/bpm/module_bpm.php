<?php
return [
    'slug'        => 'bpm',
    'name'        => 'BPM / Orquestração de Processos',
    'area'        => ['back' => true, 'front' => false],
    'version'     => '1.0.0',
    'description' => 'Designer BPMN, deploy de processos, instâncias e formulários.',

    'capabilities' => [
        'bpm:processos:read'    => 'Ver processos',
        'bpm:processos:design'  => 'Desenhar processos',
        'bpm:processos:deploy'  => 'Publicar processos',
        'bpm:processos:update'  => 'Editar metadados de processos',
        'bpm:instancias:read'   => 'Ver instâncias',
        'bpm:instancias:operate'=> 'Operar instâncias (avançar, cancelar)',
        'bpm:forms:manage'      => 'Gerenciar formulários BPM',
        'bpm:connectors:manage' => 'Gerenciar conectores/API',
    ],

    'menu' => [
        'back' => [
            [
                'label' => '<i class="ti ti-flowchart"></i> BPM',
                'children' => [
                    [
                        'label' => 'Processos',
                        'route' => BASE_URL.'/modules/bpm/processos-listar.php',
                        'requires' => ['bpm:processos:read'],
                    ],
                    [
                        'label' => 'Designer BPMN',
                        'route' => BASE_URL.'/modules/bpm/processo-modeler.php',
                        'requires' => ['bpm:processos:design'],
                    ],
                    [
                        'label' => 'Instâncias',
                        'route' => BASE_URL.'/modules/bpm/instancias-listar.php',
                        'requires' => ['bpm:instancias:read'],
                    ],
                    [ 'divider' => true ],
                    [
                        'label' => 'Formulários',
                        'route' => BASE_URL.'/modules/bpm/forms-listar.php',
                        'requires' => ['bpm:forms:manage'],
                    ],
                    [
                        'label' => 'Conectores',
                        'route' => BASE_URL.'/modules/bpm/conectores-listar.php',
                        'requires' => ['bpm:connectors:manage'],
                    ],
                ],
            ],
        ],
        'front' => [],
    ],

    'routes' => [
        [ 'path' => '/modules/bpm/processos-listar.php',     'requires' => ['bpm:processos:read'] ],
        [ 'path' => '/modules/bpm/processo-modeler.php',     'requires' => ['bpm:processos:design'] ],
        [ 'path' => '/modules/bpm/instancias-listar.php',    'requires' => ['bpm:instancias:read'] ],
        [ 'path' => '/modules/bpm/forms-listar.php',         'requires' => ['bpm:forms:manage'] ],
        [ 'path' => '/modules/bpm/conectores-listar.php',    'requires' => ['bpm:connectors:manage'] ],
    ],

    'role_defaults' => [
        'superadmin'      => ['*'],
        'admin_bpm'       => ['bpm:*'],
        'designer_bpm'    => [
            'bpm:processos:read',
            'bpm:processos:design',
            'bpm:processos:deploy',
            'bpm:forms:manage',
            'bpm:connectors:manage',
        ],
        'operador_bpm'    => [
            'bpm:instancias:read',
            'bpm:instancias:operate',
        ],
    ],
];

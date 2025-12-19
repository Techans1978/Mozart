<?php
// system/includes/manifest/module_08_form.php
// Manifesto do módulo de Formulários (Forms.js) do Mozart

return [
    'slug'        => 'forms',
    'name'        => 'Formulários',
    'area'        => ['back' => true, 'front' => false],
    'version'     => '1.0.0',
    'description' => 'Construtor de formulários (Forms.js) reutilizáveis para BPM, Helpdesk e outros módulos.',

    // === Capabilities ===
    'capabilities' => [
        // Formulários
        'forms:read'               => 'Ver formulários',
        'forms:design'             => 'Criar/editar formulários no designer',
        'forms:manage'             => 'Ativar, clonar e excluir formulários',

        // Categorias de formulários
        'forms:categories:manage'  => 'Gerenciar categorias de formulários',
    ],

    // === MENU lateral (backend) ===
    'menu' => [
        'back' => [
            [
                'label' => 'Formulários',
                'icon'  => 'fa fa-wpforms', // ajuste pro ícone que preferir (font-awesome/tabler)
                'children' => [
                    [
                        'label'    => 'Listar Formulários',
                        'url'      => BASE_URL . '/modules/forms/forms_listar.php',
                        'requires' => ['forms:read'],
                    ],
                    [
                        'label'    => 'Novo Formulário',
                        'url'      => BASE_URL . '/modules/forms/forms_designer.php',
                        'requires' => ['forms:design'],
                    ],
                    [
                        'label'    => 'Categorias de Formulário',
                        'url'      => BASE_URL . '/modules/forms/categorias_form_listar.php',
                        'requires' => ['forms:categories:manage'],
                    ],
                ],
            ],
        ],
        'front' => [],
    ],

    // === Rotas para RBAC (usa SCRIPT_NAME, sem BASE_URL) ===
    'routes' => [
        [ 'path' => '/modules/forms/forms_listar.php',            'requires' => ['forms:read'] ],
        [ 'path' => '/modules/forms/forms_designer.php',          'requires' => ['forms:design'] ],
        [ 'path' => '/modules/forms/categorias_form_listar.php',  'requires' => ['forms:categories:manage'] ],
    ],

    // === Perfis padrão (role_defaults) ===
    'role_defaults' => [
        // Superadmin do sistema: acesso total
        'superadmin'      => ['*'],

        // Admin de formulários: tudo relacionado a forms
        'admin_forms'     => ['forms:*'],

        // Designer de formulários: cria/edita/organiza
        'designer_forms'  => [
            'forms:read',
            'forms:design',
            'forms:manage',
            'forms:categories:manage',
        ],

        // Operador que só precisa visualizar
        'operador_forms'  => [
            'forms:read',
        ],
    ],
];

<?php
return [
  'slug' => 'datasets',
  'name' => 'Datasets',
  'area' => ['back'=>true,'front'=>false],
  'version' => '1.0.0',
  'description' => 'Datasets: config + connector + mapping + transform opcional.',

  'capabilities' => [
    'ds:library:read'      => 'Ver biblioteca de datasets',
    'ds:dataset:manage'    => 'Criar/editar datasets',
    'ds:dataset:publish'   => 'Publicar datasets',
    'ds:versions:read'     => 'Ver versões',
    'ds:testcases:manage'  => 'Gerenciar testcases',
    'ds:run'               => 'Executar dataset (API)',
    'ds:health:read' => 'Ver health/status do módulo Datasets',
  ],

  'menu' => [
    'back' => [[
      'label' => 'Datasets',
      'icon'  => 'fa fa-database',
      'children' => [
        ['label'=>'Listar', 'url'=>BASE_URL.'/modules/datasets/ds_list.php', 'requires'=>['ds:library:read']],
        ['label'=>'Novo',   'url'=>BASE_URL.'/modules/datasets/ds_editor.php', 'requires'=>['ds:dataset:manage']],
        ['label'=>'Runner', 'url'=>BASE_URL.'/modules/datasets/ds_runner.php', 'requires'=>['ds:run']],
      ]
    ]],
    'front' => [],
  ],

  'routes' => [
    ['path'=>'/modules/datasets/index.php',     'requires'=>['ds:library:read']],
    ['path'=>'/modules/datasets/ds_list.php',   'requires'=>['ds:library:read']],
    ['path'=>'/modules/datasets/ds_editor.php', 'requires'=>['ds:dataset:manage']],
    ['path'=>'/modules/datasets/ds_runner.php', 'requires'=>['ds:run']],
    ['path'=>'/modules/datasets/ds_versions.php','requires'=>['ds:versions:read']],

    ['path'=>'/modules/datasets/api/run.php',           'requires'=>['ds:run']],
    ['path'=>'/modules/datasets/api/datasets_list.php', 'requires'=>['ds:library:read']],
    ['path'=>'/modules/datasets/api/dataset_get.php',   'requires'=>['ds:library:read']],
    ['path'=>'/modules/datasets/api/dataset_save.php',  'requires'=>['ds:dataset:manage']],
    ['path'=>'/modules/datasets/api/dataset_publish.php','requires'=>['ds:dataset:publish']],
    ['path'=>'/modules/datasets/api/dataset_toggle.php','requires'=>['ds:dataset:manage']],
    ['path'=>'/modules/datasets/api/dataset_delete.php','requires'=>['ds:dataset:manage']],
    ['path'=>'/modules/datasets/health.php', 'requires'=>['ds:health:read']],

  ],

  'role_defaults' => [
    'superadmin' => ['*'],
    'admin_ds' => ['ds:*'],
    'gestor_ds' => [
      'ds:library:read','ds:dataset:manage','ds:dataset:publish',
      'ds:versions:read','ds:testcases:manage','ds:run'
    ],
    'leitor_ds' => ['ds:library:read','ds:versions:read'],
    'runner_ds' => ['ds:run'],
  ],
];

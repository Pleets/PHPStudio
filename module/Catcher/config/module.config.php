<?php

return [
    'router' => [
        'routes' => [
            'Catcher' => [
                'module' => 'Catcher',
                'controller' => 'Index',
                'view' => 'index'
            ]
        ],
    ],
    'view_manager' => [
        'template_map' => [
            'default' => __DIR__ . '/../view/layout/layout.phtml',
            'blank'   => __DIR__ . '/../view/layout/blank.phtml',
        ],
        'view_map' => [
            'validation' => __DIR__ . '/../view/error/validation.phtml',
        ],
    ],
];

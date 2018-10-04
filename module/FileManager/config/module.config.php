<?php

return [
    'router' => [
        'routes' => [
        	'FileManager' => [
        		'module' => 'FileManager',
        		'controller' => '',
        		'view' => ''
        	]
        ],
    ],
    'view_manager' => [
        'template_map' => [
            'default'	=> __DIR__ . '/../../Workarea/view/layout/workarea.phtml',
        ],
    ],
    'view_manager' => [
        'template_map' => [
            'default'   => __DIR__ . '/../../Workarea/view/layout/workarea.phtml',
            'blank'     => __DIR__ . '/../../Auth/view/layout/blank.phtml'
        ],
        'view_map' => [
            'validation' => __DIR__ . '/../../Auth/view/error/validation.phtml',
        ],
    ],
];
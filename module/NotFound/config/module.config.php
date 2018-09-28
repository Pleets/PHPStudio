<?php

return [
    'router' => [
        'routes' => [
        	'NotFound' => [
        		'module' => 'NotFound',
        		'controller' => '',
        		'view' => ''
        	]
        ],
    ],
    'view_manager' => [
        'template_map' => [
            'HTTP404' => __DIR__ . '/../view/layout/404.phtml',
            'blank'   => __DIR__ . '/../view/layout/blank.phtml',
        ],
    ],
];
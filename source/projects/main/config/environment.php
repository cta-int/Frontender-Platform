<?php
/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

return [
    /* Cache */
    'caching' => 1,
    'cachetime' => 1800, // In seconds

    /* Debug */
    'debug' => 0,
    'translation_debug' => 0,

    /* Maintanance */
    'offline' => 0,

    /* Slim settings */
    'determineRouteBeforeAppMiddleware' => true,
    'request' => [
        'logging' => false
    ],

    /* ID separator */
    'id_separator' => '-sid0',

    'project' => [
        'path' => ROOT_PATH . '/project'
    ],

    'core' => [
        'path' => ROOT_PATH . '/core'
    ],

    'template' => [
        'path' => ROOT_PATH . '/project/templates',
        'cache' => [
            'path' => ROOT_PATH . '/cache/twig'
        ],
        'debug' => true,
        'auto_reload' => true
    ],
    'fem_host' => getenv('FEM_HOST') ?: 'http://manager.getfrontender.com',
    'mongodb' => [
        'collections' => [
            'settings' => getenv('FEP_SETTINGS_COLLECTION') ?: 'settings-v2'
        ]
    ]

];

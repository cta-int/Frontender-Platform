<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

defined('ROOT_PATH') || define('ROOT_PATH', dirname(__DIR__));

require '../vendor/autoload.php';

if (in_array($_SERVER['REQUEST_URI'], ['/edd2018', '/edd18']) !== false) {
    http_response_code(301);
    header('Location: /en/edd18');
    return;
}

if (in_array($_SERVER['REQUEST_URI'], ['/agrf18', '/agrf2018']) !== false) {
    http_response_code(301);
    header('Location: /en/agrf2018');
    return;
}

if (in_array($_SERVER['REQUEST_URI'], ['/vijabizproject']) !== false) {
    http_response_code(301);
    header('Location: /en/project/youth-economic-empowerment-through-agribusiness-in-kenya-vijabiz-sid0f664828b-9ccd-4c1c-bf39-7ebdfd7ffe44');
    return;
}

$platform = new Frontender\Core\App();
$platform->init()
    ->start();
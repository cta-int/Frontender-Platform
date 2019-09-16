<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Frontender\Platform\Template\Helper;

use Slim\Container;

class Random extends \Twig_Extension
{
    public $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('randomPartial', [$this, 'randomPartial']),
            new \Twig_SimpleFunction('die', [$this, 'killRender'])
        ];
    }

    public function killRender()
    {
        die('Died?');
    }

    public function randomPartial($path)
    {
        $root = ROOT_PATH . '/templates/';
        $partials = glob($root . $path . '/*.html.twig');
        $random = rand(0, count($partials) - 1);

        return str_replace($root, '', $partials[$random]);
    }
}
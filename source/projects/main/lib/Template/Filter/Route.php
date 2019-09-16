<?php
/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Frontender\Platform\Template\Filter;

use Slim\Container;
use Doctrine\Common\Inflector\Inflector;
use Frontender\Platform\Model\Traits\Projectable;

class Route extends \Twig_Extension
{
    use Projectable;
    
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function getFilters()
    {
        return [
            new \Twig_Filter('publication_route', [$this, 'publicationRoute'])
        ];
    }

    public function publicationRoute($label_id) {

		$routes = $this->getRoutes();

		if(!count($routes) || !array_key_exists($label_id, $routes)) {
			return false;
		}

		return '/' . $this->container->language->language . $routes[$label_id]['path'];
    }

}

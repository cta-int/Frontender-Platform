<?php
/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Frontender\Platform\Template\Filter;

use Slim\Container;

class Schema extends \Twig_Extension
{
    public function getFilters()
    {
        return [
            new \Twig_Filter('getSchema', [$this, 'getSchema'])
        ];
    }

    /**
     * This method will first check if there is an @{property} variable,
     * if available it will return that property.
     * 
     * If not available it will check if the normal property is available and return that,
     * if that property isn't available it will return false.
     * 
     * @param string $property The name of property to lookup.
     * 
     * @return * The found value, false if nothing is found.
     */
    public function getSchema($target, string $property)
    {
        if (!is_array($target) && !is_object($target)) {
            return false;
        }

        if (!is_array($target) && !($target instanceof \ArrayAccess)) {
            $target = (array)$target;
        }

        if (isset($target['@' . $property])) {
            return $target['@' . $property];
        } else if (isset($target[$property])) {
            return isset($target[$property]);
        }

        return false;
    }
}

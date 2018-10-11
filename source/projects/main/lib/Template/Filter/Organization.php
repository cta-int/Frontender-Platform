<?php
/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Prototype\Template\Filter;

use Slim\Container;

class Organization extends \Twig_Extension
{
    public function getFilters()
    {
        return [
            new \Twig_Filter('filterCTA', [$this, 'filterCTA'])
        ];
    }

    public function filterCTA($array, $isCTA = true) {
        if(!is_array($array)) {
            return false;
        }

        $organizations = array_filter($array, function($item) use ($isCTA) {
            if(!array_key_exists('isCTA', $item)) {
                return false;
            }

            return $item['isCTA'] == $isCTA;
        });

        if(count($organizations) <= 0) {
            return false;
        }

        if($isCTA === false) {
            return $organizations;
        }

        return array_shift($organizations);
    }
}
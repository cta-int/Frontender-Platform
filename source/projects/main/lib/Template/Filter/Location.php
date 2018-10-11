<?php
/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Prototype\Template\Filter;

class Location extends \Twig_Extension
{
    public function getFilters()
    {
        return [
            new \Twig_Filter('locationSpecific', [$this, 'getLocationSpecific']),
            new \Twig_Filter('locationRegion', [$this, 'getLocationRegion'])
        ];
    }

    public function getLocationSpecific($locations = []) {
        $filtered = array_filter($locations, function($location) {
            return array_key_exists('score', $location);
        });

        if(count($filtered) <= 0) {
            if(count($locations) > 0) {
                return array_shift($locations);
            }

            return false;
        }

        uasort($filtered, function($a, $b) {
            if ($a['score'] == $b['score']) {
                return 0;
            }
            return ($a['score'] < $b['score']) ? 1 : -1;
        });

        return array_pop($filtered);
    }

    public function getLocationRegion($locations = []) {
        $filtered = array_filter($locations, function($location) {
            return array_key_exists('score', $location);
        });

        if(count($filtered) <= 0) {
            if(count($locations) > 0) {
                return array_shift($locations);
            }

            return false;
        }

        uasort($filtered, function($a, $b) {
            if ($a['score'] == $b['score']) {
                return 0;
            }
            return ($a['score'] < $b['score']) ? 1 : -1;
        });

        return array_shift($filtered);
    }
}
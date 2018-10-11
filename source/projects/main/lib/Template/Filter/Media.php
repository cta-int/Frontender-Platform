<?php
/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Prototype\Template\Filter;

use Slim\Container;

class Media extends \Twig_Extension
{
    public function getFilters()
    {
        return [
            new \Twig_Filter('findType', [$this, 'findType']),
            new \Twig_Filter('getFileSize', [$this, 'getSize'])
        ];
    }

    public function findType($array, $type) {
        if(!is_array($array)) {
            return [];
        }

        return array_filter($array, function($item) use ($type) {
            if(!array_key_exists('type', $item)) {
                return false;
            }

            return $item['type'] == $type;
        });
    }

    public function getSize($path) {
        $size = 0;

        foreach(get_headers($path) as $header)
        {
            if(strpos($header, 'Content-Length') !== false) {
                $size = trim(str_replace('Content-Length:', '', $header));
            }
        }

        $units = array( 'B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $power = $size > 0 ? floor(log($size, 1024)) : 0;
        return number_format($size / pow(1024, $power), 0, '.', ',') . ' ' . $units[$power];
    }
}
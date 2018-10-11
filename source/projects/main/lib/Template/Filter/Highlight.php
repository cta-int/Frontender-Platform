<?php
/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Prototype\Template\Filter;

class Highlight extends \Twig_Extension
{
    public function getFilters()
    {
        return [
            new \Twig_Filter('highlightText', [$this, 'highlightText'])
        ];
    }

    public function highlightText($text, $toHighlight, $class) {
        if(!is_string($text) || empty($toHighlight)) {
            return $text;
        }

        $toHighlight = str_replace(['\\', '?', '+', '.', '[', ']', '|', '(', ')', '{', '}', '^', '/'], ["\\\\", '\?', '\+', '\.', '\[', '\]', '\|', '\(', '\)', '\{', '\}', '\^', '\/'], $toHighlight);
        return preg_replace('/(' . $toHighlight . ')/i', '<mark class="' . $class . '">$1</mark>', $text);
    }

    public function getLeadMedia($array)
    {
        if(count($array) == 0) {
            return false;
        }

        // Check if a media object is an image.
        $featured = array_filter($array, function($item) {
            if(!array_key_exists('weight', $item)) {
                return 0;
            }

            return $item['weight'] === 'featured';
        });

        if(count($featured) == 0) {
            return false;
        }

        return array_shift($featured);
    }

    // public function getFirstImage($array)
    // {
    //     if(count($array) == 0) {
    //         return false;
    //     }
    //
    //     // Check if a media object is an image.
    //     $images = array_filter($array, function($item) {
    //         if(!array_key_exists('type', $item) || !array_key_exists('metadata', $item) || !array_key_exists('id', $item['metadata'])) {
    //             return false;
    //         }
    //
    //         return $item['type'] === 'image' && $item['metadata'] && $item['metadata']['id'];
    //     });
    //
    //     if(count($images) == 0) {
    //         return false;
    //     }
    //
    //     return array_shift($images);
    // }

    public function getFilename($path)
    {
        return basename($path);
    }

    public function getCloudinaryID($url) {
        $file = $this->getFilename($url);
        $parts = explode('.', $file);

        return $parts[0];
    }

    public function getExtension($mimetype) {
        $parts = explode('/', $mimetype);

        return '.' . $parts[1];
    }
}

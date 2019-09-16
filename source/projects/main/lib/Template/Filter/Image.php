<?php
/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Frontender\Platform\Template\Filter;

class Image extends \Twig_Extension
{
    function __construct()
    {
        $this->_mimetypes = new \Mimey\MimeTypes();
    }

    public function getFilters()
    {
        return [
            new \Twig_Filter('lead', [$this, 'getLeadMedia']),
            // new \Twig_Filter('firstImage', [$this, 'getFirstImage']),
            new \Twig_Filter('filename', [$this, 'getFilename']),
            new \Twig_Filter('cloudinaryID', [$this, 'getCloudinaryID']),
            new \Twig_Filter('extension', [$this, 'getExtension']),
        ];
    }

    public function getLeadMedia($array)
    {
        if(count($array) == 0) {
            return false;
        }

        $images = array_filter($array, function($item) {
            return $item['type'] === 'image';
        });

        // Check if a media object is an image.
        $featured = array_filter($images, function($item) {
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
        return '.' . $this->_mimetypes->getExtension($mimetype);
    }
}

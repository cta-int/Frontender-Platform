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
            new \Twig_Filter('getFileSize', [$this, 'getSize']),
            new \Twig_Filter('getFileFormat', [$this, 'getFileFormat']),
            new \Twig_Filter('getFileDir', [$this, 'getFileDir']),
            new \Twig_Filter('getFileName', [$this, 'getFileName']),
            new \Twig_Filter('getFileBaseName', [$this, 'getFileBaseName']),
            new \Twig_Filter('getFileExtension', [$this, 'getFileExtension']),
            new \Twig_Filter('getImageDimensions', [$this, 'getImageDimensions'])
        ];
    }

    public function findType($array, $type)
    {
        if (!is_array($array)) {
            return [];
        }

        return array_filter($array, function ($item) use ($type) {
            if (!array_key_exists('type', $item)) {
                return false;
            }

            return $item['type'] == $type;
        });
    }

    public function getSize($path)
    {
        $size = 0;
        $context = stream_context_create([
            'http' => [
                'method' => 'HEAD'
            ]
        ]);

        foreach (get_headers($path, 0, $context) as $header) {
            if (strpos($header, 'Content-Length') !== false) {
                $size = trim(str_replace('Content-Length:', '', $header));
            }
        }

        $size = $size ?: 0;

        if (!$size) {
            return false;
        }

        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $power = $size > 0 ? floor(log($size, 1024)) : 0;
        return number_format($size / pow(1024, $power), 0, '.', ',') . ' ' . $units[$power];
    }

    public function getFileFormat($mimetype)
    {
        $mimes = new \Mimey\MimeTypes;
        return $mimes->getExtension($mimetype);
    }

    public function getFileDir($filename)
    {
        return pathinfo($filename, PATHINFO_DIRNAME);
    }

    public function getFileName($filename)
    {
        return pathinfo($filename, PATHINFO_FILENAME);
    }

    public function getFileBaseName($filename)
    {
        return pathinfo($filename, PATHINFO_BASENAME);
    }

    public function getFileExtension($filename)
    {
        return pathinfo($filename, PATHINFO_EXTENSION);
    }

    public function getImageDimensions($filename)
    {
        list($width, $height, $type, $attr) = getimagesize($filename);

        return [
            'width' => $width,
            'height' => $height,
            'type' => $type,
            'attr' => $attr
        ];
    }
}

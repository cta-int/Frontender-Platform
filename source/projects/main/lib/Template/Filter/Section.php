<?php
/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Prototype\Template\Filter;

use Slim\Container;

class Section extends \Twig_Extension
{
    public function getFilters()
    {
        return [
            new \Twig_Filter('rootSection', [$this, 'getRootSection']),
            new \Twig_Filter('sectionPath', [$this, 'getSectionPath'])
        ];
    }

    public function getRootSection($entry) {
        if($entry) {
            if(isset($entry->section)) {
                return $this->getRootSection($entry->section);
            }

            if(strtolower($entry->contentType->getName()) === 'section') {
                return $entry;
            }
        }

        return false;
    }

    public function getSectionPath($entry) {
        $path = [];

        function getRoot($entry, &$path) {
            array_unshift($path, $entry);

            if(isset($entry->section)) {
                getRoot($entry->section, $path);
            }
        }

        if($entry) {
            getRoot($entry, $path);
        }

        return $path;
    }
}

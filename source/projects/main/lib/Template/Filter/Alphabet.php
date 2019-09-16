<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Frontender\Platform\Template\Filter;

use Slim\Container;

class Alphabet extends \Twig_Extension
{
    protected $_language;

    public function __construct(Container $container)
    {
        $this->_language = $container['language']->get();
    }

    public function getFilters()
    {
        return [
            new \Twig_Filter('formatAlphabet', [$this, 'formatAlphabet']),
            new \Twig_Filter('filterAlphabet', [$this, 'filterAlphabet'])
        ];
    }

    public function formatAlphabet($array, $attribute)
    {
        $ret = [];

        if (!is_array($array) && !($array instanceof \Iterator)) {
            return $ret;
        }

        foreach ($array as $item) {
            $prop = $this->_getProperty($item, $attribute);

            if ($prop) {
                $first = strtoupper(utf8_encode($prop[0]));

                if (!array_key_exists($first, $ret)) {
                    $ret[$first] = [];
                }

                $ret[$first][] = $item;
            }
        }

        ksort($ret);

        return $ret;
    }

    public function filterAlphabet($object, $attribute, $value)
    {
        if (!is_array($object) && !($object instanceof \Iterator)) {
            return [];
        }

        if (!$value) {
            return $object;
        }

        foreach ($object as $key => $item) {
            $prop = $this->_getProperty($item, $attribute);

            if ($prop) {
                if (strpos(strtolower($prop), $value) !== 0) {
                    unset($object[$key]);
                }
            } else {
                unset($object[$key]);
            }
        }

        return $object;
    }

    protected function _getProperty($object, $property)
    {
        $properties = explode('.', $property);

        if ($properties) {
            foreach ($properties as $val) {
                if (isset($object[$val])) {
                    $object = $object[$val];
                } else {
                    break;
                }
            }
        }

        if (!is_object($object) && !is_array($object) && $object) {
            return $object;
        } else {
            return $object[$this->_language] ?? $object['en'] ?? false;
        }
    }
}
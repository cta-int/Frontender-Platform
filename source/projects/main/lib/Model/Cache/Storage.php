<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Prototype\Model\Cache;

use Kevinrob\GuzzleCache\Storage\CacheStorageInterface;
use Kevinrob\GuzzleCache\CacheEntry;

class Storage implements CacheStorageInterface {
    public static $cache = [];

    public function fetch($key)
    {
        if (isset(Storage::$cache[$key])) {
            // The file exist, read it!
            $data = @unserialize(
                Storage::$cache[$key]
            );

            if ($data instanceof CacheEntry) {
                return $data;
            }
        }

        return;
    }

    /**
     * @inheritdoc
     */
    public function save($key, CacheEntry $data)
    {
        return Storage::$cache[$key] = serialize($data);
    }
}
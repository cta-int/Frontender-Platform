<?php

namespace Prototype\Model\Traits;

trait Imagable
{
    public function bindLeadImage($key = 'link.mediaObject')
    {
        $path = explode('.', $key);
        $objects = array_reduce($path, function ($carry, $key) {
            if (!$carry) {
                return false;
            }

            if (array_key_exists($key, $carry)) {
                return $carry[$key];
            }

            return false;
        }, $this);

        if (!$objects || count($objects) == 0) {
            return false;
        }

        $images = array_filter($objects, function ($item) {
            return $item['type'] === 'image';
        });

		// Check if a media object is an image.
        $featured = array_filter($images, function ($item) {
            if (!array_key_exists('weight', $item)) {
                return 0;
            }

            return $item['weight'] === 'featured';
        });

        if (count($featured) == 0) {
            return false;
        }

        return array_shift($featured);
    }
}
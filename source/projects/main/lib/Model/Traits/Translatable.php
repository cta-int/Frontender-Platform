<?php

namespace Prototype\Model\Traits;

trait Translatable
{
    public $translations = [
        'en' => 'en-GB',
        'fr' => 'fr-FR',
        'pt' => 'pt-PT',
        'es' => 'es-ES'
    ];

    public function translate($item)
    {
        foreach ($item as $key => $value) {
            if (in_array($key, array_keys($this->translations))) {
                $newKey = $this->translations[$key];
                $item[$newKey] = $item[$key];
                unset($item[$key]);
            }
        }

        return $item;
    }
}
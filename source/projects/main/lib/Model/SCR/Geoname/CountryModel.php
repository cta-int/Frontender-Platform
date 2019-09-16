<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Frontender\Platform\Model\SCR\Geoname;

use Frontender\Platform\Model\SCR\ScrModel;
use Slim\Container;
use Doctrine\Common\Inflector\Inflector;

class CountryModel extends ScrModel
{
    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->getState()
            ->insert('id');
    }

    public function fetch($raw = false)
    {
        $state = $this->getState();

        $name = $state->isUnique() ? Inflector::singularize($this->getModelName()) : Inflector::pluralize($this->getModelName());

        $method = 'get' . ucfirst($name);

        $response = call_user_func_array([$this->adapter, $method], [$state->getValues()]);
        $countries = isset($response['country']) ? $response['country'] : $response;

        if (count($state->id) > 0) {
            $countries = $this->_filterItems($countries, $state->id);
        }

        $model = $this;
        $container = $this->container;
        $state = $this->getState();

        $countries = array_map(function ($item) use ($model, $container, $state) {
            $newItem = new $model($container);
            $newItem->setData($item);
            $newItem->setState($state->getValues());

            return $newItem;
        }, $countries);

        return array_values($countries);
    }

    private function _filterItems($items, $ids)
    {
        return array_filter($items, function ($item) use ($ids) {
            return in_array($item['countryCode'], $ids);
        });
    }
}

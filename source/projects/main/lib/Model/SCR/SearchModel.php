<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Prototype\Model\SCR;

use Slim\Container;
use Doctrine\Common\Inflector\Inflector;

class SearchModel extends ScrModel
{
    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->getState()
            ->insert('types') // Can be an array or a string.
            ->insert('q')
            ->insert('language', $this->container->language->get())
//            ->insert('must', 'raw', []) // Can be an object
//            ->insert('should', 'raw', [])  // Can be an object
//            ->insert('mustNot', 'raw', [])
            ->insert('limit', 20)
            ->insert('time')
            ->insert('concepts', [])
            ->insert('to')
            ->insert('from')
            ->insert('location')
            ->insert('must')
            ->insert('should')
            ->insert('mustNot')
            ->insert('type')
            ->insert('skip');  // Can be an object
    }

    public function fetch($raw = false)
    {
		// I have to loop through all the things, and make a state, however the state will be inserted
        $state = $this->getState();
        $query = $this->container->request->getQueryParams();
        $search_types = explode(',', $state->types);
        $result = [];

        if (count($search_types)) {
            foreach ($search_types as $type) {
                $model = '\Prototype\Model\SCR\\' . ucfirst($type) . '\SearchModel';
                $model = new $model($this->container);
                $initial = $query[$type] ?? [];

                $model->setState(array_merge($this->getState()->getValues(), $initial));
                $response = $model->fetch();
                if ($response) {
                    $result[$type] = $response;
                }
            }
        }

        return [$result];
    }
}

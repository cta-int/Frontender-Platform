<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Prototype\Model\SCR;

use Slim\Container;
use Doctrine\Common\Inflector\Inflector;
use Prototype\Model\SCR\Channel\AbstractModel;

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

    public function getPropertyArticle()
    {
        $state = $this->getState();
        $types = explode(',', $state->types);

        if (in_array('article', $types)) {
            $model = new \Prototype\Model\SCR\Article\SearchModel($this->container);
            $initial = $query[$type] ?? [];

            $model->setState(array_merge($this->getState()->getValues(), $initial));
            return $model->fetch();
        }

        return [];
    }

    public function getPropertyArticleTotal()
    {
        $state = $this->getState();
        $types = explode(',', $state->types);

        if (in_array('article', $types)) {
            $model = new \Prototype\Model\SCR\Article\SearchModel($this->container);
            $initial = $query[$type] ?? [];

            $model->setState(array_merge($this->getState()->getValues(), $initial));
            $response = $model->fetch(true);

            return $response['total'];
        }

        return 0;
    }

    public function getPropertyEvent()
    {
        $state = $this->getState();
        $types = explode(',', $state->types);

        if (in_array('event', $types)) {
            $model = new \Prototype\Model\SCR\Event\SearchModel($this->container);
            $initial = $query[$type] ?? [];

            $model->setState(array_merge($this->getState()->getValues(), $initial));
            return $model->fetch();
        }

        return [];
    }

    public function getPropertyEventTotal()
    {
        $state = $this->getState();
        $types = explode(',', $state->types);

        if (in_array('event', $types)) {
            $model = new \Prototype\Model\SCR\Event\SearchModel($this->container);
            $initial = $query[$type] ?? [];

            $model->setState(array_merge($this->getState()->getValues(), $initial));
            $reponse = $model->fetch(true);

            return $response['total'];
        }

        return 0;
    }

    public function fetch($raw = false)
    {
        return [$this];
    }
}

<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Prototype\Model\SCR;

use Slim\Container;
use Prototype\Model\SCR\Channel\ArticlesModel;
use Prototype\Model\SCR\Channel\EventsModel;
use Prototype\Model\SCR\Channel\PersonsModel;

class ChannelsModel extends ScrModel
{
    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->getState()
            ->insert('id', null, true)
            ->insert('type', 'article')
            ->insert('label_id', null)
            ->insert('limit', 20)
            ->insert('skip', 0)
            ->insert('articleType', 'blog')
            ->insert('eventType');
    }

    public function getPropertyArticles()
    {
        $state = $this->getState()->getValues();

        $articlesModel = new ArticlesModel($this->container);
        $articlesModel->setState($state);

        return $articlesModel->fetch();
    }

    public function getPropertyEvents()
    {
        $state = $this->getState()->getValues();

        $eventsModel = new EventsModel($this->container);
        $eventsModel->setState($state);

        return $eventsModel->fetch();
    }

    public function getPropertyPersons()
    {
        $state = $this->getState()->getValues();

        $personsModel = new PersonsModel($this->container);
        $personsModel->setState($state);

        return $personsModel->fetch();
    }

    public function fetch($raw = false)
    {
        $result = parent::fetch($raw);

        foreach ($result as $item) {
            if (isset($item['name'])) {
                $item['name'] = $this->translate($item['name']);
            }
        }

        return $result;
    }
}

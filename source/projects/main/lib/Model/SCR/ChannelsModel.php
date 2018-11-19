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

    public function getLabels(string $type = '', bool $first = false)
    {
        $_labels = $this->data['label'];

        if ($type != '') {
            foreach ($_labels as $key => $value) {
                if ($value['type'] != $type) {
                    unset($_labels[$key]);
                }
            }
        }

        if ($first) {
            // $_labels = array_slice($_labels, 0, 1);
            $_labels = array_shift($_labels);
        }

        return $_labels;
    }

    public function getPropertyStrategyLabel()
    {
        $_label = $this->getLabels('strategy', true);
    }

    public function getPropertyTheme()
    {
        $_label = $this->getLabels('strategy', true);

        $_theme = [
            'selector' => '',
            'label' => ''
        ];

        if (!isset($this->container['theme-color'])) {
            $_config = $this->container['theme-color'] = json_decode(file_get_contents(__DIR__ . '/Label/SearchModel.json'), true);
        } else {
            $_config = $this->container['theme-color'];
        }

        if (isset($_config[$_label['type']]['theme-color'][$_label['_id']])) {
            $_label['selector'] = $_config[$_label['type']]['theme-color'][$_label['_id']];
        }

        return $_label;
    }
}

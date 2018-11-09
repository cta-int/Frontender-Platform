<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Prototype\Model\SCR;

use Slim\Container;
use Prototype\Model\SCR\Event\AttendeesModel;
use Prototype\Model\Traits\Imagable;

class EventsModel extends ScrModel
{
    use Imagable;

    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->getState()
            ->insert('id', null, true)
            ->insert('language', $this->container->language->get())
            ->insert('format', 'scr')
            ->insert('limit')
            ->insert('skip')
            ->insert('upcoming', 'false')
            ->insert('includeCancelled', false)
            ->insert('start')
            ->insert('end')
            ->insert('timezoneOffset')
            ->insert('includeRelated', false);
    }

    public function getPropertyLead_image()
    {
        return $this->bindLeadImage();
    }

    public function getPropertyAttendees()
    {
        $attendees = new AttendeesModel($this->container);
        return $attendees->setState([
            'id' => $this->getState()->id
        ])->fetch();
    }

    public function getPropertySimilar()
    {
        return new class ($this->getState(), $this->container)
        {
            private $cachedEvents = null;
            private $cachedArticles = null;
            private $container;
            private $state;

            public function __construct($state, $container)
            {
                $this->state = $state;
                $this->container = $container;
            }

            public function articles()
            {
                if (!$this->cachedArticles) {
                    $related_articles = new \Prototype\Model\SCR\Event\ArticlesModel($this->container);
                    $this->cachedArticles = $related_articles->setState([
                        'id' => $this->state->id,
                        'limit' => 8,
                        'language' => $this->state->language
                    ]);
                    $this->cachedArticles = $this->cachedArticles->fetch();
                }

                return $this->cachedArticles;
            }

            public function events()
            {
                if (!$this->cachedEvents) {
                    $related_events = new \Prototype\Model\SCR\Event\EventsModel($this->container);
                    $this->cachedEvents = $related_events->setState([
                        'id' => $this->state->id,
                        'limit' => 8,
                        'language' => $this->state->language
                    ])->fetch();
                }

                return $this->cachedEvents;
            }
        };
    }

    public function getPropertyRelated()
    {
        $linksModel = new LinksModel($this->container);
        $related = $linksModel->setState([
            'doc_type' => 'event',
            'doc_id' => $this->getState()->id
        ])->fetch();

        $state = $this->getState();
        $container = $this->container;

        return [
            'events' => array_map(function ($event) use ($state, $container) {
                $model = new EventsModel($container);
                return $model
                    ->setState($state->getValues())
                    ->setData($event);
            }, $related['events']),
            'articles' => array_map(function ($article) use ($state, $container) {
                $model = new ArticlesModel($container);
                return $model
                    ->setState($state->getValues())
                    ->setData($article);
            }, $related['articles']),
            'persons' => array_map(function ($person) use ($state, $container) {
                $model = new PersonsModel($container);
                return $model
                    ->setState($state->getValues())
                    ->setData($person);
            })
        ];
    }

    public function getPropertyTheme()
    {
        if ($this['type'] === 'Project') {
            $regions = [
                '7729886' => 'purple',
                '7729889' => 'red',
                '7729885' => 'orange',
                '9406051' => 'yellow',
                '7729891' => 'gold',
                '2363254' => 'blue'
            ];
            $ids = array_column($this['analysis']['geoname']['geonames'] ?? [], '_id');

            die('Called');

            $items = array_intersect($ids, array_keys($regions));
            $event['region_id'] = array_shift($items);

            if ($event['region_id']) {
                return $regions[$event['region_id']];
            }
        }

        return false;
    }

    private function _bindRegionId(&$event)
    {
        // get all the regions.
        $regions = [
            '7729886' => 'purple',
            '7729889' => 'red',
            '7729885' => 'orange',
            '9406051' => 'yellow',
            '7729891' => 'gold',
            '2363254' => 'blue'
        ];
        $ids = array_column($event['analysis']['geoname']['geonames'] ?? [], '_id');

        $items = array_intersect($ids, array_keys($regions));
        $event['region_id'] = array_shift($items);

        if ($event['region_id']) {
            $event['theme'] = $regions[$event['region_id']];
        }
    }
}

<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Frontender\Platform\Model\SCR;

use Frontender\Platform\Model\SCR\ScrModel;
use Frontender\Platform\Model\Traits\Imagable;
use Frontender\Platform\Model\Traits\Searchable;
use Slim\Container;
use Frontender\Platform\Model\SCR\Article\SearchModel;

class ProjectsModel extends EventsModel
{
    public function getModelName() : string
    {
        return 'event';
    }

    public function getPropertySimilar()
    {
        return new class ($this->getState(), $this->container, $this->data)
        {
            private $cachedEvents = null;
            private $cachedArticles = null;
            private $container;
            private $state;

            public function __construct($state, $container, $event)
            {
                $this->state = $state;
                $this->container = $container;
                $this->event = $event;
            }

            public function articles()
            {
                if (!$this->cachedArticles) {
                    $related_articles = new \Frontender\Platform\Model\SCR\Event\ArticlesModel($this->container);
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
                if (!$this->cachedAllEvents) {
                    $now = new \DateTime();
                    $future = new \DateTime();

                    $this->cachedAllEvents = $this->getEvents([]);
                }

                return $this->cachedAllEvents;
            }

            public function upcomingEvents()
            {
                if (!$this->cachedUpcomingEvents) {
                    $now = new \DateTime();
                    $future = new \DateTime();

                    $this->cachedUpcomingEvents = $this->getEvents([
                        'from' => $now->format('Y-m-d\TH:i:sO'),
                        'to' => $future->modify('+5 years')->format('Y-m-d\TH:i:sO')
                    ]);
                }

                return $this->cachedUpcomingEvents;
            }

            public function pastEvents()
            {
                if (!$this->cachedPastEvents) {
                    $now = new \DateTime();
                    $past = new \DateTime();

                    $this->cachedPastEvents = $this->getEvents([
                        'from' => $past->modify('-5 years')->format('Y-m-d\TH:i:sO'),
                        'to' => $now->format('Y-m-d\TH:i:sO')
                    ]);
                }

                return $this->cachedPastEvents;
            }

            private function getEvents($state)
            {
                $projectLabel = array_filter($this->event['label'], function ($label) {
                    return $label['type'] === 'programme';
                });

                $model = new \Frontender\Platform\Model\SCR\Event\SearchModel($this->container);
                $model->setState(array_merge([
                    'limit' => 5,
                    'label' => array_map(function ($label) {
                        return $label['_id'];
                    }, $projectLabel),
                    'language' => $this->state->language,
                    'mustNot' => [
                        [
                            'type' => 'field',
                            'id' => 'type',
                            'value' => 'Project'
                        ], [
                            'type' => 'field',
                            'id' => '_id',
                            'value' => $this->event['_id']
                        ]
                    ]
                ], $state));

                return $model->fetch();
            }
        };
    }

    public function getPropertyPath()
    {
        return 'project';
    }
}

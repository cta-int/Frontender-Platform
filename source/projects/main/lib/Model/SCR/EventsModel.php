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
use Prototype\Model\SCR\Article\SearchModel;
use Prototype\Model\Utils\Sorting;

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
            ->insert('limit', 20)
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
                $model = new \Prototype\Model\SCR\Event\SearchModel($this->container);
                $model->setState(array_merge([
                    'limit' => 5,
                    'label' => array_map(function ($label) {
                        return $label['_id'];
                    }, $this->event['label']),
                    'concept' => array_map(function ($concept) {
                        return $concept['_id'];
                    }, $this->event['analysis']['agrovoc']['concepts']),
                    'geo' => array_map(function ($geo) {
                        return $geo['uri'];
                    }, $this->event['analysis']['geonames'] ?? []),
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

    public function getPropertyIssues()
    {
        $_label = $this->getLabels('programme', true);

        if (!$_label) {
            return false;
        }

        $model = new SearchModel($this->container);
        $model->setState([
            'type' => 'article.issue',
            'limit' => 4,
            'label' => [$_label['_id']]
        ]);
        return $model->fetch();
    }

    public function getPropertyUpdates()
    {
        $_label = $this->getLabels('programme', true);

        if (!$_label) {
            return false;
        }

        $model = new SearchModel($this->container);
        $model->setState([
            'type' => 'article.blog',
            'limit' => 50,
            'label' => [$_label['_id']]
        ]);
        $updates = $model->fetch();

        return Sorting::sortBy($updates, 'datePublished', Sorting::$DIRECTION_DESC);
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
            }, $related['persons'])
        ];
    }

    public function getPropertyPath(): string
    {
        if ($this['type'] === 'Project') {
            return 'project';
        }

        return parent::getPropertyPath();
    }

    // private function _bindRegionId(&$event)
    // {
    //     // get all the regions.
    //     $regions = [
    //         '7729886' => 'purple',
    //         '7729889' => 'red',
    //         '7729885' => 'orange',
    //         '9406051' => 'yellow',
    //         '7729891' => 'gold',
    //         '2363254' => 'blue'
    //     ];
    //     $ids = array_column($event['analysis']['geoname']['geonames'] ?? [], '_id');

    //     $items = array_intersect($ids, array_keys($regions));
    //     $event['region_id'] = array_shift($items);

    //     if ($event['region_id']) {
    //         $event['theme'] = $regions[$event['region_id']];
    //     }
    // }   

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
            if (count($_labels)) {
                return array_shift($_labels);
            }

            return false;
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

        if (!$_label) {
            return false;
        }

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

    public function getPropertyLeadImage()
    {
        if (!is_array($this['mediaObject']) || !count($this['mediaObject'])) {
            return false;
        }

        $images = array_filter($this['mediaObject'], function ($item) {
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

        $image = array_shift($featured);

        // Merge the data with the data from the SCR to get a complete item.
        // Or just return that one.

        $model = new MediaModel($this->container);
        $model->setState([
            'id' => $image['_id']
        ]);
        $image = $model->fetch();

        return array_shift($image);
    }

    public function getPropertyTakeaways()
    {
        $this['contentBlocks'][0]['type'] === 'list' ? $this['contentBlocks'][0] : false;
    }

    public function getPropertyConcepts()
    {
        return isset($this['analysis']['agrovoc']) ? $this['analysis']['agrovoc'] : [];
    }
}

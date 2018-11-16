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

    public function getPropertyPath() : string
    {
        if ($this['type'] === 'Project') {
            return 'project';
        }

        return parent::getPropertyPath();
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

    public function getPropertyTheme()
    {
        if (!isset($this->container['theme-color'])) {
            $this->container['theme-color'] = json_decode(file_get_contents(__DIR__ . '/Label/SearchModel.json'), true);
        }

        $themeColors = $this->container['theme-color'];
        $color = '';
        $label = false;

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

            $items = array_intersect($ids, array_keys($regions));
            $event['region_id'] = array_shift($items);

            if ($event['region_id']) {
                $color = $regions[$event['region_id']];
            }
        } else if (isset($this['label']) && count($this['label'])) {
            $labels = array_filter($this['label'], function ($label) use ($themeColors) {
                return isset($themeColors[$label['type']]['theme-color'][$label['_id']]);
            });

            $label = array_shift($labels);
            $color = isset($this->container['theme-color'][$label['type']]['theme-color'][$label['_id']]) ? $this->container['theme-color'][$color['type']]['theme-color'][$label['_id']] : '';
        }

        return [
            'color' => $color,
            'label' => $label
        ];
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

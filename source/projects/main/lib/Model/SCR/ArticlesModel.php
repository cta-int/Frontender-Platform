<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Prototype\Model\SCR;

use Prototype\Model\Traits\Projectable;
use Slim\Container;
use Prototype\Model\Traits\Imagable;
use Prototype\Model\SCR\MediaModel;

class ArticlesModel extends ScrModel
{
    use Projectable;
    use Imagable;

    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->getState()
            ->insert('id', null, true)
            ->insert('limit')
            ->insert('skip')
            ->insert('language', $this->container->language->get())
            ->insert('analysis')
            ->insert('recursive', true)
            ->insert('includeRelated', false)
            ->insert('related_limit', 16)
            ->insert('similar_limit', 16);
    }

    public function getPropertyLead_image()
    {
        return $this->bindLeadImage();
    }

    public function getPropertyRelated()
    {
        $related = new LinksModel($this->container);
        $related = $related->setState([
            'doc_type' => 'article',
            'doc_id' => $this->state->id,
            'limit' => $this->state->related_limit
        ])->fetch();

        $state = $this->getState();
        $container = $this->container;

        return [
            'events' => array_map(function ($event) use ($state, $container) {
                $model = new EventsModel($container);
                return $model
                    ->setState($state)
                    ->setData($event);
            }, $related['events']),
            'articles' => array_map(function ($article) use ($state, $container) {
                $model = new ArticlesModel($container);
                return $model
                    ->setState($state)
                    ->setData($event);
            }, $related['articles']),
            'persons' => array_map(function ($person) use ($state, $container) {
                $model = new PersonsModel($container);
                return $model
                    ->setState($state)
                    ->setData($event);
            })
        ];
    }

    public function getPropertySimilar()
    {
        return new class ($this, $this->getState(), $this->container)
        {
            private $cachedEvents = null;
            private $cachedArticles = null;
            private $container;
            private $state;

            public function __construct($article, $state, $container)
            {
                $this->article = $article;
                $this->state = $state;
                $this->container = $container;
            }

            public function articles()
            {
                if (!$this->cachedArticles) {
                    $articles = new \Prototype\Model\SCR\Article\SearchModel($this->container);
                    $this->cachedArticles = $articles->setState([
                        'label' => array_map(function ($label) {
                            return $label['_id'];
                        }, $this->article['link']['label']),
                        'limit' => $this->state->similar_limit,
                        'language' => $this->state->language,
                        'mustNot' => [[
                            'type' => 'field',
                            'id' => '_id',
                            'value' => $this->article['_id']
                        ]]
                    ])->fetch();
                }

                return $this->cachedArticles;
            }
        };
    }

    public function getPropertyIssue()
    {
        // We only come here if we have labels.
        $searchModel = new \Prototype\Model\SCR\Article\SearchModel($this->container);
        $review = $searchModel->setState([
            'type' => 'article.issue',
            'label' => array_map(function ($label) {
                return $label['_id'];
            }, $labels),
            'limit' => 1,
            'language' => $this->container->language->get(),
            'recursive' => false
        ])->fetch();
        $review = $review['ArticlesSearches'];

		// Do a count just in case.
        if (!count($review)) {
            return false;
        }

        return array_shift($review);
    }

    public function getPropertyPath_prefix()
    {
        $routes = $this->getRoutes();
        if (!count($routes)) {
            return false;
        }

        if (array_key_exists('link', $article) && count($article['link']['label'])) {
            $links = [];

            foreach ($article['link']['label'] as $link) {
                $links[$link['_id']] = $link;
            }

            $intersect = array_intersect_key($routes, $links);
            if (!count($intersect)) {
                return $article;
            }

            $route = array_shift($intersect);

			// Get the first intersection.
            return $route['path'] . '/';
        }

        return false;
    }

    private function getPropertyRoutes()
    {
        if ($this->container->has('routes')) {
            return $this->container->get('routes');
        }

        $settings = $this->container->get('settings');
        $project = $settings->get('project');

        $path = $project['path'] . '/routes.json';
        $routes = file_exists($path) ? json_decode(file_get_contents($path), true) : [];

		// We will only need the label routes.
		// If there are none, we will return an empty array.
        if (!array_key_exists('labels', $routes)) {
            return [];
        } else if (count($routes['labels']) <= 0) {
            return [];
        }

		// We now know it is available and it has items.
        $routes = $routes['labels'];
		// First we will filter out the ones we don't need.
        $routes = array_filter($routes, function ($item) {
            if (!array_key_exists('publish_at', $item)) {
                return false;
            }

            if ($item['publish_at'] > time()) {
                return false;
            }

            return true;
        });

        uasort($routes, function ($a, $b) {
            if ($a['publish_at'] == $b['publish_at']) {
                return 0;
            }

            return $a['publish_at'] < $b['publish_at'] ? 1 : -1;
        });

		// Now we will format on segments.
        uasort($routes, function ($a, $b) {
            $segements_a = count(explode('/', $a['path']));
            $segements_b = count(explode('/', $b['path']));

            if ($segements_a == $segements_b) {
                return 0;
            }

            return $segements_a < $segements_b ? 1 : -1;
        });

        $this->container['routes'] = $routes;

        return $routes;
    }

    public function getPropertyContentBlocks()
    {
        $mediaModel = new MediaModel($this->container);
        $article = $this;

        $contentBlocks = array_map(function ($block) use ($article, $mediaModel) {
            if ((isset($block['subtype']) && $block['subtype'] === 'image') || (isset($block['type']) && $block['type'] === 'video')) {
                $item = $mediaModel->setState([
                    'id' => $block['id']
                ])->fetch();

                if (count($item) > 0) {
                    $item = array_shift($item);

                    $block['about'] = $item['about'] ?? '';
                    $block['name'] = $item['name'] ?? '';
                    $block['credit'] = $item['credit'] ?? '';
                    $block['url'] = $item['metadata']['url'] ?? '';
                }
            }

            return $block;
        }, $this->data['contentBlocks']);

        $contentBlocks = array_filter($contentBlocks, function ($block) {
            return $block['type'] !== 'footnote';
        });

        return $contentBlocks;
    }

    public function getPropertyFootnotes()
    {
        $footnotes = array_filter($this->data['contentBlocks'], function ($contentBlock) {
            return isset($contentBlock['type']) && $contentBlock['type'] === 'footnote';
        });

        if (count($footnotes) > 0) {
            return array_shift($footnotes);
        }

        return [];
    }
}

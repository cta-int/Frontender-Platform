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
use Frontender\Core\DB\Adapter;
use Frontender\Core\Template\Filter\Translate;
use Prototype\Model\SCR\Article\SearchModel;

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
                    $articles->setState([
                        'label' => array_map(function ($label) {
                            return $label['_id'];
                        }, $this->article['link']['label']),
                        'limit' => $this->state->similar_limit,
                        'language' => $this->state->language ?? 'en',
                        'mustNot' => [[
                            'type' => 'field',
                            'id' => '_id',
                            'value' => $this->article['_id']
                        ]]
                    ]);

                    $this->cachedArticles = $articles->fetch();
                    usort($this->cachedArticles, function($a, $b){
                        $aDatePublished = new \DateTime($a->datePublished);
                        $bDatePublished = new \DateTime($b->datePublished);
                        if($aDatePublished == $bDatePublished) {
                            return 0;
                        }
                        return $aDatePublished > $bDatePublished ? -1 : 1;
                    });
                }

                return $this->cachedArticles;
            }

            public function publicationArticles()
            {
                if (!$this->cachedPublicationArticles) {
                    $labels = $this->article['link']['label'];
                    $labels = array_filter($labels, function ($label) {
                        return $label['type'] === 'publication';
                    });

                    $articles = new \Prototype\Model\SCR\Article\SearchModel($this->container);
                    $articles->setState([
                        'label' => array_map(function ($label) {
                            return $label['_id'];
                        }, $labels),
                        'limit' => $this->state->similar_limit,
                        'language' => $this->state->language ?? 'en',
                        'mustNot' => [[
                            'type' => 'field',
                            'id' => '_id',
                            'value' => $this->article['_id']
                        ]]
                    ]);
                    $this->cachedPublicationArticles = $articles->fetch();
                }

                return $this->cachedPublicationArticles;
            }
        };
    }

    public function getPropertyIssue()
    {
        // We only come here if we have labels.
        $searchModel = new \Prototype\Model\SCR\Article\SearchModel($this->container);
        $searchModel->setState([
            'type' => 'article.issue',
            'label' => array_column($this['link']['label'], '_id'),
            'limit' => 1,
            'language' => $this->container->language->get(),
            'recursive' => false
        ]);
        $review = $searchModel->fetch();

		// Do a count just in case.
        if (!count($review)) {
            return false;
        }

        return array_shift($review);
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

        if (!isset($this->data['contentBlocks'])) {
            return '';
        }

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

    public function getPropertyPath() : string
    {
        // If we have no labels, or they are empty, we will return the parent.
        if (!isset($this['link']['label']) || !count($this['link']['label'])) {
            return parent::getPropertyPath();
        }

        // We will check if there is a route for one of the labels that we have.
        $routes = Adapter::getInstance()->collection('routes.static')->find([
            'source' => ['$regex' => 'scr\/label.*', '$options' => 'i']
        ])->toArray();
        $routes = Adapter::getInstance()->toJSON($routes, true);

        // I now need my own labels.
        $labels = $this['link']['label'];
        $own_label_ids = array_column($labels, '_id');

        // I will check if there is an intersect of the routes, everyone that isn't found will not be returned.
        $routes = array_filter($routes, function ($route) use ($own_label_ids) {
            // We already have the label ids, so we will use it.
            $parts = explode('/', $route['source']);
            $label_id = end($parts);

            return in_array($label_id, $own_label_ids);
        });

        // If no label routes are found, we will return the route as it is supposed to be.
        if (!count($routes)) {
            return parent::getPropertyPath();
        }

        $flipped_labels = array_flip($own_label_ids);
        $routes = array_map(function ($route) use ($labels, $flipped_labels) {
            // Every maintaining route will have the label appended to it.
            // I need the key of the label I need to add so I flip the array, so the value becomes the key and the key becomes the value.
            $parts = explode('/', $route['source']);
            $label_id = end($parts);
            $key = $flipped_labels[$label_id];

            $route['label'] = $labels[$key];
            $route['score'] = count(explode('/', $route['label']['name']));

            return $route;
        }, $routes);

        // I now need to sort the routes, the most specific route will be used,
        // the score is calculated at the amount or parts it has.
        uasort($routes, function ($a, $b) {
            if ($a['score'] < $b['score']) {
                return 1;
            } else if ($b['score'] < $a['score']) {
                return -1;
            }

            return 0;
        });

        $prefix = array_shift($routes);

        // Use the translated value.
        $filter = new Translate($this->container);
        $prefix = $filter->translate($prefix['destination']);

        return implode('/', [$prefix, parent::getPropertyPath()]);
    }

    public function getLabels(string $type = '', bool $first = false)
    {
        $_labels = $this->data['link']['label'];

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
        return $this->getLabels('strategy', true);
    }

    public function getPropertyPublicationLabel()
    {
        return $this->getLabels('publication', true);
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

        if ($this['publicationLabel']) {
            $_oldLabel = $_label;
            $_label = $this['publicationLabel'];

            if (!isset($_config[$_label['type']]['theme-color'][$_label['_id']])) {
                $_label = $_oldLabel;
            }
        }

        if (isset($_config[$_label['type']]['theme-color'][$_label['_id']])) {
            $_label['selector'] = $_config[$_label['type']]['theme-color'][$_label['_id']];
        }

        return $_label;
    }

    public function getPropertyDossier()
    {
        // Return dossier label if present, null if not
        switch($this->container->language->get()) {
            case 'fr-FR':
                $needle = 'dossier :';
                continue;
            case 'en-GB':
            default:
                $needle = 'dossier:';
                continue;
        } 
        
        $label = $this->getLabel('publication', $needle);

        if (!isset($label['_id'])) {
            return false;
        }

        $search = new SearchModel($this->container);
        $search->setState([
            'label' => [$label['_id']],
            'type' => 'article.issue',
            'limit' => 1
        ]);
        $issueArticle = $search->fetch();

        // The fetch function always returns an array, so we will check if we have an instance,
        // If so we will need that instance, if there is nothing, we will set the value to false,
        // This way twig doesn't break.
        if (count($issueArticle) >= 1) {
            $issueArticle = array_shift($issueArticle);
        } else {
            $issueArticle = false;
        }
        $labelsModel = new LabelsModel($this->container);
        $labelsModel->setData($label);

        return [
            'label' => $labelsModel,
            'issue' => $issueArticle
        ];
    }

    public function getPropertyNumber()
    {
        if($this['articleType'] !== 'issue') {
            return false;
        }

        // Check if we have labels.
        if(!isset($this['link']['label']) || !count($this['link']['label'])) {
            return false;
        }

        $labels = array_map(function($label) {
            $matches = null;
            if(preg_match('/(\d+)/', $label['name'], $matches) === 1) {
                $label['number'] = $matches[1];
            }

            return $label;
        }, $this['link']['label']);
        $labels = array_filter($labels, function($label) {
            return isset($label['number']);
        });

        if(!count($labels)) {
            return false;
        }

        $label = array_shift($labels);
        return $label['number'];
    }

    public function getPropertyOpinion()
    {
        // Return opinion label if present, null if not
        $label = $this->getLabel('publication', 'opinion:');

        if (!isset($label['_id'])) {
            return false;
        }

        $search = new SearchModel($this->container);
        $search->setState([
            'label' => [$label['_id']],
            'type' => 'article.issue',
            'limit' => 1
        ]);
        $issueArticle = $search->fetch();


        // The fetch function always returns an array, so we will check if we have an instance,
        // If so we will need that instance, if there is nothing, we will set the value to false,
        // This way twig doesn't break.
        if (count($issueArticle) >= 1) {
            $issueArticle = array_shift($issueArticle);
        } else {
            $issueArticle = false;
        }
        $labelsModel = new LabelsModel($this->container);
        $labelsModel->setData($label);

        return [
            'label' => $labelsModel,
            'issue' => $issueArticle
        ];
    }

    public function getPropertyBlog()
    {
        // Return blog label if present, null if not
        $blog = null;

        foreach ($this['link']['label'] as $label) {
            if (stripos($label['name'], 'blog') !== false) {
                $blog = $label;
                break;
            }
        }

        return $blog;
    }

    private function getLabel(string $type, string $needle) : array
    {
        // Return the first label that is as we defined it.
        $foundLabel = [];

        foreach ($this['link']['label'] as $label) {
            if ($label['type'] == $type) {
                if (stripos($label['name'], $needle) !== false) {
                    $foundLabel = $label;
                    // break;
                }
            }
        }

        return $foundLabel;
    }
}

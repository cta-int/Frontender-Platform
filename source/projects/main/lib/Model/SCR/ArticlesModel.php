<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Frontender\Platform\Model\SCR;

use Frontender\Platform\Model\Traits\Projectable;
use Slim\Container;
use Frontender\Platform\Model\Traits\Imagable;
use Frontender\Platform\Model\SCR\MediaModel;
use Frontender\Core\DB\Adapter;
use Frontender\Core\Template\Filter\Translate;
use Frontender\Platform\Model\SCR\Article\SearchModel;
use Frontender\Platform\Model\Utils\Sorting;
use Doctrine\Common\Inflector\Inflector;

class ArticlesModel extends ScrModel
{
    use Projectable;
    use Imagable;

    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->getState()
            ->insert('id', null, true)
            ->insert('limit', 20)
            ->insert('skip')
            ->insert('language', $this->container->language->get())
            ->insert('analysis')
            ->insert('recursive', true)
            ->insert('includeRelated', false)
            ->insert('related_limit', 16)
            ->insert('similar_limit', 16)
            ->insert('articleLimit', 20);
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
            'doc_id' => $this->getState()->id,
            'limit' => $this->getState()->related_limit
        ])->fetch();

        $state = $this->getState();
        $container = $this->container;

        $issues = array_filter($related['articles'], function($article) {
            return $article['articleType'] == 'issue';
        });

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
            }, $related['persons']),
            'issues' => array_map(function($issue) use ($state, $container) {
                $model = new ArticlesModel($container);
                return $model
                    ->setState($state->getValues())
                    ->setData($issue);
            }, $issues)
        ];
    }

    public function getPropertySimilar()
    {
        return new class ($this, $this->getState(), $this->container)
        {
            private $cachedEvents = null;
            private $cachedIssueArticles = null;
            private $cachedArticles = null;
            private $container;
            private $state;

            public function __construct($article, $state, $container)
            {
                $this->article = $article;
                $this->state = $state;
                $this->container = $container;
            }

            public function articles($config = [])
            {
                $config = array_merge([
                    'limit' => 20
                ], $config);

                if (!$this->cachedArticles) {
                    $labels = $this->article['link']['label'];

                    if (isset($config['label'])) {
                        $labels = $this->article->getLabels($config['label']);
                    }

                    if (!count($labels)) {
                        return false;
                    }

                    $articles = new \Frontender\Platform\Model\SCR\Article\SearchModel($this->container);
                    $articles->setState([
                        'label' => array_map(function ($label) {
                            return $label['_id'];
                        }, $labels),
                        'limit' => $config['limit'],
                        'language' => $this->state->language ?? 'en',
                        'mustNot' => [[
                            'type' => 'field',
                            'id' => '_id',
                            'value' => $this->article['_id']
                        ]]
                    ]);

                    $this->cachedArticles = $articles->fetch();
                    usort($this->cachedArticles, function ($a, $b) {
                        $aDatePublished = new \DateTime($a['datePublished']);
                        $bDatePublished = new \DateTime($b['datePublished']);
                        if ($aDatePublished == $bDatePublished) {
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

                    $articles = new \Frontender\Platform\Model\SCR\Article\SearchModel($this->container);
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

            public function issues($config = [])
            {
                $config = array_merge([
                    'limit' => 4,
                    'label' => 'publication'
                ], $config);

                if (!$this->cachedIssueArticles) {
                    $labels = $this->article->getLabels($config['label']);

                    if (!count($labels)) {
                        return false;
                    }

                    $articles = new \Frontender\Platform\Model\SCR\Article\SearchModel($this->container);
                    $articles->setState([
                        'label' => array_map(function ($label) {
                            return $label['_id'];
                        }, $labels),
                        'limit' => $config['limit'],
                        'language' => $this->state->language ?? 'en',
                        'articleType' => 'article.issue',
                        'mustNot' => [[
                            'type' => 'field',
                            'id' => '_id',
                            'value' => $this->article['_id']
                        ]]
                    ]);
                    $result = $articles->fetch();
                    $this->cachedIssueArticles = Sorting::sortBy($result, 'datePublished', Sorting::$DIRECTION_DESC);
                }

                return $this->cachedIssueArticles;
            }
        };
    }

    /*
     * Method to retrieve a related issue and its assets.
     * If the current article is an issue (i.e. if it has an issue label) that is returned.
     *
     */
    public function getPropertyIssue()
    {
        $article = new class ($this, $this->getState(), $this->container)
        {
            public $cachedIssue = null;
            private $cachedIssues = null;
            private $cachedIssueArticles = null;
            private $cachedIssueInterview = null;
            private $container;
            private $state;
            private $issueLabel = null;
            public $issueNumberLabel = null;

            public function __construct($article, $state, $container)
            {
                $this->state = $state;
                $this->container = $container;
                // getIssue() also sets issueLabel and issueNumberLabel
                $this->cachedIssue = $this->getIssue($article);
            }

            private function getIssue($article)
            {
                // If the current article IS the issue, return it, otherwise get the correct issue.
                // If the article has both an issue label AND an issue label with a nr (#123) it is an issue itself.
                // If the article only has an issue label with a nr (#123) it is an article that belongs to an issue.

                if (!$this->cachedIssue) {
                    if ($this->isIssue($article)) {
                        $this->cachedIssue = $article;
                    } else {
                        $this->cachedIssue = $this->getArticleIssue($article);
                    }
                }
                return $this->cachedIssue;
            }

            private function getArticleIssue($article)
            {
                if (is_null($this->issueNumberLabel)) {
                    // This is not an issue
                    return false;
                }

                $articles = new \Frontender\Platform\Model\SCR\Article\SearchModel($this->container);
                $articles->setState([
                    'label' => array_filter([$this->issueLabel['_id'] ?? false, $this->issueNumberLabel['_id'] ?? false]),
                    'limit' => 1,
                    'language' => $this->state->language ?? 'en',
                    'articleType' => 'article.issue'
                ]);
                $result = $articles->fetch();

                return array_shift($result);
            }

            public function isIssue($article)
            {
                $issue_labels = $article->getLabels('issue');

                if (empty($issue_labels)) {
                    // This is not an issue, nor an issue article
                    return false;
                }

                // Just get the names, we're only using them to determine 
                // if we're dealing with an issue or an issue article.
                $flat_labels = array_map(function ($label) {
                    return $label['name'];
                }, $issue_labels);

                $issue_name = '';
                $issue_no = '';
                $issue_label = '';
                $label_type = '';

                foreach ($flat_labels as $index => $label) {

                    // We only want to inspect if there is not more than two parts to the label (i.e. Issue/Spore #182/Something else).
                    if (count(explode('/', $label)) > 2) {
                        continue;
                    }

                    // Does this label contain the issue number?
                    if (strpos($label, '#')) {

                        // Get the issue name, we use it to cross check if both labels are 
                        // for the same publications (i.e. Spore or ICT Update)
                        list($issue_label, $issue_no) = explode('#', $label);
                        list($label_type, $issue_name) = explode('/', $issue_label);

                        $issue_no = trim($issue_no);
                        $issue_name = trim($issue_name);

                        // Set the issue number label so we can use it again
                        $this->issueNumberLabel = $issue_labels[$index];

                        break; // Stop after the first issue number; we're not supporting multiple issues for a single article at the moment
                    } else {
                        continue;
                    }
                }

                foreach ($flat_labels as $index => $label) {
                    if ($label == 'Issue/' . $issue_name) {
                        $this->issueLabel = $issue_labels[$index];
                        // This article is THE issue
                        return $article;
                    }
                }

                return false;
            }

            public function articles($config = [])
            {
                $config = array_merge([
                    'limit' => 20
                ], $config);

                if (!$this->cachedIssueArticles) {

                    $articles = new \Frontender\Platform\Model\SCR\Article\SearchModel($this->container);
                    $articles->setState([
                        'label' => [$this->issueNumberLabel['_id']],
                        'limit' => $config['limit'],
                        'language' => $this->state->language ?? 'en'
                    ]);

                    if ($this->interview()) {
                        $articles->setState([
                            'mustNot' => [[
                                'type' => 'field',
                                'id' => '_id',
                                'value' => $this->cachedIssue['_id']
                            ], [
                                'type' => 'field',
                                'id' => '_id',
                                'value' => $this->cachedIssueInterview['_id']
                            ]]
                        ]);
                    } else {
                        $articles->setState([
                            'mustNot' => [[
                                'type' => 'field',
                                'id' => '_id',
                                'value' => $this->cachedIssue['_id']
                            ]]
                        ]);
                    }

                    $this->cachedIssueArticles = $articles->fetch();
                    $this->cachedIssueArticles = Sorting::sortBy($this->cachedIssueArticles, 'datePublished', Sorting::$DIRECTION_DESC);
                }

                return $this->cachedIssueArticles;
            }

            public function interview($config = [])
            {
                if (!$this->cachedIssueInterview) {

                    $articles = new \Frontender\Platform\Model\SCR\Article\SearchModel($this->container);
                    $articles->setState([
                        'label' => [$this->issueNumberLabel['_id']],
                        'limit' => 1,
                        'language' => $this->state->language ?? 'en',
                        'articleType' => 'article.interview'
                    ]);

                    $result = $articles->fetch();

                    $this->cachedIssueInterview = array_shift($result);
                }

                return $this->cachedIssueInterview;
            }

            public function issues($config = [])
            {
                $config = array_merge([
                    'limit' => 4
                ], $config);

                if (!$this->cachedIssues) {

                    $articles = new \Frontender\Platform\Model\SCR\Article\SearchModel($this->container);
                    $articles->setState([
                        'label' => [$this->issueLabel['_id']],
                        'limit' => $config['limit'],
                        'language' => $this->state->language ?? 'en',
                        'articleType' => 'article.issue',
                        'mustNot' => [[
                            'type' => 'field',
                            'id' => '_id',
                            'value' => $this->cachedIssue['_id']
                        ]]
                    ]);

                    $this->cachedIssues = $articles->fetch();
                    $this->cachedIssues = Sorting::sortBy($this->cachedIssues, 'datePublished', Sorting::$DIRECTION_DESC);
                }

                return $this->cachedIssues;
            }
        };

        if (!$article->cachedIssue) {
            return false;
        }

        return $article;
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
                try {
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
                } catch(\Exception $e) {
                    // NOOP
                } catch(\Error $e) {
                    // NOOP
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

    public function getPropertyPath(): string
    {
        if (isset($this['link']['label']) && count($this['link']['label'])) {

            $labels = array_map(function ($label) {
                $route = Adapter::getInstance()->collection('routes')->findOne([
                    'resource' => ['$regex' => 'scr\/label[\\\/].*?' . $label['_id'], '$options' => 'i']
                ]);
                $label['score'] = count(explode('/', $label['name']));

                if ($route) {
                    $label['route'] = $route['destination'];
                }

                return $label;
            }, $this['link']['label']);

            $labels = array_filter($labels, function ($label) {
                return isset($label['route']) && !empty($label['route']);
            });

            // I now need to sort the routes, the most specific route will be used,
            // the score is calculated at the amount or parts it has.
            uasort($labels, function ($a, $b) {
                if ($a['score'] < $b['score']) {
                    return 1;
                } else if ($b['score'] < $a['score']) {
                    return -1;
                }

                return 0;
            });

            $prefix = array_shift($labels);

            // Use the translated value.
            $filter = new Translate($this->container);
            $prefix = $filter->translate($prefix['route']);
        }

        return $this->getPageRoute($prefix ?? '');
    }

    private function getPageRoute($prefix = '')
    {
        $name = Inflector::singularize($this->getModelName());

        if ($this['issue'] && $this['issue']->isIssue($this)) {
            $name = 'issue';
        } elseif ($this['articleType'] == 'issue') {
            $name = 'issue';
        }

        $parts = preg_split('/(?=[A-Z])/', $name);
        $parts = array_filter([$prefix, strtolower(end($parts))]);
        $parts = implode('/', $parts);

        return trim($parts, '/');
    }

    public function getLabels($types = [], bool $first = false)
    {
        $_labels = $this->data['link']['label'];

        // Make compatible with older requests that set type as a string
        if (is_string($types)) {
            $types = [$types];
        }

        if ($types[0]) {
            foreach ($_labels as $key => $value) {
                if (!in_array($value['type'], $types)) {
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

    public function getPropertyStrategy()
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
        switch ($this->container->language->get()) {
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
        if ($this['articleType'] !== 'issue') {
            return false;
        }

        // Check if we have labels.
        if (!isset($this['link']['label']) || !count($this['link']['label'])) {
            return false;
        }

        $labels = array_map(function ($label) {
            $matches = null;
            if (preg_match('/(\d+)/', $label['name'], $matches) === 1) {
                $label['number'] = $matches[1];
            }

            return $label;
        }, $this['link']['label']);
        $labels = array_filter($labels, function ($label) {
            return isset($label['number']);
        });

        if (!count($labels)) {
            return false;
        }

        $label = array_shift($labels);
        return $label['number'];
    }

    public function getPropertyOpinion()
    {
        // Return opinion label if present, null if not
        switch ($this->container->language->get()) {
            case 'fr-FR':
                $needle = 'opinion :';
                continue;
            case 'en-GB':
            default:
                $needle = 'opinion:';
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

    private function getLabel(string $type, string $needle): array
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

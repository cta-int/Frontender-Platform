<?php

namespace Frontender\Platform\Model\SCR;

use Slim\Container;
use Frontender\Platform\Model\SCR\Article\SearchModel;
use Frontender\Platform\Model\Utils\Sorting;

class LabelsModel extends ScrModel
{
    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->getState()
            ->insert('id', null, true)
            ->insert('limit', 20)
            ->insert('skip')
            ->insert('articleLimit', 20);
    }

    public function getPropertyTheme()
    {
        if (!isset($this->container['theme-color'])) {
            $this->container['theme-color'] = json_decode(file_get_contents(__DIR__ . '/Label/SearchModel.json'), true);
        }

        return [
            'selector' => isset($this->container['theme-color'][$this['type']]['theme-color'][$this['_id']]) ? $this->container['theme-color'][$this['type']]['theme-color'][$this['_id']] : ''
        ];
    }

    public function getPropertyDisplay_name()
    {
        $name = $this['name'];
        $language = $this->container->language->get();

        if (is_array($name) && isset($name[$language])) {
            $name = $name[$language];
        }

        $parts = explode('/', $name);
        return array_pop($parts);
    }

    public function getPropertyIssues()
    {
        $search = new SearchModel($this->container);
        $search->setState([
            'label' => [$this['_id']],
            'type' => 'article.issue',
            'limit' => $this->getState()->articleLimit ?? false
        ]);

        $result = $search->fetch();

        return Sorting::sortBy($result, 'datePublished', Sorting::$DIRECTION_DESC);
    }

    public function getPropertyArticles()
    {
        $search = new SearchModel($this->container);
        $search->setState([
            'label' => [$this['_id']],
            'limit' => $this->getState()->articleLimit ?? false,
            'skip' => $this->getState()->skip
        ]);

        $result = $search->fetch();

        return Sorting::sortBy($result, 'datePublished', Sorting::$DIRECTION_DESC);
    }

    public function getPropertyArticlesTotal()
    {
        $search = new SearchModel($this->container);
        $search->setState([
            'label' => [$this['_id']],
            'limit' => 1
        ]);

        $result = $search->fetch(true);

        return $result['total'];
    }

    public function fetch($raw = false)
    {
        if (is_array($this->getState()->id)) {
            $container = $this->container;
            $labels = array_map(function ($labelID) use ($raw, $container) {
                try {
                    $model = new LabelsModel($container);
                    $model->setState([
                        'id' => $labelID
                    ]);
                    $result = $model->fetch($raw);
                    return array_shift($result);
                } catch (\Error $e) {
                    return null;
                } catch (\Exception $e) {
                    return null;
                }
            }, $this->getState()->id);
            $labels = array_filter($labels);
            $labels = array_values($labels);
        } else {
            $labels = parent::fetch($raw);
        }

        if ($raw) {
            return $labels;
        }

        foreach ($labels as $label) {
            $label['name'] = $this->translate($label['name']);
        }

        return $labels;
    }
}

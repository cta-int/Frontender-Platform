<?php

namespace Prototype\Model\SCR;

use Slim\Container;
use Prototype\Model\SCR\Article\SearchModel;

class LabelsModel extends ScrModel
{
    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->getState()
            ->insert('id', null, true)
            ->insert('articleLimit');
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

        return $search->fetch();
    }

    public function getPropertyArticles()
    {
        $search = new SearchModel($this->container);
        $search->setState([
            'label' => [$this['_id']],
            'limit' => $this->getState()->articleLimit ?? false
        ]);

        $result = $search->fetch();

        usort($result, function ($a, $b) {
            $aDatePublished = new \DateTime($a['datePublished']);
            $bDatePublished = new \DateTime($b['datePublished']);
            if ($aDatePublished == $bDatePublished) {
                return 0;
            }
            return $aDatePublished > $bDatePublished ? -1 : 1;
        });

        return $result;
    }

    public function fetch($raw = false)
    {
        $labels = parent::fetch($raw);

        if ($raw) {
            return $labels;
        }

        foreach ($labels as $label) {
            $label['name'] = $this->translate($label['name']);
        }

        return $labels;
    }
}

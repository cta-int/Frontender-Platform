<?php

namespace Prototype\Model\SCR;

use Slim\Container;
use Prototype\Model\SCR\Article\SearchModel;

class LabelsModel extends ScrModel
{
    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->getState()->insert('id', null, true);
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
        $parts = explode('/', $this['name']);
        return array_pop($parts);
    }

    public function getPropertyIssues()
    {
        $search = new SearchModel($this->container);
        $search->setState([
            'label' => [$this['_id']],
            'type' => 'article.issue'
        ]);

        return $search->fetch();
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

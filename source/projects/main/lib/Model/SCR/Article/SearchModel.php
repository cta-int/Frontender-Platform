<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Prototype\Model\SCR\Article;

use Prototype\Model\Traits\Searchable;
use Slim\Container;
use Prototype\Model\SCR\ArticlesModel;

class SearchModel extends ArticlesModel
{
    use Searchable {
        __construct as public traitConstruct;
        setState as public traitSetState;
    }

    public function __construct(\Slim\Container $container)
    {
        $this->traitConstruct($container);

        $this->getState()
            ->insert('articleType');
    }

    public function setState(array $values)
    {
        if (isset($values['articleType'])) {
            $values['type'] = $values['articleType'];
            unset($values['articleType']);
        }

        $this->traitSetState($values);
    }

    public function fetch($raw = false)
    {
        $container = $this->container;
        $state = $this->getState()->getValues();
        $response = parent::fetch(true);

        if ($raw) {
            return $response;
        }

        return array_map(function ($item) use ($container, $state) {
            $article = new ArticlesModel($container);
            $article->setState($state);
            $article->setData($item);

            return $article;
        }, $response['items']);
    }

    public function getModelName() : string
    {
        $name = parent::getModelName();
        return 'Articles' . ucfirst($name);
    }
}

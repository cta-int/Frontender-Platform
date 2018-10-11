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
    use Searchable;

    public function fetch($raw = false)
    {
        if (strpos($this->getState()->type, 'article.') === 0 || !$this->getState()->type) {
            $container = $this->container;
            $state = $this->getState()->getValues();

            return array_map(function ($item) use ($container, $state) {
                $article = new ArticlesModel($container);
                $article->setState($state);
                $article->setData($item);

                return $article;
            }, parent::fetch(true));
        }

        return false;
    }

    public function getName() : string
    {
        $name = parent::getName();
        return 'Articles' . ucfirst($name);
    }
}

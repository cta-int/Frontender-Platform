<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Prototype\Model\SCR\Article;

use Slim\Container;

class ArticlesModel extends \Prototype\Model\SCR\ArticlesModel
{
    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->getState()
            ->insert('id', null)
            ->insert('bookmarked', 'false')
            ->insert('explain', 'false')
            ->insert('language', $this->container->language->get())
            ->insert('limit')
            ->insert('skip');
    }

    public function getName() : string
    {
        $name = parent::getName();

        return 'Article' . ucfirst($name);
    }
}

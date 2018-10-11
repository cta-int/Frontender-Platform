<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Prototype\Model\SCR\Event;

use Prototype\Model\SCR\ScrModel;
use Slim\Container;

class EventsModel extends ScrModel
{
    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->getState()
            ->insert('id', null)
            ->insert('explain', 'false')
            ->insert('language', $this->container->language->get())
            ->insert('limit')
            ->insert('skip');
    }

    public function getName() : string
    {
        $name = parent::getName();

        return 'Event' . ucfirst($name);
    }
}

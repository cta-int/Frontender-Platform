<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Prototype\Model\SCR\Event;

use Prototype\Model\SCR\ScrModel;
use Prototype\Model\Traits\Imagable;
use Prototype\Model\Traits\Searchable;
use Slim\Container;

class SearchModel extends ScrModel
{
    use Searchable;
    use Imagable;


    public function getName() : string
    {
        $name = parent::getName();
        return 'Events' . ucfirst($name);
    }

    public function fetch($raw = false)
    {
        if (strpos($this->getState()->type, 'event.') === 0 || !$this->getState()->type) {
            $container = $this->container;
            $state = $this->getState()->getValues();

            return array_map(function ($item) use ($container, $state) {
                $event = new EventsModel($container);
                $event->setState($state);
                $event->setData($item);

                return $event;
            }, parent::fetch(true));
        }

        return false;
    }
}

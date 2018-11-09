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
    use Searchable {
        __construct as parentConstruct;
        setState as parentSetState;
    }
    use Imagable;

    public function __construct(Container $contianer)
    {
        $this->parentConstruct($contianer);

        $this->getState()
            ->insert('upcoming');
    }

    public function setState(array $values)
    {
        if (isset($values['upcoming'])) {
            if ($values['upcoming'] === 'true') {
                $values['from'] = date('c');
                $values['to'] = date('c', strtotime('+5 year'));
            } else {
                $values['from'] = date('c', strtotime('-5 year'));
                $values['to'] = date('c');
            }
        }

        return $this->parentSetState($values);
    }

    public function getModelName() : string
    {
        $name = parent::getModelName();
        return 'Events' . ucfirst($name);
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
            $event = new EventsModel($container);
            $event->setState($state);
            $event->setData($item);

            return $event;
        }, $response['items']);
    }
}

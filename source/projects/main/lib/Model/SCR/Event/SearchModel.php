<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Frontender\Platform\Model\SCR\Event;

use Frontender\Platform\Model\SCR\ScrModel;
use Frontender\Platform\Model\SCR\EventsModel;
use Frontender\Platform\Model\Traits\Imagable;
use Frontender\Platform\Model\Traits\Searchable;
use Slim\Container;

class SearchModel extends ScrModel
{
    use Searchable {
        __construct as parentConstruct;
        setState as parentSetState;
    }
    use Imagable;

    public function __construct(Container $container)
    {
        $this->parentConstruct($container);

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
            $event->setState(array_merge($state, [
                'id' => $item['_id']
            ]));
            $event->setData($item);

            return $event;
        }, $response['items']);
    }
}

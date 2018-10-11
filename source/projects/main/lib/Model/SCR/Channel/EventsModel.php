<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Prototype\Model\SCR\Channel;

use Prototype\Model\SCR\ChannelsModel;
use Prototype\Model\SCR\LinksModel;
use Slim\Container;

class EventsModel extends AbstractModel
{
    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->getState()
            ->insert('language', $this->container->language->get())
            ->insert('format', 'scr')
            ->insert('timezoneOffset')
            ->insert('upcoming', 'true')
            ->insert('includeCancelled', 'false')
            ->insert('start')
            ->insert('end')
            ->insert('sortDirection', 'asc')
            ->insert('sortProperty', 'startDateUTC')
            ->insert('eventType')
            ->insert('includeRelated', false);
    }

    public function setState(array $values)
    {
        if (isset($values['upcoming']) && $values['upcoming'] == '1') {
            $values['upcoming'] = 'true';
        }

        if (isset($values['upcoming']) && $values['upcoming'] == '0') {
            $values['upcoming'] = 'false';
        }

        if (isset($values['includeRelated']) && $values['includeRelated'] == '1') {
            $values['includeRelated'] = 'true';
        }

        if (isset($values['includeRelated']) && $values['includeRelated'] == '0') {
            $values['includeRelated'] = 'false';
        }

        return parent::setState($values);
    }

    public function getPropertyChannel()
    {
        $channelModel = new ChannelsModel($this->container);
        $channelModel->setState([
            'id' => $this->getState()->id
        ]);
        $channel = $channelModel->fetch();

        return $channel[0];
    }
}

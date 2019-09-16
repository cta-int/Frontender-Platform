<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Frontender\Platform\Model\SCR\Channel;

use Slim\Container;

class PersonsModel extends \Frontender\Platform\Model\SCR\PersonsModel
{
    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->getState()
            ->insert('id', null)
            ->insert('limit', 20)
            ->insert('skip');
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

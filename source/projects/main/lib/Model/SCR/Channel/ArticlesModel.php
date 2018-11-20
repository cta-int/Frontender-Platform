<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Prototype\Model\SCR\Channel;

use Prototype\Model\SCR\ChannelsModel;
use Prototype\Model\SCR\MediaModel;
use Slim\Container;

class ArticlesModel extends \Prototype\Model\SCR\ArticlesModel
{
    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->getState()
            ->insert('id', null, false)
            ->insert('language', $this->container->language->get())
            ->insert('format', 'scr')
            ->insert('sortDirection', 'desc')
            ->insert('sortProperty', 'datePublished')
            ->insert('articleType')
            ->insert('imageWeight')
            ->insert('includeRelated', false);
    }

    public function setState(array $values)
    {
        if (isset($values['articleType'])) {
            $values['articleType'] = str_replace('article.', '', $values['articleType']);
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

    public function getModelName() : string
    {
        return 'Channel' . ucfirst(parent::getModelName());
    }
}

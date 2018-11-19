<?php

namespace Prototype\Model\SCR;

use Slim\Container;

class LabelsModel extends ScrModel
{
    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->getState()->insert('id', null, true);
    }

    public function getPropertyTheme()
    {
        if (!isset($this->container['theme-color'])) {
            $this->container['theme-color'] = json_decode(file_get_contents(__DIR__ . '/Label/SearchModel.json'), true);
        }

        return [
            'selector' => isset($this->container['theme-color'][$this['type']]['theme-color'][$this['_id']]) ? $this->container['theme-color'][$this['type']]['theme-color'][$this['_id']] : ''
        ];
    }
}

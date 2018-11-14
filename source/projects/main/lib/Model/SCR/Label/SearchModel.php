<?php

namespace Prototype\Model\SCR\Label;

use Prototype\Model\SCR\ScrModel;
use Prototype\Model\Traits\Searchable;
use Prototype\Model\SCR\LabelsModel;
use Prototype\Model\Traits\Translatable;

class SearchModel extends ScrModel
{
    use Searchable;
    use Translatable;

    public function getModelName() : string
    {
        $name = parent::getModelName();
        return 'Label' . ucfirst($name);
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
            $label = new LabelsModel($container);
            $label->setState($state);

            $item['name'] = $this->translate($item['name']);

            $label->setData($item);

            return $label;
        }, $response['items']);
    }
}
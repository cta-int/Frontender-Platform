<?php

namespace Prototype\Model\SCR\Label;

use Prototype\Model\SCR\ScrModel;
use Prototype\Model\Traits\Searchable;
use Prototype\Model\SCR\LabelsModel;
use Prototype\Model\Traits\Translatable;
use Slim\Container;

class SearchModel extends ScrModel
{
    use Searchable {
        __construct as public traitConstruct;
    }
    use Translatable;

    public function __construct(Container $container) {
        $this->traitConstruct($container);

        $this->getState()
            ->insert('articleLimit');
    }

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
        $fields = [];

        // Check if we have an id in the must fields.
        if (isset($state['must']) && is_array($state['must'])) {
            $fields = array_filter($state['must'], function ($state) {
                return $state['id'] == '_id';
            });
        }

        if (count($fields)) {
            $id = array_column($fields, 'value');

            $label = new LabelsModel($container);
            $label->setState([
                'id' => array_shift($id),
                'articleLimit' => $state['articleLimit'] ?? false
            ]);
            return $label->fetch($raw);
        }

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
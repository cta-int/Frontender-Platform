<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Prototype\Model\SCR;

use Prototype\Model\SCR\ScrModel;
use Prototype\Model\Traits\Imagable;
use Prototype\Model\Traits\Searchable;
use Slim\Container;
use Prototype\Model\SCR\Article\SearchModel;

class ProjectsModel extends EventsModel
{
    public function getPropertyUpdates()
    {
        $labels = isset($this['label']) && is_array($this['label']) ? $this['label'] : [];
        $labels = array_filter($labels, function ($label) {
            return $label['type'] == 'programme';
        });

        // I will hard-code this for now.
        $model = new SearchModel($this->container);
        $model->setState([
            'type' => 'article.blog',
            'limit' => 3,
            'label' => array_map(function ($label) {
                return $label['_id'];
            }, $label)
        ]);
        return $model->fetch();
    }

    public function getModelName() : string
    {
        return 'event';
    }
}

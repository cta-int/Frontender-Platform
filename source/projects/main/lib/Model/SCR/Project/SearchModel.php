<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Frontender\Platform\Model\SCR\Project;

use Frontender\Platform\Model\SCR\ScrModel;
use Frontender\Platform\Model\Traits\Imagable;
use Frontender\Platform\Model\Traits\Searchable;
use Slim\Container;
use Frontender\Platform\Model\SCR\ProjectsModel;

class SearchModel extends \Frontender\Platform\Model\SCR\Event\SearchModel
{
    public function __construct(\Slim\Container $container)
    {
        parent::__construct($container);

        $this->getState()->insert('must', [
            [
                'type' => 'field',
                'id' => 'type',
                'value' => 'Project'
            ]
        ]);
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
            $project = new ProjectsModel($container);

            // Append the current ID to the state, we need it later.
            $project->setState(array_merge($state, [
                'id' => $item['_id']
            ]));
            $project->setData($item);

            return $project;
        }, $response['items']);
    }
}

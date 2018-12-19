<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Prototype\Model\SCR\Project;

use Prototype\Model\SCR\ScrModel;
use Prototype\Model\Traits\Imagable;
use Prototype\Model\Traits\Searchable;
use Slim\Container;
use Prototype\Model\SCR\ProjectsModel;

class SearchModel extends \Prototype\Model\SCR\Event\SearchModel
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

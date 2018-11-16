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

class ProjectsModel extends EventsModel
{
    public function getModelName() : string
    {
        return 'event';
    }
}

<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Prototype\Model\SCR\Media;

use Prototype\Model\SCR\ScrModel;
use Prototype\Model\Traits\Searchable;
use Slim\Container;

class SearchModel extends ScrModel
{
    use Searchable;

    public function getName() : string
    {
        $name = parent::getName();
        return 'Media' . ucfirst($name);
    }
}

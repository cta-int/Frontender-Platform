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
    use Searchable {
        __construct as public traitConstruct;
        setState as public traitSetState;
    }

    public function __construct(\Slim\Container $container)
    {
        $this->traitConstruct($container);

        $this->getState()
            ->insert('searchQuery')
            ->insert('type');
    }

    public function getModelName() : string
    {
        $name = parent::getModelName();
        return 'Media' . ucfirst($name);
    }

    public function setState(array $values)
    {
        if(isset($values['q'])) {
            $values['searchQuery'] = $values['q'];
            unset($values['q']);
        }

        $this->traitSetState($values);

        return $this;
    }
}

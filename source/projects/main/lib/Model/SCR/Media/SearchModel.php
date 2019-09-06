<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Frontender\Platform\Model\SCR\Media;

use Frontender\Platform\Model\SCR\ScrModel;
use Frontender\Platform\Model\Traits\Searchable;
use Frontender\Platform\Model\Traits\Translatable;
use Slim\Container;

class SearchModel extends ScrModel
{
    use Searchable {
        __construct as public traitConstruct;
        setState as public traitSetState;
    }
    use Translatable;

    public function __construct(\Slim\Container $container)
    {
        $this->traitConstruct($container);

        $this->getState()
            ->insert('searchQuery')
            ->insert('type')
            ->insert('limit', 10);
    }

    public function getModelName() : string
    {
        $name = parent::getModelName();
        return 'Media' . ucfirst($name);
    }

    public function setState(array $values)
    {
        if(isset($values['q']) && !empty($values['q'])) {
            $values['searchQuery'] = $values['q'] . '*';
        }

        $this->traitSetState($values);

        return $this;
    }

    public function fetch($raw = false)
    {
        $items = parent::fetch($raw);

        foreach ($items as $item) {
            if (isset($item['title'])) {
                $item['title'] = $this->translate($item['title']);
            }
        }

        return $items;
    }
}

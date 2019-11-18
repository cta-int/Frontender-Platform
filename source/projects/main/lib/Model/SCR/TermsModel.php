<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Frontender\Platform\Model\SCR;

use Slim\Container;
use Frontender\Platform\Model\Traits\Translatable;

class TermsModel extends ScrModel
{
    use Translatable;

    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->getState()
            ->insert('id', null, true);
    }

    public function fetch($raw = false)
    {
        $result = parent::fetch($raw);

        foreach ($result as $item) {
            if (isset($item['label'])) {
                $item['label'] = $this->translate($item['label']);
            }
        }

        return $result;
    }
}

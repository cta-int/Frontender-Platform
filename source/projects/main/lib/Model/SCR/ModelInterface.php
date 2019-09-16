<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Frontender\Platform\Model\SCR;

use Frontender\Platform\Model\State\State;

interface ModelInterface
{
    public function setState(array $values);

    public function getState() : State;

    public function fetch($raw = false);
}

<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Prototype\Model\SCR;

use Prototype\Model\State\State;

interface ModelInterface
{
    public function setState(array $values);

    public function getState() : State;

    public function fetch($raw = false);
}

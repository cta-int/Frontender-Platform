<?php

namespace Prototype\Model\SCR;

use Slim\Container;

class LabelsModel extends ScrModel
{
    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->getState()->insert('id', null, true);
    }
}

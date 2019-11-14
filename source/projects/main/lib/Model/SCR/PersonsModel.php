<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Frontender\Platform\Model\SCR;

use Frontender\Platform\Model\Event\AttendeesModel;
use Slim\Container;

class PersonsModel extends ScrModel
{
    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->getState()
            ->insert('id', null, true)
            ->insert('language', $this->container->language->get())
            ->insert('limit', 20)
	        ->insert('eventLimit', 20)
	        ->insert('articleLimit', 20)
            ->insert('skip');
    }

    public function getPropertyEvents() {
    	$model = new \Frontender\Platform\Model\SCR\Event\SearchModel($this->container);
    	$model->setState([
    		'must' => [
    			'type' => 'person',
			    'id' => $this['_id']
		    ],
		    'limit' => $this->getState()->eventLimit
	    ]);

    	return $model->fetch();
    }

    public function getPropertyArticles() {
    	$model = new \Frontender\Platform\Model\SCR\Article\SearchModel($this->container);
		$model->setState([
			'must' => [
				'type' => 'person',
				'id' => $this['_id']
			],
			'limit' => $this->getState()->articleLimit
		]);

		return $model->fetch();
    }
}

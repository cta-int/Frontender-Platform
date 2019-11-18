<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Frontender\Platform\Model\SCR;

use Frontender\Platform\Model\Event\AttendeesModel;
use Slim\Container;

class PersonsModel extends ScrModel {
	public function __construct( Container $container ) {
		parent::__construct( $container );

		$this->getState()
		     ->insert( 'id', null, true )
		     ->insert( 'language', $this->container->language->get() )
		     ->insert( 'limit', 20 )
		     ->insert( 'skip' );
	}
}

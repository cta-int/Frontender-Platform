<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Frontender\Platform\Model\SCR\Event;

use Frontender\Platform\Model\SCR\ScrModel;
use Slim\Container;

class EventsModel extends ScrModel {
	public function __construct( Container $container ) {
		parent::__construct( $container );

		$this->getState()
		     ->insert( 'id', null )
		     ->insert( 'explain', 'false' )
		     ->insert( 'language', $this->container->language->get() )
		     ->insert( 'limit', 20 )
		     ->insert( 'skip' );
	}

	public function fetch( $raw = false ) {
		$result = parent::fetch( $raw );

		if ( $raw ) {
			return $result;
		}

		return array_map( function ( $item ) {
			$model = new \Frontender\Platform\Model\SCR\EventsModel( $this->container );
			$model->setState( [
				'id' => $item['_id']
			] );
			$model->setData( $item );

			return $model;
		}, $result['items'] ?? [] );
	}

	public function getModelName(): string {
		$name = parent::getModelName();

		return 'Event' . ucfirst( $name );
	}
}

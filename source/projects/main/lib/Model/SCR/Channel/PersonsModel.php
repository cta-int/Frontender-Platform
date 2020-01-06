<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Frontender\Platform\Model\SCR\Channel;

use Slim\Container;

class PersonsModel extends \Frontender\Platform\Model\SCR\PersonsModel {
	public function __construct( Container $container ) {
		parent::__construct( $container );

		$this->getState()
		     ->insert( 'id', null )
		     ->insert( 'limit', 20 )
		     ->insert( 'skip' );
	}

	public function fetch( $raw = false ) {
		$result = parent::fetch( $raw );

		if ( $raw ) {
			return $result;
		}

		return array_map( function ( $item ) {
			$model = new \Frontender\Platform\Model\SCR\PersonsModel( $this->container );
			$model->setState( [
				'id' => $item['_id']
			] );
			$model->setData( $item );

			return $model;
		}, $result['items'] ?? [] );
	}

	public function getPropertyChannel() {
		$channelModel = new ChannelsModel( $this->container );
		$channelModel->setState( [
			'id' => $this->getState()->id
		] );
		$channel = $channelModel->fetch();

		return $channel[0];
	}
}

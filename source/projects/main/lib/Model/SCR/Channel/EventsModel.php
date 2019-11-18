<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Frontender\Platform\Model\SCR\Channel;

use Frontender\Platform\Model\SCR\ChannelsModel;
use Frontender\Platform\Model\SCR\LinksModel;
use Slim\Container;

class EventsModel extends AbstractModel {
	public function __construct( Container $container ) {
		parent::__construct( $container );

		$this->getState()
		     ->insert( 'language', $this->container->language->get() )
		     ->insert( 'format', 'scr' )
		     ->insert( 'timezoneOffset' )
		     ->insert( 'upcoming', 'true' )
		     ->insert( 'includeCancelled', 'false' )
		     ->insert( 'start' )
		     ->insert( 'end' )
		     ->insert( 'sortDirection', 'asc' )
		     ->insert( 'sortProperty', 'startDateUTC' )
		     ->insert( 'eventType' )
		     ->insert( 'includeRelated', false );
	}

	public function setState( array $values ) {
		if ( isset( $values['upcoming'] ) && $values['upcoming'] == '1' ) {
			$values['upcoming'] = 'true';
		}

		if ( isset( $values['upcoming'] ) && $values['upcoming'] == '0' ) {
			$values['upcoming'] = 'false';
		}

		if ( isset( $values['includeRelated'] ) && $values['includeRelated'] == '1' ) {
			$values['includeRelated'] = 'true';
		}

		if ( isset( $values['includeRelated'] ) && $values['includeRelated'] == '0' ) {
			$values['includeRelated'] = 'false';
		}

		if ( isset( $values['eventType'] ) ) {
			$values['eventType'] = str_replace( 'event.', '', $values['eventType'] );
		}

		return parent::setState( $values );
	}

	public function getPropertyChannel() {
		$channelModel = new ChannelsModel( $this->container );
		$channelModel->setState( [
			'id' => $this->getState()->id
		] );
		$channel = $channelModel->fetch();

		return $channel[0];
	}

	public function fetch( $raw = false ) {
		$result = parent::fetch( true );

		if ( $raw ) {
			return $result;
		}

		$container = $this->container;
		$state     = $this->getState()->getValues();

		return array_map( function ( $event ) use ( $container, $state ) {
			$eventsModel = new \Frontender\Platform\Model\SCR\EventsModel( $container );
			$eventsModel->setData( $event );
			$eventsModel->setState( array_merge( $state, [
				'id' => $event['_id']
			] ) );

			return $eventsModel;
		}, $result['items'] );
	}
}

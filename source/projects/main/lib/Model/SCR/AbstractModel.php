<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Frontender\Platform\Model\SCR;

use Frontender\Platform\Model\Traits\Translatable;
use Frontender\Core\DB\Adapter;
use MongoDB\BSON\ObjectId;

abstract class AbstractModel extends \Frontender\Core\Model\AbstractModel {
	use Translatable;

	/**
	 * If no name has been set extract it from the class name.
	 *
	 * @return string
	 */
	public function getModelName(): string {
		if ( ! $this->name ) {
			$path   = explode( '\\', get_called_class() );
			$pieces = preg_split( '/(?=[A-Z])/', end( $path ), - 1, PREG_SPLIT_NO_EMPTY );

			$this->setName( strtolower( array_shift( $pieces ) ) );
		}

		return $this->name;
	}

	public function startLog( $args = [] ) {
		/**
		 * Enable toggle,
		 * csv export must be able to be returned from a url.
		 */

		// If the loggins is disabled. return false.
		if ( ! isset( $this->container->config->request['logging'] ) || ! $this->container->config->request['logging'] ) {
			return false;
		}

		[ $microsecs, $secs ] = explode( ' ', microtime() );

		$response = Adapter::getInstance()->collection( 'log' )->insertOne( array_merge( [
			'model'           => [
				'name'  => get_class( $this ),
				'state' => $this->getState()->getValues()
			],
			'user_session_id' => session_id(),
			'timings'         => [
				'start' => $secs + $microsecs
			]
		], $args ) );

		return $response->getInsertedId();
	}

	public function endLog( $loggingID, $data = [] ) {
		if ( ! $loggingID ) {
			return false;
		}

		if ( ! ( $loggingID instanceof ObjectId ) ) {
			$loggingID = new ObjectId( $loggingID );
		}

		[ $microsecs, $secs ] = explode( ' ', microtime() );
		$logCollection = Adapter::getInstance()->collection( 'log' );
		$original      = $logCollection->findOne( [
			'_id' => $loggingID
		] );

		$logCollection->findOneAndUpdate( [
			'_id' => $loggingID,
		], [
			'$set' => array_merge( [
				'timings.end'      => $secs + $microsecs,
				'timings.duration' => ( $secs + $microsecs ) - $original->timings->start
			], $data )
		] );
	}
}

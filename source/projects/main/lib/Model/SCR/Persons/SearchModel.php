<?php

namespace Frontender\Platform\Model\SCR\Persons;

use Frontender\Platform\Model\SCR\PersonsModel;
use Frontender\Platform\Model\SCR\ScrModel;
use Frontender\Platform\Model\Traits\Translatable;
use Slim\Container;

class SearchModel extends ScrModel {
	use Translatable;

	public function __construct( Container $container ) {
		parent::__construct( $container );

		$this->getState()
		     ->insert( 'q', '' )
		     ->insert( 'id' )
		     ->insert( 'limit', 20 )
		     ->insert( 'skip' );
	}

	public function fetch( $raw = false ) {
		$state     = $this->getState()->getValues();
		$container = $this->container;

		// If we have an ID, we will call the persons model directly, no matter what.
		if ( isset( $state['id'] ) && ! empty( $state['id'] ) ) {
			$model = new PersonsModel( $container );
			$model->setState( $state );

			return $model->fetch( $raw );
		}

		$result = parent::fetch( true );

		if ( $raw ) {
			return $result;
		}

		return array_map( function ( $person ) use ( $state, $container ) {
			if ( isset( $person['jobTitle'] ) && ! empty( $person['jobTitle'] ) ) {
				$person['jobTitle'] = $this->translate( $person['jobTitle'] );
			}

			if ( isset( $person['biography'] ) && ! empty( $person['biography'] ) ) {
				$person['biography'] = $this->translate( $person['biography'] );
			}

			if ( isset( $person['description'] ) && ! empty( $person['description'] ) ) {
				$person['description'] = $this->translate( $person['description'] );
			}

			$item = new PersonsModel( $container );
			$item->setState( array_merge( $state, [
				'id' => $person['_id']
			] ) );
			$item->setData( $person );

			return $item;
		}, $result['items'] );
	}

	public function getModelName(): string {
		$name = parent::getModelName();

		return 'Persons' . ucfirst( $name );
	}
}
<?php

namespace Frontender\Platform\Model\Flickr;

use Slim\Container;

class PhotosetsModel extends AbstractAdapter {
	public function __construct( Container $container ) {
		parent::__construct( $container );

		$this->getState()
		     ->insert( 'id' )
		     ->insert( 'photo_limit', 20 );
	}

	public function fetch( $raw = false ): array {
		$client = $this->getClient();
		$userID = $this->container->config->flickr_userid;

		if ( $this->getState()->id ) {
			$photoset = $client->photosets()->getInfo( $this->getState()->id, $userID );

			$model = new PhotosetsModel( $this->container );
			$model->setData( $photoset );
			$model->setState( [
				'id' => $photoset['id']
			] );

			return [ $model ];
		}

		$photosets = $client->photosets()->getList( $userID );

		if ( ! isset( $photosets['photoset'] ) ) {
			return [ false ];
		}

		return array_map( function ( $photoset ) {
			$model = new PhotosetsModel( $this->container );
			$model->setData( $photoset );
			$model->setState( [
				'id' => $photoset['id']
			] );

			return $model;
		}, $photosets['photoset'] );
	}

	public function getTotal(): int {
		$client    = $this->getClient();
		$userID    = $this->container->config->flickr_userid;
		$photosets = $client->photosets()->getList( $userID );

		return (int) isset( $photosets['total'] ) ? $photosets['total'] : 0;
	}

	public function getPropertyPhotos() {
		$client = $this->getClient();
		$userID = $this->container->config->flickr_userid;
		$photos = $client->photosets()->getPhotos( $this['id'], $userID, null, $this->getState()->photo_limit );

		if(!isset($photos['photo'])) {
			return false;
		}

		return array_map(function($photo) {
			$model = new PhotosModel($this->container);
			$model->setState([
				'id' => $photo['id']
			]);
			$photos = $model->fetch();

			if(!count($photos)) {
				return false;
			}

			return array_shift($photos);
		}, $photos['photo']);
	}
}
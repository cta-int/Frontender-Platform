<?php

namespace Frontender\Platform\Model\Flickr;

use Slim\Container;

class PhotosModel extends AbstractAdapter implements \JsonSerializable {
	public function __construct( Container $container ) {
		parent::__construct( $container );

		$this->getState()
		     ->insert( 'id' )
		     ->insert( 'text' )
		     ->insert( 'sort', 'date-posted-asc' )
		     ->insert( 'limit', 20 )
		     ->insert( 'skip', 0 );
	}

	public function fetch( $raw = false ): array {
		$client  = $this->getClient();
		$photoId = isset( $this->getState()->id ) ? $this->getState()->id : false;

		if ( $photoId ) {
			$result = $client->photos()->getInfo( $this->getState()->id );

			$model = new PhotosModel( $this->container );
			$model->setData( $result );
			$model->setState( [
				'id' => $this->getState()->id
			] );

			return [ $model ];
		} else if ( ! $this->getState()->text ) {
			$result = $client->photos()->getRecent( null, $this->getState()->limit, ( $this->getState()->skip / $this->getState()->limit ) + 1 );
		} else {
			// Do a search request
			$result = $client->photos()->search( [
				'text'     => $this->getState()->text,
				'sort'     => $this->getState()->sort,
				'per_page' => $this->getState()->limit,
				'page'     => ( $this->getState()->skip / $this->getState()->limit ) + 1
			] );
			$result = $result['photo'];
		}

		return array_map( function ( $photo ) {
			$model = new PhotosModel( $this->container );
			$model->setState( [
				'id' => $photo['id']
			] );
			$result = $model->fetch();

			return array_shift( $result );
		}, $result );
	}

	public function getTotal() {
		if ( ! $this->getState()->text ) {
			$result = $this->getClient()->request( 'flickr.photos.getRecent', [
				'extras'   => [],
				'per_page' => 1,
				'page'     => 1
			] );

			if ( ! isset( $result['photos'] ) ) {
				return 0;
			}
		} else {
			$result = $this->getClient()->request( 'flickr.photos.search', [
				'text'     => $this->getState()->text,
				'sort'     => $this->getState()->sort,
				'per_page' => 1,
				'page'     => 1
			] );
		}

		return (int) isset( $result['photos']['total'] ) ? $result['photos']['total'] : 0;
	}

	public function getPropertyMetadata() {
		return new class( $this ) {
			private $model;

			public function __construct( PhotosModel $model ) {
				$this->model = $model;
			}

			public function id() {
				return $this->model['sizes']->get( 'original' );
			}

			public function previewUrl() {
				return $this->model['sizes']->get( 'thumbnail', true );
			}

			public function url() {
				return $this->model['sizes']->get( 'original' );
			}
		};
	}

	public function getPropertyType() {
		return 'flickr';
	}

	public function getPropertySizes() {
		return new class( $this ) implements \ArrayAccess {
			public $sizes;

			public function __construct( PhotosModel $model ) {
				$this->sizes = [];

				try {
					$this->sizes = $model->getClient()->photos()->getSizes( $model['id'] );
				} catch ( \Exception $e ) {
					// NOOP
				}
			}

			public function get( $size, $originalFallback = false ) {
				$photos = array_filter( $this->sizes['size'], function ( $photoSize ) use ( $size ) {
					return strtolower( $photoSize['label'] ) === $size;
				} );

				if ( ! count( $photos ) ) {
					if ( ! $originalFallback ) {
						return false;
					}

					$photos = array_filter( $this->sizes['size'], function ( $size ) {
						return strtolower( $size['label'] ) === 'original';
					} );

					if ( ! count( $photos ) ) {
						return false;
					}
				}

				$size = array_shift( $photos );

				return $size['source'];
			}

			public function __get( $key ) {
				return $this->get( $key, true );
			}

			/**
			 * @inheritDoc
			 */
			public function offsetExists( $offset ) {
				return true;
			}

			/**
			 * @inheritDoc
			 */
			public function offsetGet( $offset ) {
				return $this->{$offset};
			}

			/**
			 * @inheritDoc
			 */
			public function offsetSet( $offset, $value ) {
				return false;
			}

			/**
			 * @inheritDoc
			 */
			public function offsetUnset( $offset ) {
				return true;
			}
		};
	}

	public function jsonSerialize() {
		$data    = $this->data;
		$data['sizes'] = $this['sizes']->sizes['size'];
		$data['metadata'] = [
			'previewUrl' => $this['metadata']->previewUrl(),
			'url' => $this['metadata']->url(),
			'id' => $this['metadata']->id()
		];
		$data['type'] = $this['type'];
		
		return $data;
	}
}
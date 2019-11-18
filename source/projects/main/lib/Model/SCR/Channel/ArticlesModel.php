<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Frontender\Platform\Model\SCR\Channel;

use Frontender\Platform\Model\SCR\ChannelsModel;
use Slim\Container;

class ArticlesModel extends AbstractModel {
	public function __construct( Container $container ) {
		parent::__construct( $container );

		$this->getState()
		     ->insert( 'language', $this->container->language->get() )
		     ->insert( 'format', 'scr' )
		     ->insert( 'sortDirection', 'desc' )
		     ->insert( 'sortProperty', 'datePublished' )
		     ->insert( 'articleType' )
		     ->insert( 'imageWeight' )
		     ->insert( 'includeRelated', false );
	}

	public function setState( array $values ) {
		if ( isset( $values['articleType'] ) ) {
			$values['articleType'] = str_replace( 'article.', '', $values['articleType'] );
		}

		return parent::setState( $values );
	}

	public function fetch( $raw = false ) {
		$result = parent::fetch( true );

		if ( $raw ) {
			return $result;
		}

		return array_map( function ( $item ) {
			$model = new \Frontender\Platform\Model\SCR\ArticlesModel( $this->container );
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

	public function getModelName(): string {
		return 'ChannelArticles';
	}
}

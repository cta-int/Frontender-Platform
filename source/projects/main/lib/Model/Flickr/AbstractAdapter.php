<?php

namespace Frontender\Platform\Model\Flickr;

use Samwilson\PhpFlickr\PhpFlickr;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

class AbstractAdapter extends \Frontender\Core\Model\AbstractModel {
	private $_client;

	public function getClient() {
		if ( ! $this->_client ) {
			$options = $this->getClientOptions();

			$this->_client = new PhpFlickr( $options['apiKey'], $options['secret'] );
			$this->_client->setCache(
				new FilesystemAdapter('', 0, ROOT_PATH . '/cache/flickr')
			);
		}

		return $this->_client;
	}

	protected function getClientOptions(): array {
		return [
			'apiKey' => $this->container->config->flickr_brusselsbriefings_apikey,
			'secret' => $this->container->config->flickr_brusselsbriefings_secret,
			'userID' => $this->container->config->flickr_brusselsbriefings_userid
		];
	}

	public function fetch( $raw = false ): array {
		throw new \Exception( 'This method needs to be overridden!' );
	}
}
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
		     ->insert( 'eventLimit', 4 )
		     ->insert( 'projectLimit', 4 )
		     ->insert( 'articleLimit', 4 )
		     ->insert( 'skip' );
	}

	public function getPropertyEvents($raw = false) {
		$model = new \Frontender\Platform\Model\SCR\Event\SearchModel( $this->container );
		$model->setState( [
			'must'  => [
				[
					'type' => 'person',
					'id'   => $this['_id']
				]
			],
			'mustNot'  => [
				[
					'type' => 'field',
					'id' => 'type',
					'value' => 'Project'
				]
			],
			'limit' => $this->getState()->eventLimit
		] );

		return $model->fetch($raw);
	}

	public function getPropertyEventsTotal() {
		$response = $this->getPropertyEvents(true);

		return $response['total'] ?? 0;
	}

	public function getPropertyProjects($raw = false) {
		$model = new \Frontender\Platform\Model\SCR\Project\SearchModel( $this->container );
		$model->setState( [
			'must'  => [
				[
					'type' => 'person',
					'id'   => $this['_id']
				],
				[
					'type' => 'field',
					'id' => 'type',
					'value' => 'Project'
				]
			],
			'limit' => $this->getState()->eventLimit
		] );

		return $model->fetch($raw);
	}

	public function getPropertyProjectsTotal() {
		$response = $this->getPropertyProjects(true);

		return $response['total'] ?? 0;
	}

	public function getPropertyArticles($raw = false) {
		$model = new \Frontender\Platform\Model\SCR\Article\SearchModel( $this->container );
		$model->setState( [
			'must'    => [
				[
					'type' => 'person',
					'id'   => $this['_id']
				]
			],
			'mustNot' => [[
				'type' => 'label',
				'id'   => 'f47307e1-8703-4759-9b99-1f0c46cadc63'
			]],
			'limit'   => $this->getState()->articleLimit
		] );

		return $model->fetch($raw);
	}

	public function getPropertyArticlesTotal() {
		$response = $this->getPropertyArticles(true);

		return $response['total'] ?? 0;
	}

	public function getPropertyPublications($raw = false) {
		$model = new \Frontender\Platform\Model\SCR\Article\SearchModel( $this->container );
		$model->setState( [
			'must' => [
				[
					'type' => 'person',
					'id'   => $this['_id']
				],
				[
					'type'  => 'field',
					'id'    => 'articleType',
					'value' => 'issue'
				],
				[
					'type' => 'label',
					'id'   => 'f47307e1-8703-4759-9b99-1f0c46cadc63'
				]
			]
		] );

		return $model->fetch($raw);
	}

	public function getPropertyPublicationsTotal() {
		$response = $this->getPropertyPublications(true);

		return $response['total'] ?? 0;
	}

	public function getPropertyPath(): string {
		return 'profile';
	}

	public function getPropertyEvent() {
		return new class($this) {
			public function __construct($person) {
				$this->person = $person;
			}

			public function roles($event) {
				// Return this roles in an array of strings
				// The author.roles_to_string macro will translate each
				$roles = ['chair', 'keynote', 'speaker', 'panellist', 'moderator', 'facilitateur', 'pressofficer', 'socialreporter'];
				$active_roles = array();

				foreach($roles as $role) {
					if(isset($event[$role]) && is_array($event[$role])) {
						$ids = array_column($event[$role], '_id');

						if(in_array($this->person['_id'], $ids)) {
							array_push($active_roles, ucfirst($role));
						}
					}
				}

				return $active_roles;
			}
		};
	}

	public function getPropertyProject() {
		return new class($this) {
			public function __construct($person) {
				$this->person = $person;
			}

			public function roles($event) {
				// Return this roles in an array of strings
				// The author.roles_to_string macro will translate each
				$roles = ['officer'];
				$active_roles = array();

				foreach($roles as $role) {
					if(isset($event[$role]) && is_array($event[$role])) {
						$ids = array_column($event[$role], '_id');

						if(in_array($this->person['_id'], $ids)) {
							array_push($active_roles, ucfirst($role));
						}
					}
				}

				return $active_roles;
			}
		};
	}

	public function getPropertyFullname() {
		return implode(' ', [$this['givenName'], $this['familyName']]);
	}
}

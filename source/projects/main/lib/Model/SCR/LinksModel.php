<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Prototype\Model\SCR;

use Slim\Container;
use Doctrine\Common\Inflector\Inflector;

class LinksModel extends ScrModel
{
    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->getState()
            ->insert('doc_type')
            ->insert('doc_id')
            ->insert('limit', 100)
            ->insert('skip', 0);
    }

    public function fetch($raw = false)
    {
        $state = $this->getState();

        $name = $state->isUnique() ? Inflector::singularize($this->getName()) : Inflector::pluralize($this->getName());

        $method = 'get' . ucfirst($name);

        $response = call_user_func_array([$this->adapter, $method], [$state->getValues()]);

        $events = [];
        $articles = [];
        $persons = [];

        foreach ($response['items'] as $type => $link) {
            // Get the current item and check how many items are in there.
            if (count($link[$state->doc_type]) == 2) {
                // If we get in here, than we need to have the first element of the array, this is the correct item.
                $event = array_shift($link[$state->doc_type]);
                $type = Inflector::pluralize($state->doc_type);

                if ($event['_id'] === $state->doc_id) {
                    $$type[] = array_shift($link[$state->doc_type]);
                } else {
                    $$type[] = $event;
                }

            } else if (count($link[$state->doc_type]) == 1) {
            	// Remove our current item.
                unset($link[$state->doc_type]);

                // We will use the article or the person.
                if (array_key_exists('person', $link) && count($link['person'])) {
                    $persons[] = array_shift($link['person']);
                }

                if (array_key_exists('article', $link) && count($link['article'])) {
                    $articles[] = array_shift($link['article']);
                }

                if (array_key_exists('event', $link) && count($link['event'])) {
                    $events[] = array_shift($link['event']);
                }
            }
        }

        return [
            'events' => $events,
            'articles' => $articles,
            'persons' => $persons
        ];
    }
}

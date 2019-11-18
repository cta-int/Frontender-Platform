<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Frontender\Platform\Model\SCR;

//use Frontender\Platform\Object\ObjectArray;

use Teemr\Scr\Client\ScrClient;
use Teemr\Scr\Client\ScrClientFactory;
use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;
use Kevinrob\GuzzleCache\Storage\FlysystemStorage;
use League\Flysystem\Adapter\Local;
use Slim\Container;
use Doctrine\Common\Inflector\Inflector;
use Frontender\Core\DB\Adapter;
use Prototype\Model\Cache\Storage;
use Prototype\Model\Cache\Strategy;

class ScrModel extends AbstractModel
{
    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->adapter = $this->getClient();

        $this->getState()
            ->insert('locale');
    }

    public function getClient() : ScrClient
    {
        $stack = HandlerStack::create();
        $cacheMiddleware = new CacheMiddleware(
            new Strategy(
                new Storage(),
                1800 // This is a faux value as the request is cache in-memory for this request.
            )
        );

        // In the current setup of the SCR we can cache everything.
        $cacheMiddleware->setHttpMethods([
            'GET' => 'GET',
            'POST' => 'POST'
        ]);

        $model = $this;
        $stack->push(function(callable $handler) use ($model) {
            return function ($request, $options) use ($handler, $model) {
                $loggingID = $model->startLog([
                    'page' => [
                        'route' => $model->container->request->getUri()->__toString()
                    ],
                    'request' => [
                        'uri' => $request->getUri()->__toString(),
                        'body' => $request->getBody()->getContents()
                    ]
                ]);

                $request->getBody()->rewind();

                $promise = $handler($request, $options);

                $promise->then(function($response) use ($model, $loggingID) {
                    $model->endLog($loggingID, [
                        'response' => [
                            'cached' => $response->getHeader('X-Kevinrob-Cache')[0],
                            'size' => $response->getHeader('Content-Length')[0]
                        ]
                    ]);
                });

                return $promise;
            };
        });
        $stack->push($cacheMiddleware, 'cache');

        $config = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->container['settings']['scr_token']
            ],
            'handler' => $stack
        ];

        $srcClient = (new ScrClientFactory())->getClient($config);
        $srcClient->setDescription(ROOT_PATH . '/config/src.json');

        return $srcClient;
    }

    public function getTotal()
    {
        $model = clone $this;
        $state = $model->getState()->getValues();

        $state['limit'] = 1;
        $model->setState($state);
        $result = $model->fetch(true);

        if (isset($result['total'])) {
            return $result['total'];
        } else if (isset($result['items'])) {
            return count($result['items']);
        }

        return 0;
    }

    public function fetch($raw = false)
    {
        $state = clone $this->getState();
        $stateValues = $state->getValues();
        $ids = [];

	    // Check the id if it is an array or not.
        if (is_array($state->id)) {
            $ids = $stateValues['id'];
            $stateValues['id'] = null;
            $stateValues['limit'] = 100;

            $name = Inflector::pluralize($this->getModelName());
        } else {
            $name = $state->isUnique() ? Inflector::singularize($this->getModelName()) : Inflector::pluralize($this->getModelName());
        }

        $method = 'get' . ucfirst($name);

        if (isset($stateValues['locale'])) {
            $stateValues['language'] = $stateValues['locale'];
        }

        if (!isset($stateValues['language'])) {
            $stateValues['language'] = $this->container->language->get();
        }

        if (isset($stateValues['language'])) {
            $stateValues['language'] = substr($stateValues['language'], 0, 2);
        }

        $response = call_user_func_array([$this->adapter, $method], [$stateValues]);
        $items = isset($response['items']) ? $response['items'] : [$response->toArray()];

        if ($raw) {
            return $response;
        }

        if (count($ids) > 0) {
            $items = array_values($this->_filterItems($items, $ids));
        }

        $model = $this;
        $container = $this->container;

        return array_map(function ($item) use ($model, $container, $state) {
            $newItem = new $model($container);
            $newItem->setData($item);
            $newItem->setState($state->getValues());

            return $newItem;
        }, $items);
    }

    private function _filterItems($items, $ids)
    {
        return array_filter($items, function ($item) use ($ids) {
            return in_array($item['_id'], $ids);
        });
    }
}

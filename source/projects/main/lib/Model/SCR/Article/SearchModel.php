<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Frontender\Platform\Model\SCR\Article;

use Frontender\Platform\Model\Traits\Searchable;
use Slim\Container;
use Frontender\Platform\Model\SCR\ArticlesModel;
use Frontender\Platform\Model\Utils\Sorting;

class SearchModel extends ArticlesModel
{
    use Searchable {
        __construct as public traitConstruct;
        setState as public traitSetState;
    }

    public function __construct(\Slim\Container $container)
    {
        $this->traitConstruct($container);

        $this->getState()
            ->insert('articleType');
    }

    public function setState(array $values)
    {
        if (isset($values['articleType'])) {
            $values['type'] = $values['articleType'];
            unset($values['articleType']);
        }

        // If type is an array we will modify it into a must query.
        if(isset($values['type']) && is_array($values['type'])) {
            $types = $values['type'];
            unset($values['type']);

            foreach($types as $type) {
                $values['should'] = $values['should'] ?? [];

                $values['should'][] = $this->addTerm('field', 'articleType', str_replace('article.', '', $type));
            }
        }

        $this->traitSetState($values);
    }

    public function fetch($raw = false)
    {
        $container = $this->container;
        $state = $this->getState()->getValues();
        $response = parent::fetch(true);

        if ($raw) {
            return $response;
        }

        $result = array_map(function ($item) use ($container, $state) {
            $article = new ArticlesModel($container);
            $article->setState(array_merge($state, [
                'id' => $item['_id']
            ]));
            $article->setData($item);

            return $article;
        }, $response['items']);

        return Sorting::sortBy($result, 'datePublished', Sorting::$DIRECTION_DESC);
    }

    public function getModelName() : string
    {
        $name = parent::getModelName();
        return 'Articles' . ucfirst($name);
    }
}

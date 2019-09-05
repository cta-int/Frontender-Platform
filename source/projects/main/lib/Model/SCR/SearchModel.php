<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Prototype\Model\SCR;

use Slim\Container;
use Doctrine\Common\Inflector\Inflector;
use Prototype\Model\SCR\Channel\AbstractModel;
use Prototype\Model\Traits\Searchable;

class SearchModel extends ScrModel
{
    use Searchable;

    private $searchFilters = [
        'scope' => [
            'cta' => [
                'mustNot' => [[
                    'type' => 'label',
                    'id' => 'fc2675eb-1e24-47cb-8cc6-2d958b29e739'
                ], [
                    'type' => 'label',
                    'id' => 'bab132fd-314e-4874-8f91-780ece3eab7b'
                ]]
            ],
            'spore' => [
                'must' => [[
                    'type' => 'label',
                    'id' => 'fc2675eb-1e24-47cb-8cc6-2d958b29e739'
                ]]
            ],
            'ictupdate' => [
                'must' => [[
                    'type' => 'label',
                    'id' => 'bab132fd-314e-4874-8f91-780ece3eab7b'
                ]]
            ]
        ],
        'area' => [
            'youth' => [
                'must' => [[
                    'type' => 'label',
                    'id' => '6e7d45e3-fc9f-4010-952c-a336d01bb03d'
                ]]
            ],
            'gender' => [
                'must' => [[
                    'type' => 'label',
                    'id' => '6e7d45e3-fc9f-4010-952c-a336d01bb03d'
                ]]
            ],
            'knowledge-management' => [
                'must' => [[
                    'type' => 'label',
                    'id' => '6e7d45e3-fc9f-4010-952c-a336d01bb03d'
                ]]
            ],
            'nutrition' => [
                'must' => [[
                    'type' => 'label',
                    'id' => '6e7d45e3-fc9f-4010-952c-a336d01bb03d'
                ]]
            ],
            'digitalisation' => [
                'must' => [[
                    'type' => 'label',
                    'id' => '6e7d45e3-fc9f-4010-952c-a336d01bb03d'
                ]]
            ],
            'climate' => [
                'must' => [[
                    'type' => 'label',
                    'id' => '6e7d45e3-fc9f-4010-952c-a336d01bb03d'
                ]]
            ],
        ],
        'theme' => [
            'cooperatives' => [
                'must' => [[
                    'type' => 'label',
                    'id' => '628b89d2-4c68-4a71-a639-045e60e2bce8'
                ]]
            ],
            'market-access' => [
                'must' => [[
                    'type' => 'label',
                    'id' => '898a9d29-1215-463c-93ca-a107220086e9'
                ]]
            ],
            'digitalisation' => [
                'must' => [[
                    'type' => 'label',
                    'id' => '34055746-c1e2-4c56-98cb-aad7ca4a56ee'
                ]]
            ],
            'environment' => [
                'must' => [[
                    'type' => 'label',
                    'id' => '9074b1c4-13bb-4f19-b188-0e19e0ef6491'
                ]]
            ],
            'climate-adaptation' => [
                'must' => [[
                    'type' => 'label',
                    'id' => 'ffe0247e-0898-4e86-9ad9-5e6db75fae7e'
                ]]
            ],
            'food-policy' => [
                'must' => [[
                    'type' => 'label',
                    'id' => '8f66ec97-5e19-49be-a801-913f2c6a825e'
                ]]
            ],
            'food-security' => [
                'must' => [[
                    'type' => 'label',
                    'id' => '46242f97-bd2f-49d2-8a25-163c622acdbf'
                ]]
            ],
            'agritourism' => [
                'must' => [[
                    'type' => 'label',
                    'id' => 'b658ce78-374e-4c76-bf52-b5ba6a3aaf98'
                ]]
            ],
            'fisheries' => [
                'must' => [[
                    'type' => 'label',
                    'id' => '18a2bf9f-c1b1-48b2-b377-5b7d96488da2'
                ]]
            ],
            'value-chains' => [
                'must' => [[
                    'type' => 'label',
                    'id' => '9b43333e-0570-472b-b0e8-8b8ce0fadc59'
                ]]
            ],
            'trade' => [
                'must' => [[
                    'type' => 'label',
                    'id' => '9711057e-fab8-4d05-90ac-359068afb2d8'
                ]]
            ],
            'climate-change' => [
                'must' => [[
                    'type' => 'label',
                    'id' => '797ada64-96b8-4112-90a2-4f5b8a936da6'
                ]]
            ],
            'agribusiness' => [
                'must' => [[
                    'type' => 'label',
                    'id' => 'd81fc233-64c1-49ec-b59b-42088d2414fd'
                ]]
            ],
            'innovation' => [
                'must' => [[
                    'type' => 'label',
                    'id' => 'dd7aacfe-ed78-4c7a-836e-cc870cec3751'
                ]]
            ],
            'employment' => [
                'must' => [[
                    'type' => 'label',
                    'id' => '7b13bc2e-3e43-44e8-9f87-fc975afe7f41'
                ]]
            ],
        ]
    ];

    private $_scopes = [
        'cta' => null,
        'spore' => '',
        'ictupdate' => ''
    ];

    public function __construct(Container $container)
    {
        parent::__construct($container);

        $this->getState()
            ->insert('types') // Can be an array or a string.
            ->insert('q')
            ->insert('language', $this->container->language->get())
//            ->insert('must', 'raw', []) // Can be an object
//            ->insert('should', 'raw', [])  // Can be an object
//            ->insert('mustNot', 'raw', [])
            ->insert('limit', 20)
            ->insert('time')
            ->insert('concepts', [])
            ->insert('to')
            ->insert('from')
            ->insert('location')
            ->insert('must', [])
            ->insert('mustNot', [])
            ->insert('should', [])
            ->insert('type')
            ->insert('skip');  // Can be an object

        /**
         * New states for this model, added for the new search.
         */
        $this->getState()
            ->insert('area')
            ->insert('scope');
    }

    public function setState(array $values)
    {
        $values['must'] = isset($values['must']) && is_array($values['must']) ? $values['must'] : $this->getState()->must;
        $values['should'] = isset($values['should']) && is_array($values['should']) ? $values['should'] : $this->getState()->should;
        $values['mustNot'] = isset($values['mustNot']) && is_array($values['mustNot']) ? $values['mustNot'] : $this->getState()->mustNot;

        // Add mapping for the new search properties.
        if(isset($values['q']) && !empty($values['q'])) {
            $values['should'][] = $this->addTerm('concept', 'http://aims.fao.org/aos/agrovoc/' . $values['q']);
        }

        foreach(['area', 'scope', 'theme'] as $filter) {
            if(isset($values[$filter]) && !empty($values[$filter]) && isset($this->searchFilters[$filter][$values[$filter]])) {
                $values = array_merge_recursive($values, $this->searchFilters[$filter][$values[$filter]]);
            }
        }

        parent::setState($values);
    }

    public function getPropertyArticle()
    {
        $state = $this->getState();
        $types = explode(',', $state->types);

        if (in_array('article', $types)) {
            $model = new \Prototype\Model\SCR\Article\SearchModel($this->container);
            $model->setState($this->getState()->getValues());

            return $model->fetch();
        }

        return [];
    }

    public function getPropertyArticleTotal()
    {
        $state = $this->getState();
        $types = explode(',', $state->types);

        if (in_array('article', $types)) {
            $model = new \Prototype\Model\SCR\Article\SearchModel($this->container);
            $model->setState($this->getState()->getValues());

            $response = $model->fetch(true);

            return $response['total'];
        }

        return 0;
    }

    public function getPropertyEvent()
    {
        $state = $this->getState();
        $types = explode(',', $state->types);

        if (in_array('event', $types)) {
            $model = new \Prototype\Model\SCR\Event\SearchModel($this->container);
            $model->setState($this->getState()->getValues());

            return $model->fetch();
        }

        return [];
    }

    public function getPropertyEventTotal()
    {
        $state = $this->getState();
        $types = explode(',', $state->types);

        if (in_array('event', $types)) {
            $model = new \Prototype\Model\SCR\Event\SearchModel($this->container);
            $model->setState($this->getState()->getValues());

            $response = $model->fetch(true);

            return $response['total'];
        }

        return 0;
    }

    public function fetch($raw = false)
    {
        return [$this];
    }
}

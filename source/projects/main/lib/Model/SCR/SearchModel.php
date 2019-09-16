<?php

/**
 * @package     Dipity
 * @copyright   Copyright (C) 2014 - 2017 Dipity B.V. All rights reserved.
 * @link        http://www.dipity.eu
 */

namespace Frontender\Platform\Model\SCR;

use Slim\Container;
use Doctrine\Common\Inflector\Inflector;
use Frontender\Platform\Model\SCR\Channel\AbstractModel;
use Frontender\Platform\Model\Traits\Searchable;

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
            ],
            'publications' => [
                'must' => [[
                    'type' => 'label',
                    'id' => 'f47307e1-8703-4759-9b99-1f0c46cadc63'
                ], [
                    'type' => 'field',
                    'id' => 'articleType',
                    'value' => 'issue'
                ]]
            ]
        ],
        'strategy' => [
            [
                'slug' => 'youth',
                'title' => 'Youth',
                'id' => '6e7d45e3-fc9f-4010-952c-a336d01bb03d',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => '6e7d45e3-fc9f-4010-952c-a336d01bb03d'
                    ]]
                ]
            ],
            [
                'slug' => 'gender',
                'title' => 'Gender',
                'id' => 'f267b616-a552-4747-9030-dbca6ebfa817',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => 'f267b616-a552-4747-9030-dbca6ebfa817'
                    ]]
                ]
            ],
            [
                'slug' => 'knowledge-management',
                'title' => 'Knowledge Management',
                'id' => 'ecf5f724-3929-4803-99f9-ae1002ae796d',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => 'ecf5f724-3929-4803-99f9-ae1002ae796d'
                    ]]
                ]
            ],
            [
                'slug' => 'nutrition',
                'title' => 'Nutrition',
                'id' => '7cd83d87-4285-4e4d-bc39-74bc87532a0c',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => '7cd83d87-4285-4e4d-bc39-74bc87532a0c'
                    ]]
                ]
            ],
            [
                'slug' => 'digitalisation',
                'title' => 'Digitalisation',
                'id' => '6567e105-820e-484c-b82e-728b8474e1c9',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => '6567e105-820e-484c-b82e-728b8474e1c9'
                    ]]
                ]
            ],
            [
                'slug' => 'climate',
                'title' => 'Climate',
                'id' => '9d206e7f-c883-4798-9bf4-89d3f0f47228',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => '9d206e7f-c883-4798-9bf4-89d3f0f47228'
                    ]]
                ]
            ],
        ],
        'programme' => [
            [
                'slug' => 'farmers-hub',
                'title' => 'Farmers\' Hub',
                'id' => '95995a37-66f5-4abe-9eb9-c47be18dc92a',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => '95995a37-66f5-4abe-9eb9-c47be18dc92a'
                    ]]
                ]
            ],
            [
                'slug' => 'blockchain',
                'title' => 'Blockchain',
                'id' => '1f990ed1-5d97-400b-bd91-b2056c422460',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => '1f990ed1-5d97-400b-bd91-b2056c422460'
                    ]]
                ]
            ],
            [
                'slug' => 'spore',
                'title' => 'Spore',
                'id' => '7f3d1ca0-8ee8-44fb-946d-bd93e20ae94b',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => '7f3d1ca0-8ee8-44fb-946d-bd93e20ae94b'
                    ]]
                ]
            ],
            [
                'slug' => 'diary profit',
                'title' => 'Diary profit',
                'id' => 'b2603120-8f05-43f8-95d8-15cbfa0b568a',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => 'b2603120-8f05-43f8-95d8-15cbfa0b568a'
                    ]]
                ]
            ],
            [
                'slug' => 'vijabiz',
                'title' => 'Vijabiz',
                'id' => '189b26d4-51e9-4fee-b659-5705f4429e81',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => '189b26d4-51e9-4fee-b659-5705f4429e81'
                    ]]
                ]
            ],
            [
                'slug' => 'edc',
                'title' => 'EDC',
                'id' => '8ece89cd-1c8f-499e-9f39-5d2de2d48ffb',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => '8ece89cd-1c8f-499e-9f39-5d2de2d48ffb'
                    ]]
                ]
            ],
            [
                'slug' => 'climark',
                'title' => 'Climark',
                'id' => '5ef24ad9-15af-4a25-b9a5-d401d0a7a1d4',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => '5ef24ad9-15af-4a25-b9a5-d401d0a7a1d4'
                    ]]
                ]
            ],
            [
                'slug' => 'csa-in-aouthern-africa',
                'title' => 'CSA in Southern Africa',
                'id' => '75aa3910-c3f6-48ba-a68e-73471075883e',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => '75aa3910-c3f6-48ba-a68e-73471075883e'
                    ]]
                ]
            ],
            [
                'slug' => 'nutrition-in-the-pacific-islands',
                'title' => 'Nutrition in the Pacific islands',
                'id' => '0cfcb73a-794a-4da7-9255-f5ac504677af',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => '0cfcb73a-794a-4da7-9255-f5ac504677af'
                    ]]
                ]
            ],
            [
                'slug' => 'apps4ag',
                'title' => 'Apps4Ag',
                'id' => 'a640e427-a2b0-43f3-b3eb-82f408666f9d',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => 'a640e427-a2b0-43f3-b3eb-82f408666f9d'
                    ]]
                ]
            ],
            [
                'slug' => 'cassava-value-chain',
                'title' => 'Cassava value chain',
                'id' => '0f2ac206-f01e-44ec-b901-83b4212e35f8',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => '0f2ac206-f01e-44ec-b901-83b4212e35f8'
                    ]]
                ]
            ],
            [
                'slug' => 'afsi',
                'title' => 'AFSI',
                'id' => '8263d2be-bda5-469a-b60a-62b3bddec6a6',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => '8263d2be-bda5-469a-b60a-62b3bddec6a6'
                    ]]
                ]
            ],
            [
                'slug' => 'fisheries-value-chains',
                'title' => 'Fisheries value chains',
                'id' => '82facba7-52d3-4632-97ca-2aa92559c09b',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => '82facba7-52d3-4632-97ca-2aa92559c09b'
                    ]]
                ]
            ],
            [
                'slug' => 'sids',
                'title' => 'SIDS',
                'id' => 'be8e38ba-dc36-45c5-bd9f-56f979e81492',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => 'be8e38ba-dc36-45c5-bd9f-56f979e81492'
                    ]]
                ]
            ],
            [
                'slug' => 'rongead',
                'title' => 'RONGEAD',
                'id' => '24b634de-12f9-43d1-aab4-43dfb5dbe759',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => '24b634de-12f9-43d1-aab4-43dfb5dbe759'
                    ]]
                ]
            ],
            [
                'slug' => 'jaden-lakou',
                'title' => 'Jaden lakou',
                'id' => 'b041fd02-a2d0-4a59-adca-4b28cefe0af2',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => 'b041fd02-a2d0-4a59-adca-4b28cefe0af2'
                    ]]
                ]
            ],
            [
                'slug' => 'experience-capitalisation',
                'title' => 'Experience Capitalisation',
                'id' => 'b6f09a65-5e23-463d-97d7-0ad9c8127062',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => 'b6f09a65-5e23-463d-97d7-0ad9c8127062'
                    ]]
                ]
            ],
            [
                'slug' => 'pejeriz',
                'title' => 'PEJERIZ',
                'id' => '2e25e156-7b3d-4fcc-b1d4-ad7135ea54e1',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => '2e25e156-7b3d-4fcc-b1d4-ad7135ea54e1'
                    ]]
                ]
            ],
            [
                'slug' => 'uptake-of-csa',
                'title' => 'Uptake of CSA',
                'id' => '1fff51df-dbdf-4c92-bad6-cee2f85ec612',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => '1fff51df-dbdf-4c92-bad6-cee2f85ec612'
                    ]]
                ]
            ],
            [
                'slug' => 'eyes-in-the-sky',
                'title' => 'Eyes in the sky',
                'id' => '3b5d608a-f768-4d75-921a-12ebc7e9b72e',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => '3b5d608a-f768-4d75-921a-12ebc7e9b72e'
                    ]]
                ]
            ],
            [
                'slug' => 'brussels-briefings',
                'title' => 'Brussels Briefings',
                'id' => 'd1b7836b-3724-43a8-b7ee-f975737b1cf1',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => 'd1b7836b-3724-43a8-b7ee-f975737b1cf1'
                    ]]
                ]
            ],
            [
                'slug' => 'muiis',
                'title' => 'MUIIS',
                'id' => '21b25d44-b1e0-4da7-89d7-900303865601',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => '21b25d44-b1e0-4da7-89d7-900303865601'
                    ]]
                ]
            ],
            [
                'slug' => 'pitch-agriHack',
                'title' => 'Pitch AgriHack',
                'id' => '9c2331fc-22b6-4748-a69c-fe713e3784eb',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => '9c2331fc-22b6-4748-a69c-fe713e3784eb'
                    ]]
                ]
            ],
            [
                'slug' => 'godan',
                'title' => 'GODAN',
                'id' => 'f6faa3d7-4cc8-4754-a6ac-13c453ef589f',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => 'f6faa3d7-4cc8-4754-a6ac-13c453ef589f'
                    ]]
                ]
            ],
            [
                'slug' => 'godan-action',
                'title' => 'GODAN Action',
                'id' => '96242f0f-1696-444a-b7e0-4215e25b8516',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => '96242f0f-1696-444a-b7e0-4215e25b8516'
                    ]]
                ]
            ],
            [
                'slug' => 'ideal-burkina',
                'title' => 'iDEAL Burkina',
                'id' => 'b454576c-581b-441a-992f-3a9c2c3e9bfc',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => 'b454576c-581b-441a-992f-3a9c2c3e9bfc'
                    ]]
                ]
            ],
            [
                'slug' => 'icon',
                'title' => 'ICON',
                'id' => 'd9e77a0a-acad-4d2d-b8c3-4fdb8f2e4584',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => 'd9e77a0a-acad-4d2d-b8c3-4fdb8f2e4584'
                    ]]
                ]
            ],
            [
                'slug' => 'grainhubs',
                'title' => 'GRAINHUBs',
                'id' => '07fcbb14-6326-4e19-93bd-d5b2c6f53337',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => '07fcbb14-6326-4e19-93bd-d5b2c6f53337'
                    ]]
                ]
            ],
            [
                'slug' => 'value4her',
                'title' => 'VALUE4HER',
                'id' => '90cb575e-a5a0-4d1f-85d2-000d677b5b3f',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => '90cb575e-a5a0-4d1f-85d2-000d677b5b3f'
                    ]]
                ]
            ],
            [
                'slug' => 'manioc21',
                'title' => 'Manioc 21',
                'id' => '9416ff8e-c02f-41bb-b471-383f5dbc33fb',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => '9416ff8e-c02f-41bb-b471-383f5dbc33fb'
                    ]]
                ]
            ],
            [
                'slug' => 'ideal',
                'title' => 'iDEAL',
                'id' => 'f109a9c7-2db6-4a9d-9ffa-4a26bb87db33',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => 'f109a9c7-2db6-4a9d-9ffa-4a26bb87db33'
                    ]]
                ]
            ],
            [
                'slug' => 'data4ag',
                'title' => 'Data4Ag',
                'id' => '1d3e0bbe-b79a-4ecc-a525-9b894c4d9913',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => '1d3e0bbe-b79a-4ecc-a525-9b894c4d9913'
                    ]]
                ]
            ],
            [
                'slug' => 'dapp-zambia',
                'title' => 'DAPP Zambia',
                'id' => '516c9c24-7428-4d6b-8d8d-43b452765644',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => '516c9c24-7428-4d6b-8d8d-43b452765644'
                    ]]
                ]
            ],
            [
                'slug' => 'fin4ag',
                'title' => 'Fin4ag',
                'id' => '40eebb23-6bf0-44e7-a535-f33bf6f5ad4d',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => '40eebb23-6bf0-44e7-a535-f33bf6f5ad4d'
                    ]]
                ]
            ],
            [
                'slug' => 'pgis',
                'title' => 'PGIS',
                'id' => '08832ad0-a322-4d0f-8ef6-ae51957ef03d',
                'query' => [
                    'must' => [[
                        'type' => 'label',
                        'id' => '08832ad0-a322-4d0f-8ef6-ae51957ef03d'
                    ]]
                ]
            ],
        ]
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
            ->insert('strategy')
            ->insert('scope')
            ->insert('programme')
            ->insert('issue');
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

        foreach(['strategy', 'scope', 'programme'] as $filter) {
            if(isset($values[$filter]) && !empty($values[$filter]) && isset($this->searchFilters[$filter])) {
                if(isset($this->searchFilters[$filter][$values[$filter]])) {
                    $values = array_merge_recursive(
                        $values,
                        $this->searchFilters[$filter][$values[$filter]]
                    );
                } else {
                    $value = $values[$filter];
                    $items = array_filter($this->searchFilters[$filter], function($item) use ($value) {
                        if(!isset($item['id']) || !isset($item['slug'])) {
                            return false;
                        }

                        return $item['id'] == $value || $item['slug'] == $value;
                    });

                    if(count($items)) {
                        $item = array_shift($items);
                        $values = array_merge_recursive($values, $item['query']);
                    } else if(strlen($value) == 32 || strlen($value) == 36) {
                        $values = array_merge_recursive($values, [
                            'must' => [[
                                'type' => 'label',
                                'id' => $value
                            ]]
                        ]);
                    }
                }
            }
        }

        if(isset($values['issue']) && !empty($values['issue'])) {
            $values['must'][] = $this->addTerm('label', $values['issue']);
        }

        parent::setState($values);
    }

    public function getPropertyArticle()
    {
        $state = $this->getState();
        $types = explode(',', $state->types);

        if (in_array('article', $types)) {
            $model = new \Frontender\Platform\Model\SCR\Article\SearchModel($this->container);
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
            $model = new \Frontender\Platform\Model\SCR\Article\SearchModel($this->container);
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
            $model = new \Frontender\Platform\Model\SCR\Event\SearchModel($this->container);
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
            $model = new \Frontender\Platform\Model\SCR\Event\SearchModel($this->container);
            $model->setState($this->getState()->getValues());

            $response = $model->fetch(true);

            return $response['total'];
        }

        return 0;
    }

    public function getPropertyStrategy()
    {
        return $this->searchFilters['strategy'];
    }

    public function getPropertyProgramme()
    {
        return $this->searchFilters['programme'];
    }

    public function getPropertySporeIssues()
    {
        $model = new \Frontender\Platform\Model\SCR\Article\SearchModel($this->container);
        $model->setState([
            'type' => 'issue',
            'label' => [
                'da4459e6-9cc2-4f47-8b7c-b79b8ff88f3d'
            ],
            'language' => $this->getState()->language,
            'limit' => 20
        ]);
        $articles = $model->fetch();
        return array_filter($articles, function($article) {
            if($article['issue'] && $article['issue']->issueNumberLabel && $article['number']) {
                return true;
            }

            return false;
        });
    }

    public function getPropertyIctupdateIssues()
    {
        $model = new \Frontender\Platform\Model\SCR\Article\SearchModel($this->container);
        $model->setState([
            'type' => 'issue',
            'label' => [
                '1dde377b-5712-4d1b-91fe-bedd80f76e28'
            ],
            'language' => $this->getState()->language,
            'limit' => 20
        ]);
        $articles = $model->fetch();
        return array_filter($articles, function($article) {
            if($article['issue'] && $article['issue']->issueNumberLabel && $article['number']) {
                return true;
            }

            return false;
        });
    }

    public function fetch($raw = false)
    {
        return [$this];
    }
}

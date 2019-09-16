<?php

namespace Frontender\Platform\Model\Traits;

trait Searchable
{
    public function __construct($container)
    {
        parent::__construct($container);

        $this->getState()
            ->insert('q')
            ->insert('language', $container->language->get())
            ->insert('limit', 20)
            ->insert('must', [])
            ->insert('mustNot', [])
            ->insert('should', [])
            ->insert('skip')
            ->insert('to')
            ->insert('from')
            ->insert('terms')
            ->insert('type')
            ->insert('id')
            ->insert('label');
    }

    public function setState(array $values)
    {
        $values['must'] = array_key_exists('must', $values) && is_array($values['must']) ? $values['must'] : $this->getState()->must;
        $values['should'] = array_key_exists('should', $values) && is_array($values['should']) ? $values['should'] : $this->getState()->should;
        $values['mustNot'] = array_key_exists('mustNot', $values) && is_array($values['mustNot']) ? $values['mustNot'] : $this->getState()->mustNot;

        if (isset($values['location']) && !empty($values['location'])) {
            $values['must'][] = $this->addTerm('geo', 'http://sws.geonames.org/' . $values['location'] . '/');
        }

        if (isset($values['type']) && !empty($values['type'])) {
            if (strpos($values['type'], 'article.') !== false) {
                if (!in_array('articleType', array_column($values['must'], 'id'))) {
                    $values['must'][] = $this->addTerm('field', 'articleType', str_replace('article.', '', $values['type']));
                }
            } else if (strpos($values['type'], 'event.') !== false) {
                if (!in_array('type', array_column($values['must'], 'id'))) {
                    $values['must'][] = $this->addTerm('field', 'type', str_replace('event.', '', $values['type']));
                }
            }
        }

        if (isset($values['label'])) {
            foreach ($values['label'] as $label) {
                $values['must'][] = $this->addTerm('label', $label);
            }
        }

        if (array_key_exists('concepts', $values)) {
            foreach ($values['concepts'] as $term) {
                $values['must'][] = $this->addTerm('concept', 'http://aims.fao.org/aos/agrovoc/' . $term);
            }
        }

        if (isset($values['geo']) && !empty($values['geo'])) {
            foreach ($values['geo'] as $term) {
                $values['must'][] = $this->addTerm('geo', $term);
            }
        }

        if (isset($values['id'])) {
            $values['must'][] = $this->addTerm('field', '_id', $values['id']);
            unset($values['id']);
        }

        if (isset($values['to'])) {
            $values['to'] = str_replace(' ', '+', $values['to']);
        }

        if (isset($values['from'])) {
            $values['from'] = str_replace(' ', '+', $values['from']);
        }

        $values['must'] = array_unique($values['must'], SORT_REGULAR);
        $values['should'] = array_unique($values['should'], SORT_REGULAR);

        return parent::setState($values);
    }

    private function addTerm($type, $field, $value = false)
    {
        $term = [
            'type' => $type,
            'id' => $field
        ];

        if ($value) {
            $term['value'] = $value;
        }


        return $term;
    }
}
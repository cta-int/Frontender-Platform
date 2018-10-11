<?php

namespace Prototype\Template\Helper;

use Frontender\Core\Template\Helper;
use Frontender\Core\Template\Filter\Translate;
use Frontender\Core\Template\Filter\Escaping;
use Frontender\Core\Template\Helper\Router;

class Url extends Helper\Url
{
    public function translateUrl($locale)
    {
        $route = $this->container['page']->getRequest()->getAttribute('route');
        $router = new Router($this->container);
        $query = http_build_query($this->container->request->getQueryParams());
        $page = $this->container['page']->getData();
        $params = $route->getArguments() ?? [];
        $params['locale'] = $locale;

        if (isset($params['id']) && $params['id'] && isset($page['model'])) {
            // $params['id'] .= !empty($query) ? '?' . $query : '';
            $params['slug'] = $this->getTranslatedSlug($page['model'], $locale);
        } else {
            $params['page'] = $params['page'] ?? '';
            // $params['page'] .= !empty($query) ? '?' . $query : '';
        }

        return $router->route($params);
    }

    public function getTranslatedSlug($data, $locale)
    {
		// Get the item.
        $slugs = [
            'events' => 'name',
            'articles' => 'headline',
            'channels' => 'name'
        ];

        $slug_key = $slugs[$data->current()->getName()];
        $translator = new Translate($this->container);
        $escaping = new Escaping();
		// Check if we have an item in pimple.
		// If so we will return that item.
        if ($this->container->has('translate_item')) {
            $item = $this->container->get('translate_item');
        } else if ($data) {
            $model = clone $data->current();
            $state = $model->getState()->getValues();
            $state['language'] = null;

            $model->setState($state);
            $item = $model->fetch();

            if (count($item)) {
                $item = array_shift($item);
                $this->container['translate_item'] = $item;
            } else {
                $item = false;
            }
        }

        if ($item) {
            $slug = $translator->translate($item->translate($item[$slug_key]), [$locale]);
            return $escaping->slugify($slug);
        }

        return '';
    }
}
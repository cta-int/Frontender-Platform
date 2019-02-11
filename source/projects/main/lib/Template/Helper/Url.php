<?php

namespace Prototype\Template\Helper;

use Frontender\Core\Template\Helper;
use Frontender\Core\Template\Filter\Translate;
use Frontender\Core\Template\Filter\Escaping;
use Frontender\Core\Template\Helper\Router;

class Url extends Helper\Url
{
    public $translations = [
        'en' => 'en-GB',
        'fr' => 'fr-FR',
        'pt' => 'pt-PT',
        'es' => 'es-ES'
    ];

    public function translateUrl($locale)
    {
        $route = $this->container['page']->getRequest()->getAttribute('route');
        $router = new Router($this->container);
        $query = http_build_query($this->container->request->getQueryParams());
        $page = $this->container['page']->getData();
        $params = $route->getArguments() ?? [];
        $params['locale'] = $locale;
        $pageJson = $this->container['page']->getRequest()->getAttribute('json');
        $translateLocale = $this->translations[$locale] ?? $locale;

        if (isset($params['id']) && $params['id'] && isset($page['model'])) {
            // $params['id'] .= !empty($query) ? '?' . $query : '';
            $params['slug'] = $this->getTranslatedSlug($page['model'], $locale);
        } else {
            $params['page'] = $pageJson['definition']['route'][$translateLocale] ?? $params['page'] ?? '';
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

        if (isset($slugs[$data->current()->getModelName()])) {
            $slug_key = $slugs[$data->current()->getModelName()];
            $translator = new Translate($this->container);
            $escaping = new Escaping($this->container);

            $model = get_class($data->current());
            $model = new $model($this->container);
            $state = $data->current()->getState()->getValues();
            $state['language'] = $locale;

            $model->setState($state);
            $item = $model->fetch();

            if (count($item)) {
                $item = array_shift($item);
            } else {
                $item = false;
            }

            if ($item) {
                $slug = $translator->translate($item->translate($item[$slug_key]), [$locale]);
                return $escaping->slugify($slug);
            }
        }

        return '';
    }
}

<?php

namespace Frontender\Platform\Model\Traits;

trait Projectable
{
    public function addPublication(&$article, $bindPublication = true)
    {
        if (is_array($article) && !array_key_exists('headline', $article)) {
            return array_map(function ($item) use ($bindPublication) {
                return $this->addPublication($item, $bindPublication);
            }, $article);
        }

        $routes = $this->getRoutes();
        if (!count($routes) || !$article) {
            return $article;
        }

		// Check if we have a route that matches our id.
        if (array_key_exists('link', $article) && count($article['link']['label'])) {
            $links = [];

            foreach ($article['link']['label'] as $link) {
                $links[$link['_id']] = $link;
            }

            $intersect = array_intersect_key($routes, $links);
            if (!count($intersect)) {
                return $article;
            }

            $route = array_shift($intersect);

			// Get the first intersection.
            $article['path_prefix'] = $route['path'] . '/';

            if ($bindPublication === true) {
                $article['issue'] = $this->bindIssue($article['link']['label']);
            }
        }

		// Check if we have the routes in pimple.
        return $article;
    }

    private function getRoutes()
    {
        if ($this->container->has('routes')) {
            return $this->container->get('routes');
        }

        $settings = $this->container->get('settings');
        $project = $settings->get('project');

        $path = $project['path'] . '/routes.json';
        $routes = file_exists($path) ? json_decode(file_get_contents($path), true) : [];

		// We will only need the label routes.
		// If there are none, we will return an empty array.
        if (!array_key_exists('labels', $routes)) {
            return [];
        } else if (count($routes['labels']) <= 0) {
            return [];
        }

		// We now know it is available and it has items.
        $routes = $routes['labels'];
		// First we will filter out the ones we don't need.
        $routes = array_filter($routes, function ($item) {
            if (!array_key_exists('publish_at', $item)) {
                return false;
            }

            if ($item['publish_at'] > time()) {
                return false;
            }

            return true;
        });

        uasort($routes, function ($a, $b) {
            if ($a['publish_at'] == $b['publish_at']) {
                return 0;
            }

            return $a['publish_at'] < $b['publish_at'] ? 1 : -1;
        });

		// Now we will format on segments.
        uasort($routes, function ($a, $b) {
            $segements_a = count(explode('/', $a['path']));
            $segements_b = count(explode('/', $b['path']));

            if ($segements_a == $segements_b) {
                return 0;
            }

            return $segements_a < $segements_b ? 1 : -1;
        });

        $this->container['routes'] = $routes;

        return $routes;
    }

    public function bindIssue($labels)
    {
		// We only come here if we have labels.
        $searchModel = new \Frontender\Platform\Model\SCR\Article\SearchModel($this->container);
        $review = $searchModel->setState([
            'type' => 'article.issue',
            'label' => array_map(function ($label) {
                return $label['_id'];
            }, $labels),
            'limit' => 1,
            'language' => $this->getState()->language,
            'recursive' => false
        ])->fetch();
        $review = $review['ArticlesSearches'];

		// Do a count just in case.
        if (!count($review)) {
            return false;
        }

        return array_shift($review);
    }
}
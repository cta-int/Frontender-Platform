importScripts('https://storage.googleapis.com/workbox-cdn/releases/4.3.1/workbox-sw.js');

workbox.setConfig({
    debug: false
});

workbox.core.skipWaiting();
workbox.core.clientsClaim();

workbox.googleAnalytics.initialize();

const preCache = caches.open(
    workbox.core._private.cacheNames.getPrecacheName()
);

self.addEventListener('fetch', function(event) {
    if(!event || !event.request || !event.request.url) {
        // No url found, bail.
        return;
    }

    let cacheKey = new URL(event.request.url);

    // We don't match query params.
    for(let index of cacheKey.searchParams.keys()) {
        cacheKey.searchParams.delete(index);
    }

    preCache.then(function(cache) {
        // Check if there is a match in the precached results.
        return cache.match(cacheKey.toString())
            .then(function(requestMatch) {
                // No match found, bail.
                if(!requestMatch) {
                    return true;
                }

                // Updating found match. This is effectively a StaleWhileRevalidate.
                return cache.add(cacheKey);
            });
    });
});

workbox.precaching.precacheAndRoute([
    // Pages
    '/en',
    '/fr',
    '/en/offline',
    '/fr/offline',

    // Assets
    '/assets/css/screen.css',
    '/assets/fonts/cta-icons.woff',
    '/assets/fonts/cta-icons.ttf',
    '/assets/fonts/cta-illustrations/fonts/cta-illustrations.woff',
    '/assets/fonts/cta-illustrations/fonts/cta-illustrations.ttf',
    '/images/line-art/water.svg',
    '/images/logos/europe-logo.png',
    '/images/logos/acp-logo.png'
], {
    ignoreURLParametersMatching: [/.*/]
});

workbox.routing.registerRoute(
    /.*(?:googleapis|gstatic)\.com/,
    new workbox.strategies.StaleWhileRevalidate()
);

workbox.routing.registerRoute(
    ({ event }) => event.request.destination === 'document',
    new workbox.strategies.StaleWhileRevalidate({
        cacheName: 'document-cache',
        plugins: [
            new workbox.expiration.Plugin({
                maxEntries: 20,
                maxAgeSeconds: 24 * 60 * 60
            })
        ]
    })
);

workbox.routing.registerRoute(
    /\.js.*$/,
    new workbox.strategies.StaleWhileRevalidate({
        cacheName: 'script-cache',
        ignoreURLParametersMatching: [/.*/],
        plugins: [
            new workbox.expiration.Plugin({
                maxEntries: 30
            })
        ]
    })
);

workbox.routing.registerRoute(
    /\.css.*$/,
    new workbox.strategies.StaleWhileRevalidate({
        cacheName: 'css-cache',
        ignoreURLParametersMatching: [/.*/],
        plugins: [
            new workbox.expiration.Plugin({
                maxEntries: 10
            })
        ]
    })
);

workbox.routing.registerRoute(
    function(url) {
        if(url.url.host.indexOf('cloudinary') > -1) {
            return false;
        }

        return /\.(?:png|jpg|jpeg|svg|gif|ico|woff|ttf).*$/.test(url.url.href);
    },
    new workbox.strategies.CacheFirst({
        cacheName: 'image-cache',
        ignoreURLParametersMatching: [/.*/],
        plugins: [
            new workbox.expiration.Plugin({
                maxEntries: 30,
                maxAgeSeconds: 7 * 24 * 60 * 60,
            })
        ]
    })
);

workbox.routing.setCatchHandler(({ event }) => {
    switch (event.request.destination) {
        case 'document':
            return caches.match('/en/offline');
            break;
        default:
            return Response.error();
    }
});

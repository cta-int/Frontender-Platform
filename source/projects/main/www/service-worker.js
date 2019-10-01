importScripts('https://storage.googleapis.com/workbox-cdn/releases/4.3.1/workbox-sw.js');
// Basic Workbox configuration
workbox.setConfig({
    debug: true
});

addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        skipWaiting();
    }
});

workbox.googleAnalytics.initialize();
// End of basic Workbox configuration

// Cache Google API urls
workbox.routing.registerRoute(
    /.*(?:googleapis|gstatic)\.com/,
    new workbox.strategies.StaleWhileRevalidate()
);

workbox.routing.registerRoute(
    // Custom `matchCallback` function to cache the page document
    ({ event }) => event.request.destination === 'document',
    new workbox.strategies.StaleWhileRevalidate({
        cacheName: 'document-cache'
    })
);

// Caching strategies for assets
workbox.routing.registerRoute(
    /\.js.*$/,
    new workbox.strategies.NetworkFirst({
        cacheName: 'script-cache',
        ignoreURLParametersMatching: [/.*/]
    })
);

workbox.routing.registerRoute(
    /\.css.*$/,
    new workbox.strategies.StaleWhileRevalidate({
        cacheName: 'css-cache',
        ignoreURLParametersMatching: [/.*/]
    })
);

workbox.routing.registerRoute(
    /\.(?:png|jpg|jpeg|svg|gif|ico).*$/,
    // Use the cache if it's available.
    new workbox.strategies.CacheFirst({
        cacheName: 'image-cache',
        plugins: [
            new workbox.expiration.Plugin({
                // Cache only 20 images.
                maxEntries: 20,
                // Cache for a maximum of a week.
                maxAgeSeconds: 7 * 24 * 60 * 60,
            })
        ]
    })
);

const precacheController = new workbox.precaching.PrecacheController();
precacheController.addToCacheList([
    '/assets/css/screen.css'
]);

workbox.precaching.precacheAndRoute([
    '/en/offline',
    '/fr/offline'
]);

// This "catch" handler is triggered when any of the other routes fail to
// generate a response.
workbox.routing.setCatchHandler(({ event }) => {
    // The FALLBACK_URL entries must be added to the cache ahead of time, either via runtime
    // or precaching.
    // If they are precached, then call workbox.precaching.getCacheKeyForURL(FALLBACK_URL)
    // to get the correct cache key to pass in to caches.match().
    //
    // Use event, request, and url to figure out how to respond.
    // One approach would be to use request.destination, see
    // https://medium.com/dev-channel/service-worker-caching-strategies-based-on-request-types-57411dd7652c
    switch (event.request.destination) {
        case 'document':
            return caches.match('/en/offline');
            break;

        default:
            // If we don't have a fallback, just return an error response.
            return Response.error();
    }
});

importScripts('https://storage.googleapis.com/workbox-cdn/releases/4.3.1/workbox-sw.js');
workbox.setConfig({
    debug: false
});

addEventListener('install', function () {
    skipWaiting();
});

addEventListener('activate', function (event) {
    event.waitUntil(clients.claim());
});

workbox.googleAnalytics.initialize();

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
                maxEntries: 20
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
    /(?:cloudinary)/,
    new workbox.strategies.NetworkFirst({
        cacheName: 'image-cache',
        plugins: [
            new workbox.expiration.Plugin({
                maxEntries: 30
            })
        ]
    })
);

workbox.routing.registerRoute(
    function(url) {
        if(url.url.host.indexOf('cloudinary') > -1) {
            return false;
        }

        return /\.(?:png|jpg|jpeg|svg|gif|ico).*$/.test(url.url.href);
    },
    new workbox.strategies.CacheFirst({
        cacheName: 'image-cache',
        plugins: [
            new workbox.expiration.Plugin({
                maxEntries: 30,
                maxAgeSeconds: 7 * 24 * 60 * 60,
            })
        ]
    })
);

const precacheController = new workbox.precaching.PrecacheController();
precacheController.addToCacheList([
    '/assets/css/screen.css',
    '/images/logos/europe-logo.png',
    '/images/logos/acp-logo.png'
]);

workbox.precaching.precacheAndRoute([
    '/en/offline',
    '/fr/offline'
]);

workbox.routing.setCatchHandler(({ event }) => {
    switch (event.request.destination) {
        case 'document':
            return caches.match('/en/offline');
            break;
        default:
            return Response.error();
    }
});

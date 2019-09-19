importScripts('https://storage.googleapis.com/workbox-cdn/releases/4.3.1/workbox-sw.js');
// Basic Workbox configuration
workbox.setConfig({
    debug: true
});

workbox.googleAnalytics.initialize();
// End of basic Workbox configuration

// Cache Google API urls
workbox.routing.registerRoute(
  /.*(?:googleapis|gstatic)\.com/,
  new workbox.strategies.StaleWhileRevalidate(),
);

// Caching strategies for assets
workbox.routing.registerRoute(
    /\.js$/,
    // Load from network first, if network fails used cached files
    new workbox.strategies.NetworkFirst()
);

workbox.routing.registerRoute(
    /\.css$/,
    // Use cache but update in the background.
    new workbox.strategies.StaleWhileRevalidate({
        cacheName: 'css-cache',
    })
);

workbox.routing.registerRoute(
    /\.(?:png|jpg|jpeg|svg|gif|ico)$/,
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
        ],
    })
);

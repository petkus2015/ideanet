// Minimálny service worker – umožní inštaláciu na plochu. Obsah sa vždy berie zo siete.
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (e) => e.waitUntil(self.clients.claim()));
self.addEventListener('fetch', () => {});

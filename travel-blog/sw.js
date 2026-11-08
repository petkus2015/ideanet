// Service worker: offline shell (cache-first, aktualizácia na pozadí). Uložené články žijú v 'adventuro-saved-v1'.
const SHELL = 'adventuro-shell-v2';
const FILES = ['./', 'index.html', 'styles.css', 'app.js', 'manifest.webmanifest', 'icon.svg', 'icon-192.png', 'icon-512.png'];
self.addEventListener('install', e => e.waitUntil(caches.open(SHELL).then(c => c.addAll(FILES)).then(() => self.skipWaiting())));
self.addEventListener('activate', e => e.waitUntil(caches.keys().then(ks => Promise.all(ks.filter(k => k.startsWith('adventuro-shell') && k !== SHELL).map(k => caches.delete(k)))).then(() => self.clients.claim())));
self.addEventListener('fetch', e => {
  if (e.request.method !== 'GET') return;
  e.respondWith(caches.match(e.request).then(hit => {
    const net = fetch(e.request).then(r => { if (r.ok && new URL(e.request.url).origin === location.origin) { const cp = r.clone(); caches.open(SHELL).then(c => c.put(e.request, cp)); } return r; }).catch(() => hit);
    return hit || net;
  }));
});

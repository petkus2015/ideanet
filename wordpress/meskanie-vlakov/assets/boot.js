/* Meškanie vlakov — vloží aplikáciu do Shadow DOM, aby sa jej štýly nemiešali so štýlmi témy.
   Beží pred assets/app.js a odovzdá mu nastavenia cez window.VLAKY_CONFIG. */
(function () {
  'use strict';
  var host = document.getElementById('meskanie-vlakov');
  var tpl = document.getElementById('mv-template');
  if (!host || !tpl || host.shadowRoot) return;
  var cfg = {};
  try { cfg = JSON.parse(host.getAttribute('data-config') || '{}'); } catch (e) { /* predvolené nastavenia */ }

  var root = host.attachShadow({ mode: 'open' });
  var css = document.createElement('link');
  css.rel = 'stylesheet';
  css.href = cfg.cssUrl;
  var app = document.createElement('div');
  app.className = 'mv';
  app.appendChild(tpl.content.cloneNode(true));
  root.appendChild(css);
  root.appendChild(app);

  cfg.root = root;
  window.VLAKY_CONFIG = cfg;
})();

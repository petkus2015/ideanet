/* Meškanie vlakov — spúšťač pre WordPress plugin.
   Build (scripts/build_wp_plugin.py) z neho a z vlaky/app.js zloží jeden súbor assets/meskanie-vlakov.js,
   aby optimalizačné pluginy nemohli pokaziť poradie skriptov. */
(function () {
  'use strict';

  /* @APP@ */

  function showError(host, err) {
    var box = document.createElement('p');
    box.setAttribute('style', 'margin:0;padding:16px;border-radius:12px;background:#3f1b1b;color:#ffd7d7;font:16px/1.5 system-ui,sans-serif');
    box.textContent = 'Tabuľu meškania vlakov sa nepodarilo spustiť (' + ((err && err.message) || err) + '). Skúste obnoviť stránku.';
    (host.shadowRoot || host).appendChild(box);
  }

  function start() {
    var host = document.getElementById('meskanie-vlakov');
    var tpl = document.getElementById('mv-template');
    if (!host || !tpl || host.shadowRoot) return;
    var cfg = {};
    try { cfg = JSON.parse(host.getAttribute('data-config') || '{}'); } catch (e) { /* predvolené nastavenia */ }

    // Shadow DOM: štýly témy a tabule sa navzájom neovplyvnia
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
    try {
      mvApp();
    } catch (err) {
      if (window.console) console.error('Meškanie vlakov:', err);
      showError(host, err);
    }
  }

  // skript môže byť optimalizátorom presunutý do hlavičky alebo spustený neskôr: počkáme na obsah stránky
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
  else start();
})();

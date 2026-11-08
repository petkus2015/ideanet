/* Administrácia: spustenie kontroly GDPR lišty AdSense (v skrytom rámci s úvodnou stránkou ako návštevník). */
(function () {
	'use strict';
	var cfg = window.relifeCmp || {};
	var $ = function (id) { return document.getElementById(id); };
	var icon = { ok: '✅', warn: '⚠️', fail: '❌', info: 'ℹ️' };
	var ORDER = ['srv_theme', 'script', 'srv_raw', 'srv_id', 'pub', 'agload', 'fcnet', 'srv_csp', 'srv_fc', 'conflict', 'srv_plugins', 'cookie', 'region', 'fcreq', 'dom', 'tcf'];

	if (cfg.pub && !$('rl-cmp-pub').value) { $('rl-cmp-pub').value = 'ca-pub-' + cfg.pub; }

	function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }

	function clearConsent() {
		var names = ['FCCDCF', 'FCNEC', 'euconsent-v2', 'addtl_consent'];
		var host = location.hostname.split('.');
		var domains = ['', location.hostname, '.' + location.hostname];
		if (host.length > 2) { domains.push('.' + host.slice(-2).join('.')); }
		names.forEach(function (n) {
			domains.forEach(function (d) {
				document.cookie = n + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/' + (d ? '; domain=' + d : '');
			});
		});
		try { localStorage.removeItem('relife.consent'); } catch (e) { /* nič */ }
	}

	function verdict(res) {
		var byId = {};
		res.forEach(function (r) { byId[r.id] = r; });
		var i, r;
		for (i = 0; i < ORDER.length; i++) {
			r = byId[ORDER[i]];
			if (r && r.status === 'fail') { return { found: true, r: r }; }
		}
		for (i = 0; i < ORDER.length; i++) {
			r = byId[ORDER[i]];
			if (r && r.status === 'warn' && ['conflict', 'cookie', 'region', 'srv_plugins'].indexOf(r.id) > -1) { return { found: true, r: r }; }
		}
		return { found: false };
	}

	function render(res, frameOk) {
		var tb = $('rl-cmp-table').querySelector('tbody');
		tb.innerHTML = '';
		res.sort(function (a, b) {
			var w = { fail: 0, warn: 1, info: 2, ok: 3 };
			return (w[a.status] - w[b.status]);
		});
		res.forEach(function (x) {
			var tr = document.createElement('tr');
			tr.innerHTML = '<td>' + (icon[x.status] || '•') + '</td><td><strong>' + esc(x.title) + '</strong></td><td>' +
				(x.detail ? '<div>' + esc(x.detail) + '</div>' : '') +
				(x.fix && x.status !== 'ok' ? '<div style="color:#1d2327;margin-top:4px"><strong>→ ' + esc(x.fix) + '</strong></div>' : '') + '</td>';
			tb.appendChild(tr);
		});
		$('rl-cmp-table').hidden = false;
		var v = verdict(res);
		var box = $('rl-cmp-verdict');
		box.hidden = false;
		if (v.found) {
			box.style.borderLeftColor = '#d63638';
			box.innerHTML = '<h2 style="margin-top:0">Najpravdepodobnejšia príčina</h2><p style="font-size:15px"><strong>' + esc(v.r.title) + '</strong></p><p>Návrh opravy: ' + esc(v.r.fix) + '</p>';
		} else {
			box.style.borderLeftColor = frameOk ? '#00a32a' : '#dba617';
			box.innerHTML = '<h2 style="margin-top:0">' + (frameOk ? 'Nenašla sa jednoznačná chyba' : 'Test sa dokončil len čiastočne') + '</h2><p>Pozrite upozornenia v tabuľke. Ak lišta stále chýba, otvorte web v novom anonymnom okne zo slovenskej siete.</p>';
		}
		$('rl-cmp-copy').hidden = false;
		$('rl-cmp-copy').onclick = function () {
			var txt = res.map(function (x) { return (icon[x.status] || '•') + ' ' + x.title + (x.detail ? '\n   ' + x.detail : '') + (x.fix && x.status !== 'ok' ? '\n   → ' + x.fix : ''); }).join('\n');
			if (navigator.clipboard) { navigator.clipboard.writeText(txt); $('rl-cmp-copy').textContent = 'Skopírované'; }
		};
	}

	function runFrame(wait) {
		return new Promise(function (resolve) {
			var fr = $('rl-cmp-frame');
			var done = false;
			var finish = function (v) { if (!done) { done = true; resolve(v); } };
			fr.onload = function () {
				setTimeout(function () {
					try {
						var w = fr.contentWindow;
						w.__RELIFE_CMP_NO_AUTORUN = true;
						var s = w.document.createElement('script');
						s.src = cfg.script;
						s.onload = function () {
							w.relifeCmpCheck({ pub: ($('rl-cmp-pub').value || '').replace(/\D/g, '') || cfg.pub }).then(function (r) { finish(r.results); }, function (e) { finish([{ id: 'frame', status: 'fail', title: 'Test v prehliadači zlyhal', detail: String(e), fix: '' }]); });
						};
						s.onerror = function () { finish([{ id: 'frame', status: 'fail', title: 'Nepodarilo sa načítať kontrolný skript', detail: cfg.script, fix: 'Skontrolujte, či je nahraná aktuálna verzia témy.' }]); };
						w.document.head.appendChild(s);
					} catch (e) {
						finish([{ id: 'frame', status: 'fail', title: 'Úvodnú stránku sa nepodarilo otvoriť na kontrolu', detail: String(e), fix: 'Web môže posielať hlavičku X-Frame-Options/CSP frame-ancestors, ktorá zakazuje zobrazenie v rámci. Použite postup z tools/README.md (konzola prehliadača).' }]);
					}
				}, wait * 1000);
			};
			fr.src = cfg.testUrl + '&t=' + Date.now();
			setTimeout(function () { finish([{ id: 'frame', status: 'fail', title: 'Úvodná stránka sa nenačítala včas', detail: '', fix: 'Skúste znova; pri pomalom hostingu zvýšte čakanie.' }]); }, (wait + 40) * 1000);
		});
	}

	$('rl-cmp-run').addEventListener('click', function () {
		var btn = this;
		btn.disabled = true;
		$('rl-cmp-status').textContent = 'Beží test… (približne 20 s)';
		$('rl-cmp-table').hidden = true;
		$('rl-cmp-verdict').hidden = true;
		if ($('rl-cmp-clear').checked) { clearConsent(); }
		var pub = ($('rl-cmp-pub').value || '').replace(/\D/g, '');
		var fd = new FormData();
		fd.append('action', 'relife_cmp');
		fd.append('nonce', cfg.nonce);
		fd.append('pub', pub);
		var server = fetch(cfg.ajax, { method: 'POST', credentials: 'same-origin', body: fd }).then(function (r) { return r.json(); }).then(function (j) { return j.success ? j.data : []; }).catch(function () { return []; });
		Promise.all([server, runFrame(12)]).then(function (all) {
			var frameOk = all[1].length > 0 && all[1][0].id !== 'frame';
			render(all[0].concat(all[1]), frameOk);
			$('rl-cmp-status').textContent = 'Hotovo.';
			btn.disabled = false;
		});
	});
})();

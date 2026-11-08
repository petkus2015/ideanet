/*
 * RELIFE – diagnostika: prečo sa nezobrazuje GDPR lišta (správa o súhlase) z Google AdSense.
 *
 * POUŽITIE (najjednoduchšie): otvorte relife.sk v NOVOM anonymnom okne, počkajte ~10 s, otvorte konzolu
 * (F12 → Console), vložte celý tento súbor a stlačte Enter. Výsledok sa vypíše do konzoly.
 * Alternatíva: node adsense-cmp-check.node.js https://relife.sk   (pozri README.md)
 *
 * Skript nič nemení ani neposiela nikam údaje; len číta stránku a robí jednu požiadavku na ads.txt
 * a na adresu Google Funding Choices.
 */
(function () {
	'use strict';

	var DEFAULT_PUB = '2577354057353027';

	async function relifeCmpCheck(opts) {
		opts = opts || {};
		var pub = String(opts.pub || DEFAULT_PUB).replace(/^ca-/, '').replace(/^pub-/, '');
		var res = [];
		var add = function (id, status, title, detail, fix) {
			res.push({ id: id, status: status, title: title, detail: detail || '', fix: fix || '' });
		};
		var entries = performance.getEntriesByType('resource');
		var has = function (re) { return entries.filter(function (e) { return re.test(e.name); }); };

		/* 1. Je skript AdSense na stránke? */
		var scripts = Array.prototype.slice.call(document.scripts).filter(function (s) { return /adsbygoogle\.js/.test(s.src); });
		var clients = {};
		scripts.forEach(function (s) { var m = /client=(ca-pub-\d+)/.exec(s.src); if (m) { clients[m[1]] = 1; } });
		Array.prototype.slice.call(document.querySelectorAll('ins.adsbygoogle[data-ad-client]')).forEach(function (n) { clients[n.getAttribute('data-ad-client')] = 1; });
		var clientList = Object.keys(clients);
		var loaderInline = /pagead2\.googlesyndication\.com\/pagead\/js\/adsbygoogle\.js/.test(document.documentElement.innerHTML);

		if (!scripts.length && !loaderInline) {
			add('script', 'fail', 'Skript AdSense (adsbygoogle.js) na stránke nie je',
				'Bez neho Google nemá čo načítať, preto sa nezobrazí ani lišta súhlasu.',
				'1) Vzhľad → Prispôsobiť → RELIFE – Reklamy: vyplňte ID vydavateľa alebo vložte celý kód AdSense do hlavičky (téma ho načíta po načítaní stránky). 2) Vyprázdnite cache (Starcache). 3) Skontrolujte, či nie ste prihlásený ako redaktor, ak je zapnuté „Neukazovať reklamy prihláseným redaktorom“. 4) Pri článku nesmie byť zapnuté „Nezobrazovať reklamy v tomto článku“.');
		} else {
			add('script', 'ok', 'Skript AdSense je na stránke', scripts.length ? scripts[0].src : '(vkladá sa dynamicky po načítaní stránky)');
		}

		/* 2. Zhoduje sa ID vydavateľa? */
		if (clientList.length) {
			if (clientList.indexOf('ca-pub-' + pub) === -1) {
				add('pub', 'fail', 'ID vydavateľa na stránke nie je ca-pub-' + pub, 'Nájdené: ' + clientList.join(', '),
					'Opravte ID v Customizeri alebo v kóde AdSense. Lištu GDPR zobrazuje len správa priradená k tomu istému účtu.');
			} else if (clientList.length > 1) {
				add('pub', 'warn', 'Na stránke je viac ID vydavateľov', clientList.join(', '), 'Ponechajte len jedno ID – rôzne kódy sa môžu rušiť.');
			} else {
				add('pub', 'ok', 'ID vydavateľa sedí', 'ca-pub-' + pub);
			}
		}

		/* 3. Načítal sa adsbygoogle.js? */
		var ag = has(/adsbygoogle\.js/);
		var agLoaded = !!(window.adsbygoogle && window.adsbygoogle.loaded === true);
		if ((scripts.length || loaderInline) && !ag.length && !agLoaded) {
			add('agload', 'fail', 'Skript AdSense sa nestiahol (zablokovaný alebo ešte nenačítaný)', 'V sieťových požiadavkách nie je žiadny záznam pre adsbygoogle.js.',
				'Počkajte ~10 s po načítaní stránky a spustite kontrolu znova. Ak sa nestiahne: vypnite blokovač reklám/ochranu súkromia v prehliadači, skontrolujte CSP hlavičku servera (musí povoliť pagead2.googlesyndication.com a fundingchoicesmessages.google.com).');
		} else if (ag.length || agLoaded) {
			add('agload', 'ok', 'Skript AdSense sa načítal', ag.length ? ag[0].name : 'window.adsbygoogle.loaded = true');
		}

		/* 4. Požiadavka na Funding Choices (tadiaľ prichádza GDPR správa) */
		var fc = has(/fundingchoicesmessages\.google\.com/);
		var probe = 'nezistené';
		try {
			await fetch('https://fundingchoicesmessages.google.com/i/pub-' + pub + '?ers=1', { mode: 'no-cors', cache: 'no-store' });
			probe = 'dostupné';
		} catch (e) {
			probe = 'zablokované / chyba siete';
		}
		if (probe !== 'dostupné') {
			add('fcnet', 'fail', 'Adresa Google Funding Choices je z tohto prehliadača nedostupná', 'fundingchoicesmessages.google.com → ' + probe,
				'Vypnite blokovač reklám / ochranu proti sledovaniu / DNS filter (AdGuard, uBlock, Pi-hole, Brave Shields) a skúste znova v čistom anonymnom okne.');
		}
		if (!fc.length) {
			add('fcreq', (scripts.length || agLoaded) ? 'fail' : 'warn', 'Stránka si nevyžiadala GDPR správu od Google',
				'AdSense skript sa pri načítaní neobrátil na fundingchoicesmessages.google.com. Skript to robí len vtedy, keď má účet publikovanú správu pre túto stránku.',
				'V AdSense: Súkromie a správy (Privacy & messaging) → Európske predpisy (GDPR) → vytvorte správu a stlačte „Publikovať“. Skontrolujte, že je v zozname „Stránky“ a že relife.sk má stav Pripravené (overená stránka). Po publikovaní počkajte 1–2 hodiny.');
		} else {
			add('fcreq', 'ok', 'Stránka si vyžiadala GDPR správu od Google', fc[0].name);
		}

		/* 5. Je správa v DOM? */
		var dialog = document.querySelector('.fc-consent-root, .fc-dialog-container, .fc-dialog-overlay');
		var present = document.querySelector('iframe[name="googlefcPresent"]');
		var inactive = document.querySelector('iframe[name="googlefcInactive"]');
		var visible = false;
		if (dialog) {
			var r = dialog.getBoundingClientRect();
			var cs = getComputedStyle(dialog);
			visible = r.width > 0 && r.height > 0 && cs.visibility !== 'hidden' && cs.display !== 'none';
		}
		if (dialog && visible) {
			add('dom', 'ok', 'GDPR lišta Google je na stránke a je viditeľná');
		} else if (dialog && !visible) {
			add('dom', 'fail', 'GDPR lišta Google je v stránke, ale je skrytá', 'Prvok existuje, ale nie je vidieť (display/visibility/rozmery).',
				'Pravdepodobne ju prekrýva alebo skrýva CSS témy/pluginu (z-index, overflow, display:none). Vyskúšajte dočasne vypnúť pluginy na optimalizáciu CSS/JS (minifikácia, odkladanie skriptov).');
		} else if (inactive) {
			add('dom', 'fail', 'Google hlási „žiadna aktívna správa“ (googlefcInactive)', 'Skript Funding Choices sa načítal, ale pre túto stránku/návštevníka sa žiadna správa nemá zobraziť.',
				'Najčastejšie: správa nie je publikovaná alebo nie je priradená k relife.sk, návštevník nie je v EHP/UK, alebo už súhlas dal (cookie FCCDCF). Skontrolujte v AdSense → Súkromie a správy, či je GDPR správa „Aktívna“ a obsahuje relife.sk.');
		} else if (present) {
			add('dom', 'warn', 'Funding Choices je prítomný, ale lišta sa nezobrazila', 'Iframe googlefcPresent existuje, dialóg nie.', 'Súhlas bol pravdepodobne už zadaný (pozrite cookies), alebo správa čaká na publikovanie.');
		} else {
			add('dom', 'fail', 'V stránke nie je žiadny prvok Google Funding Choices', 'Neexistuje ani dialóg, ani iframe googlefcPresent/googlefcInactive.',
				'Pozri vyššie: skript sa buď nenačítal, alebo účet nemá publikovanú správu pre túto stránku.');
		}

		/* 6. TCF API (štandardné rozhranie lišty) */
		if (typeof window.__tcfapi === 'function') {
			var tc = await new Promise(function (resolve) {
				var done = false;
				var t = setTimeout(function () { if (!done) { done = true; resolve(null); } }, 3000);
				try {
					window.__tcfapi('getTCData', 2, function (d, ok) { if (!done) { done = true; clearTimeout(t); resolve(ok ? d : { error: true }); } });
				} catch (e) { if (!done) { done = true; clearTimeout(t); resolve(null); } }
			});
			if (!tc) {
				add('tcf', 'warn', 'Rozhranie TCF (__tcfapi) existuje, ale neodpovedá', '', 'Skúste stránku znova načítať; ak sa opakuje, koliduje druhá cookie lišta.');
			} else {
				var d = 'gdprApplies=' + tc.gdprApplies + ', cmpStatus=' + tc.cmpStatus + ', eventStatus=' + tc.eventStatus + ', tcString=' + (tc.tcString ? tc.tcString.length + ' znakov' : 'prázdny');
				if (tc.gdprApplies === false) {
					add('tcf', 'warn', 'TCF hovorí, že GDPR sa na vás nevzťahuje (gdprApplies=false)', d,
						'Google zobrazuje lištu len návštevníkom z EHP/UK/Švajčiarska. Testujte zo slovenskej IP (nie cez VPN mimo EÚ).');
				} else {
					add('tcf', 'ok', 'Rozhranie TCF funguje', d);
				}
			}
		} else {
			add('tcf', 'info', 'Rozhranie TCF (__tcfapi) na stránke nie je', 'Žiadna certifikovaná lišta (CMP) sa zatiaľ nezaregistrovala.');
		}

		/* 7. Už zadaný súhlas? */
		var ck = document.cookie.split(';').map(function (c) { return c.trim().split('=')[0]; });
		var consentCookies = ck.filter(function (n) { return /^(FCCDCF|FCNEC|euconsent-v2|addtl_consent)$/.test(n); });
		if (consentCookies.length) {
			add('cookie', 'warn', 'Súhlas už bol zadaný (cookies: ' + consentCookies.join(', ') + ')', 'Lišta sa druhýkrát nezobrazí.',
				'Test urobte v novom anonymnom okne alebo vymažte tieto cookies pre relife.sk (F12 → Application → Cookies).');
		} else {
			add('cookie', 'ok', 'Žiadny uložený súhlas Google – lišta by sa mala ukázať');
		}

		/* 8. Konflikt s inou lištou */
		var others = [];
		var gl = { Cookiebot: window.Cookiebot, OneTrust: window.OneTrust, CookieYes: window.cookieyes || window.CookieYes, Complianz: window.cmplz_blocked_scripts || window.complianz, Usercentrics: window.UC_UI, 'Borlabs Cookie': window.BorlabsCookie, 'CookieConsent': window.CookieConsent, Iubenda: window._iub, Didomi: window.Didomi, Termly: window.Termly };
		Object.keys(gl).forEach(function (k) { if (gl[k]) { others.push(k); } });
		if (document.querySelector('[id*="cookie-law-info"], [class*="cmplz"], #cookiebanner, .cc-window')) { others.push('iná cookie lišta v DOM'); }
		if (others.length) {
			add('conflict', 'warn', 'Na stránke je iná cookie lišta: ' + others.join(', '), 'Dve lišty sa rušia; Google nezobrazí svoju správu, ak už existuje iná TCF lišta.',
				'Používajte jednu: buď vypnite druhý plugin, alebo v ňom nastavte Google ako CMP.');
		}
		if (document.getElementById('rl-cookie')) {
			add('rlbanner', 'warn', 'Zobrazuje sa aj vlastná lišta témy RELIFE (#rl-cookie)', 'Okrem Google lišty je zapnutá aj lišta témy pre Google Analytics.',
				'Ak chcete len Google lištu: Vzhľad → Prispôsobiť → RELIFE → vypnite „Zobraziť cookie lištu“ a nechajte meranie riadiť Googlom.');
		}

		/* 9. ads.txt */
		try {
			var t = await fetch('/ads.txt', { cache: 'no-store' });
			var txt = t.ok ? await t.text() : '';
			if (!t.ok) {
				add('adstxt', 'warn', 'ads.txt neexistuje (' + t.status + ')', '', 'Pridajte riadok „google.com, pub-' + pub + ', DIRECT, f08c47fec0942fa0“ (téma ho vytvorí sama, ak je zadané ID v Customizeri) – chýbajúci ads.txt znižuje príjmy a AdSense na to upozorňuje.');
			} else if (txt.indexOf('pub-' + pub) === -1) {
				add('adstxt', 'warn', 'ads.txt neobsahuje pub-' + pub, txt.slice(0, 200), 'Doplňte riadok „google.com, pub-' + pub + ', DIRECT, f08c47fec0942fa0“.');
			} else {
				add('adstxt', 'ok', 'ads.txt obsahuje vaše ID');
			}
		} catch (e) {
			add('adstxt', 'info', 'ads.txt sa nepodarilo načítať', String(e));
		}

		/* 10. Okolnosti testu */
		if (document.getElementById('wpadminbar')) {
			add('admin', 'warn', 'Ste prihlásený vo WordPresse (admin lišta)', '', 'Téma pre prihlásených redaktorov môže skrývať reklamy/tag. Testujte odhlásený alebo v anonymnom okne.');
		}
		var tz = '';
		try { tz = Intl.DateTimeFormat().resolvedOptions().timeZone || ''; } catch (e) { /* nič */ }
		if (tz && !/^(Europe|Atlantic\/(Reykjavik|Canary|Madeira|Azores)|Africa\/Ceuta)/.test(tz)) {
			add('region', 'warn', 'Časové pásmo prehliadača (' + tz + ') nie je európske', '', 'GDPR správu vidia len návštevníci z EHP/UK/CH. Testujte zo slovenskej siete bez VPN.');
		}

		return { url: location.href, pub: 'ca-pub-' + pub, when: new Date().toISOString(), results: res, verdict: verdict(res) };
	}

	/* Vyhodnotenie: najpravdepodobnejšia príčina a postup opravy */
	function verdict(res) {
		var order = ['script', 'pub', 'agload', 'fcnet', 'conflict', 'cookie', 'region', 'admin', 'fcreq', 'dom', 'tcf', 'rlbanner'];
		var byId = {};
		res.forEach(function (r) { byId[r.id] = r; });
		for (var i = 0; i < order.length; i++) {
			var r = byId[order[i]];
			if (r && (r.status === 'fail' || (r.status === 'warn' && ['conflict', 'cookie', 'region', 'admin'].indexOf(r.id) > -1))) {
				return { found: true, id: r.id, title: r.title, fix: r.fix };
			}
		}
		var allOk = res.every(function (r) { return r.status === 'ok' || r.status === 'info' || r.id === 'adstxt'; });
		return allOk ? { found: false, title: 'Všetko vyzerá v poriadku – lišta by sa mala zobrazovať.', fix: '' } : { found: false, title: 'Jednoznačná chyba sa nenašla, pozrite upozornenia vyššie.', fix: '' };
	}

	function relifeCmpFormat(r) {
		var icon = { ok: '✅', warn: '⚠️ ', fail: '❌', info: 'ℹ️ ' };
		var out = [];
		out.push('RELIFE – kontrola GDPR lišty AdSense');
		out.push(r.url + '  |  ' + r.pub + '  |  ' + r.when);
		out.push('');
		r.results.forEach(function (x) {
			out.push((icon[x.status] || '•') + ' ' + x.title);
			if (x.detail) { out.push('     ' + x.detail); }
			if (x.fix && x.status !== 'ok') { out.push('     → ' + x.fix); }
		});
		out.push('');
		out.push('VÝSLEDOK: ' + r.verdict.title);
		if (r.verdict.fix) { out.push('NÁVRH OPRAVY: ' + r.verdict.fix); }
		return out.join('\n');
	}

	window.relifeCmpCheck = relifeCmpCheck;
	window.relifeCmpFormat = relifeCmpFormat;

	if (!window.__RELIFE_CMP_NO_AUTORUN) {
		relifeCmpCheck().then(function (r) {
			window.__relifeCmpResult = r;
			console.log(relifeCmpFormat(r));
		});
	}
})();

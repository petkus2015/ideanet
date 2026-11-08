#!/usr/bin/env node
/*
 * RELIFE – diagnostika GDPR lišty AdSense v príkazovom riadku.
 *
 *   npm i playwright && npx playwright install chromium     (jednorazovo)
 *   node adsense-cmp-check.node.js https://relife.sk [--pub 2577354057353027] [--mobile] [--wait 12] [--screenshot out.png] [--headed]
 *
 * Spustite z počítača na Slovensku (alebo v EÚ) bez VPN: Google ukazuje správu len návštevníkom z EHP/UK/CH.
 * Skript otvorí čistý profil (bez cookies), počká na načítanie, vloží adsense-cmp-check.js a vypíše správu.
 */
'use strict';
const fs = require('fs');
const path = require('path');

function arg(name, def) {
	const i = process.argv.indexOf('--' + name);
	return i > -1 ? (process.argv[i + 1] && !process.argv[i + 1].startsWith('--') ? process.argv[i + 1] : true) : def;
}

const url = process.argv[2] && !process.argv[2].startsWith('--') ? process.argv[2] : 'https://relife.sk';
const pub = String(arg('pub', '2577354057353027')).replace(/^ca-/, '').replace(/^pub-/, '');
const waitSec = Number(arg('wait', 12));
const mobile = !!arg('mobile', false);
const shot = arg('screenshot', '');

let pw;
try {
	pw = require('playwright');
} catch (e) {
	console.error('Chýba balík playwright. Spustite: npm i playwright && npx playwright install chromium');
	process.exit(2);
}

(async () => {
	const browser = await pw.chromium.launch({ headless: !arg('headed', false) });
	const ctx = await browser.newContext(
		Object.assign(
			{ locale: 'sk-SK', timezoneId: 'Europe/Bratislava', viewport: { width: 1366, height: 800 } },
			mobile ? pw.devices['Pixel 5'] : {}
		)
	);
	const page = await ctx.newPage();

	const googleReqs = [];
	const failed = [];
	const consoleErr = [];
	const csp = [];
	page.on('request', (r) => { if (/google(syndication|tagmanager|fundingchoices|-analytics)|doubleclick|fundingchoicesmessages/.test(r.url())) { googleReqs.push({ url: r.url(), t: Date.now() }); } });
	page.on('requestfailed', (r) => { if (/google|doubleclick/.test(r.url())) { failed.push(r.url() + ' – ' + (r.failure() || {}).errorText); } });
	page.on('console', (m) => { if (m.type() === 'error' || /Content Security Policy|CSP/i.test(m.text())) { consoleErr.push(m.text().slice(0, 300)); } });
	await page.addInitScript(() => {
		window.__csp = [];
		document.addEventListener('securitypolicyviolation', (e) => window.__csp.push(e.blockedURI + ' ← ' + e.violatedDirective));
	});

	const resp = await page.goto(url, { waitUntil: 'load', timeout: 60000 });
	await page.waitForTimeout(waitSec * 1000);

	// Surové HTML (bez JavaScriptu) a hlavičky servera
	const raw = await ctx.request.get(url).then(async (r) => ({ status: r.status(), headers: r.headers(), body: await r.text() })).catch(() => null);

	await page.addScriptTag({ content: 'window.__RELIFE_CMP_NO_AUTORUN=true;' });
	await page.addScriptTag({ path: path.join(__dirname, 'adsense-cmp-check.js') });
	const result = await page.evaluate((p) => window.relifeCmpCheck({ pub: p }), pub);
	const text = await page.evaluate((r) => window.relifeCmpFormat(r), result);
	const cspHits = await page.evaluate(() => window.__csp || []);

	// Doplnenie z pohľadu servera
	const extra = [];
	if (raw) {
		const inRaw = /adsbygoogle\.js/.test(raw.body);
		extra.push((inRaw ? '✅' : '⚠️ ') + ' Surové HTML zo servera ' + (inRaw ? 'obsahuje' : 'NEobsahuje') + ' skript adsbygoogle.js' + (inRaw ? '' : ' (ak sa načíta až cez JavaScript, je to v poriadku pri nastavení „celý kód v hlavičke“ – skontrolujte, či ho nevkladá cache/optimalizačný plugin)'));
		const h = raw.headers;
		if (h['content-security-policy']) {
			const c = h['content-security-policy'];
			const ok = /fundingchoicesmessages\.google\.com/.test(c) || /default-src[^;]*\*/.test(c);
			extra.push((ok ? 'ℹ️ ' : '❌') + ' Hlavička Content-Security-Policy je nastavená' + (ok ? '' : ' a nepovoľuje fundingchoicesmessages.google.com → Google lišta bude zablokovaná. Pridajte pagead2.googlesyndication.com, fundingchoicesmessages.google.com, *.google.com, *.gstatic.com do script-src, frame-src a connect-src.'));
		}
		if (h['x-frame-options'] || h['cache-control'] || h['x-cache'] || h['x-starcache']) {
			extra.push('ℹ️  Cache: cache-control=' + (h['cache-control'] || '-') + (h['x-cache'] ? ', x-cache=' + h['x-cache'] : ''));
		}
	}
	try {
		const fc = await ctx.request.get('https://fundingchoicesmessages.google.com/i/pub-' + pub + '?ers=1', { timeout: 20000 });
		const body = await fc.text();
		const sig = /googlefc|FC_|consent/i.test(body);
		extra.push(
			(fc.ok() && body.length > 2000 ? '✅' : '❌') +
				' Funding Choices pre pub-' + pub + ': HTTP ' + fc.status() + ', ' + body.length + ' B' +
				(fc.ok() && body.length > 2000 && sig ? ' (správa je publikovaná)' : ' → malá/prázdna odpoveď: GDPR správa pre tento účet nie je publikovaná alebo ju Google pre vašu IP/stránku nevracia (AdSense → Súkromie a správy → Európske predpisy → Publikovať; stránka relife.sk musí byť pridaná a pripravená).')
		);
	} catch (e) {
		extra.push('⚠️  Funding Choices sa nepodarilo načítať z tohto počítača: ' + e.message);
	}
	if (cspHits.length) { extra.push('❌ Blokované politikou CSP: ' + cspHits.slice(0, 5).join(' | ')); }
	if (failed.length) { extra.push('❌ Zlyhané požiadavky na Google: ' + failed.slice(0, 5).join(' | ')); }

	console.log(text);
	console.log('\n--- Doplnok (zo servera a siete) ---');
	extra.forEach((l) => console.log(l));
	console.log('\n--- Požiadavky na Google (' + googleReqs.length + ') ---');
	googleReqs.slice(0, 15).forEach((r) => console.log('  ' + r.url.slice(0, 140)));
	if (consoleErr.length) { console.log('\n--- Chyby v konzole ---'); consoleErr.slice(0, 8).forEach((l) => console.log('  ' + l)); }
	if (shot) { await page.screenshot({ path: String(shot), fullPage: false }); console.log('\nSnímka: ' + shot); }
	await browser.close();
	process.exit(result.verdict.found ? 1 : 0);
})().catch((e) => { console.error(e); process.exit(3); });

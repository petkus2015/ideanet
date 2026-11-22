/*
 * Zlatý Žĺtok — objednávkový server
 * Node.js 18+ bez závislostí (Stripe API voláme cez fetch).
 *
 *   node server.js
 *
 * Premenné prostredia (alebo súbor .env vedľa tohto súboru):
 *   PORT                    port (predvolene 8080)
 *   PUBLIC_URL              verejná adresa webu, napr. https://zlatyzltok.sk (pre návrat zo Stripe)
 *   STRIPE_SECRET_KEY       sk_live_… alebo sk_test_…  (bez neho beží platba kartou v DEMO režime)
 *   STRIPE_WEBHOOK_SECRET   whsec_… (overenie webhooku checkout.session.completed)
 *   ORDER_WEBHOOK_URL       nepovinné: sem sa pošle JSON každej novej/zaplatenej objednávky (Make, Zapier, Slack…)
 */
'use strict';

const http = require('node:http');
const fs = require('node:fs');
const fsp = require('node:fs/promises');
const path = require('node:path');
const crypto = require('node:crypto');

const ROOT = __dirname;
const ORDERS_DIR = path.join(ROOT, 'orders');

/* ---------- .env ---------- */
try {
  const env = fs.readFileSync(path.join(ROOT, '.env'), 'utf8');
  for (const line of env.split(/\r?\n/)) {
    const m = line.match(/^\s*([A-Z0-9_]+)\s*=\s*(.*)\s*$/);
    if (m && !(m[1] in process.env)) process.env[m[1]] = m[2].replace(/^["']|["']$/g, '');
  }
} catch (e) { /* .env je nepovinný */ }

const PORT = +process.env.PORT || 8080;
const STRIPE_KEY = process.env.STRIPE_SECRET_KEY || '';
const WEBHOOK_SECRET = process.env.STRIPE_WEBHOOK_SECRET || '';
const PUBLIC_URL = (process.env.PUBLIC_URL || '').replace(/\/$/, '');
const ORDER_WEBHOOK_URL = process.env.ORDER_WEBHOOK_URL || '';

const catalog = () => JSON.parse(fs.readFileSync(path.join(ROOT, 'data', 'products.json'), 'utf8'));

/* ---------- pomocné ---------- */
function send(res, status, body, headers = {}) {
  const isObj = typeof body === 'object' && !Buffer.isBuffer(body);
  res.writeHead(status, {
    'Content-Type': isObj ? 'application/json; charset=utf-8' : 'text/plain; charset=utf-8',
    'Cache-Control': 'no-store',
    ...headers
  });
  res.end(isObj ? JSON.stringify(body) : body);
}

function readBody(req, limit = 64 * 1024) {
  return new Promise((resolve, reject) => {
    const chunks = [];
    let size = 0;
    req.on('data', (c) => {
      size += c.length;
      if (size > limit) { reject(Object.assign(new Error('Príliš veľká požiadavka'), { status: 413 })); req.destroy(); return; }
      chunks.push(c);
    });
    req.on('end', () => resolve(Buffer.concat(chunks)));
    req.on('error', reject);
  });
}

const str = (v, max) => (typeof v === 'string' ? v.trim().slice(0, max) : '');

function baseUrl(req) {
  if (PUBLIC_URL) return PUBLIC_URL;
  const proto = (req.headers['x-forwarded-proto'] || '').split(',')[0] || 'http';
  return `${proto}://${req.headers.host}`;
}

function newOrderId() {
  const d = new Date();
  const ymd = d.toISOString().slice(2, 10).replace(/-/g, '');
  return `ZZ-${ymd}-${crypto.randomBytes(3).toString('hex').toUpperCase()}`;
}

async function saveOrder(order) {
  await fsp.mkdir(ORDERS_DIR, { recursive: true });
  await fsp.writeFile(path.join(ORDERS_DIR, `${order.id}.json`), JSON.stringify(order, null, 2));
}
async function loadOrder(id) {
  if (!/^ZZ-\d{6}-[0-9A-F]{6}$/.test(id)) return null;
  try { return JSON.parse(await fsp.readFile(path.join(ORDERS_DIR, `${id}.json`), 'utf8')); } catch (e) { return null; }
}

async function notify(order, event) {
  console.log(`[objednávka] ${event}: ${order.id} · ${order.payment} · ${(order.totals.total / 100).toFixed(2)} € · ${order.customer.email}`);
  if (!ORDER_WEBHOOK_URL) return;
  try {
    await fetch(ORDER_WEBHOOK_URL, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ event, order }) });
  } catch (e) { console.error('ORDER_WEBHOOK_URL zlyhal:', e.message); }
}

/* ---------- validácia a výpočet ceny (ceny sa VŽDY berú zo servera) ---------- */
function buildOrder(input) {
  const cat = catalog();
  const products = Object.fromEntries(cat.products.map((p) => [p.id, p]));
  const errors = [];

  const items = [];
  const seen = new Set();
  for (const it of Array.isArray(input.items) ? input.items.slice(0, 20) : []) {
    const p = products[it && it.id];
    const qty = Math.floor(Number(it && it.qty));
    if (!p || seen.has(p.id) || !(qty >= 1 && qty <= 24)) continue;
    seen.add(p.id);
    items.push({ id: p.id, name: p.name, volume: p.volume, price: p.price, qty, line: p.price * qty });
  }
  if (!items.length) errors.push('Košík je prázdny.');

  const payment = input.payment === 'cod' ? 'cod' : input.payment === 'card' ? 'card' : null;
  if (!payment) errors.push('Vyberte spôsob platby.');
  if (input.adult !== true) errors.push('Potvrďte, že máte viac ako 18 rokov.');
  if (input.terms !== true) errors.push('Potvrďte súhlas s obchodnými podmienkami.');

  const c = input.customer || {};
  const customer = {
    name: str(c.name, 120), email: str(c.email, 160), phone: str(c.phone, 40),
    street: str(c.street, 160), city: str(c.city, 80), zip: str(c.zip, 10),
    country: cat.shipping.countries[c.country] ? c.country : '', note: str(c.note, 500)
  };
  if (customer.name.length < 3) errors.push('Vyplňte meno a priezvisko.');
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(customer.email)) errors.push('Zadajte platný e-mail.');
  if (customer.phone.replace(/\D/g, '').length < 9) errors.push('Zadajte telefónne číslo.');
  if (!customer.street || !customer.city) errors.push('Vyplňte adresu doručenia.');
  if (!/^\d{3}\s?\d{2}$/.test(customer.zip)) errors.push('PSČ má tvar 811 01.');
  if (!customer.country) errors.push('Doručujeme na Slovensko a do Česka.');

  const subtotal = items.reduce((s, i) => s + i.line, 0);
  const shipping = subtotal >= cat.shipping.freeFrom ? 0 : cat.shipping.price;
  const cod = payment === 'cod' ? cat.shipping.codFee : 0;

  return {
    errors,
    order: {
      id: newOrderId(),
      createdAt: new Date().toISOString(),
      status: payment === 'cod' ? 'new_cod' : 'awaiting_payment',
      payment, items, customer,
      currency: cat.currency,
      totals: { subtotal, shipping, cod, total: subtotal + shipping + cod },
      shippingLabel: cat.shipping.label
    }
  };
}

/* ---------- Stripe ---------- */
function formEncode(obj, prefix, out = []) {
  for (const [k, v] of Object.entries(obj)) {
    if (v === undefined || v === null) continue;
    const key = prefix ? `${prefix}[${k}]` : k;
    if (typeof v === 'object') formEncode(v, key, out);
    else out.push(`${encodeURIComponent(key)}=${encodeURIComponent(v)}`);
  }
  return out.join('&');
}

async function stripe(method, endpoint, params) {
  const res = await fetch(`https://api.stripe.com/v1/${endpoint}`, {
    method,
    headers: {
      Authorization: `Bearer ${STRIPE_KEY}`,
      'Content-Type': 'application/x-www-form-urlencoded',
      'Stripe-Version': '2024-06-20'
    },
    body: params ? formEncode(params) : undefined
  });
  const data = await res.json();
  if (!res.ok) throw new Error(data.error && data.error.message || `Stripe ${res.status}`);
  return data;
}

async function createCheckout(order, base) {
  const line_items = {};
  order.items.forEach((it, i) => {
    line_items[i] = {
      quantity: it.qty,
      price_data: { currency: 'eur', unit_amount: it.price, product_data: { name: `${it.name} ${it.volume}` } }
    };
  });
  const session = await stripe('POST', 'checkout/sessions', {
    mode: 'payment',
    locale: 'sk',
    customer_email: order.customer.email,
    client_reference_id: order.id,
    line_items,
    shipping_options: {
      0: { shipping_rate_data: { type: 'fixed_amount', display_name: order.shippingLabel, fixed_amount: { amount: order.totals.shipping, currency: 'eur' } } }
    },
    metadata: { order_id: order.id },
    payment_intent_data: { metadata: { order_id: order.id }, description: `Objednávka ${order.id}` },
    success_url: `${base}/dakujeme.html?order=${order.id}&session_id={CHECKOUT_SESSION_ID}`,
    cancel_url: `${base}/#objednavka`
  });
  return session;
}

function verifyStripeSignature(raw, header) {
  if (!WEBHOOK_SECRET || !header) return false;
  const parts = Object.fromEntries(header.split(',').map((kv) => kv.split('=')).map(([k, ...v]) => [k, v.join('=')]));
  const t = parts.t;
  const sigs = header.split(',').filter((kv) => kv.startsWith('v1=')).map((kv) => kv.slice(3));
  if (!t || !sigs.length) return false;
  if (Math.abs(Date.now() / 1000 - Number(t)) > 300) return false;
  const expected = crypto.createHmac('sha256', WEBHOOK_SECRET).update(`${t}.${raw}`).digest('hex');
  return sigs.some((s) => s.length === expected.length && crypto.timingSafeEqual(Buffer.from(s), Buffer.from(expected)));
}

async function markPaid(orderId, session) {
  const order = await loadOrder(orderId);
  if (!order || order.status === 'paid') return order;
  order.status = 'paid';
  order.paidAt = new Date().toISOString();
  order.stripe = { session: session.id, payment_intent: session.payment_intent, amount_total: session.amount_total };
  await saveOrder(order);
  await notify(order, 'paid');
  return order;
}

/* ---------- API ---------- */
async function apiOrder(req, res) {
  let input;
  try { input = JSON.parse((await readBody(req)).toString('utf8')); } catch (e) { return send(res, 400, { error: 'Neplatná požiadavka.' }); }
  const { errors, order } = buildOrder(input || {});
  if (errors.length) return send(res, 422, { error: errors[0], errors });

  const base = baseUrl(req);

  if (order.payment === 'cod') {
    await saveOrder(order);
    await notify(order, 'new_cod');
    return send(res, 200, { url: `${base}/dakujeme.html?order=${order.id}&method=cod` });
  }

  if (!STRIPE_KEY) {
    order.status = 'demo';
    await saveOrder(order);
    return send(res, 200, { url: `${base}/dakujeme.html?order=${order.id}&method=card&demo=1` });
  }

  try {
    const session = await createCheckout(order, base);
    order.stripe = { session: session.id };
    await saveOrder(order);
    await notify(order, 'awaiting_payment');
    return send(res, 200, { url: session.url });
  } catch (e) {
    console.error('Stripe:', e.message);
    return send(res, 502, { error: 'Platobnú bránu sa nepodarilo otvoriť. Skúste to znova alebo zvoľte dobierku.' });
  }
}

async function apiStatus(req, res, url) {
  const order = await loadOrder(url.searchParams.get('order') || '');
  if (!order) return send(res, 404, { error: 'Objednávka neexistuje.' });
  const sessionId = url.searchParams.get('session_id') || '';
  // záloha k webhooku: overíme platbu priamo v Stripe
  if (order.status === 'awaiting_payment' && STRIPE_KEY && order.stripe && sessionId === order.stripe.session) {
    try {
      const s = await stripe('GET', `checkout/sessions/${encodeURIComponent(sessionId)}`);
      if (s.payment_status === 'paid') {
        await markPaid(order.id, s);
        order.status = 'paid';
      }
    } catch (e) { console.error('Stripe status:', e.message); }
  }
  return send(res, 200, {
    id: order.id, status: order.status, payment: order.payment,
    total: order.totals.total, items: order.items.map((i) => ({ name: i.name, qty: i.qty })),
    firstName: order.customer.name.split(' ')[0]
  });
}

async function apiWebhook(req, res) {
  const raw = (await readBody(req, 512 * 1024)).toString('utf8');
  if (!verifyStripeSignature(raw, req.headers['stripe-signature'])) return send(res, 400, { error: 'Neplatný podpis' });
  const event = JSON.parse(raw);
  if (event.type === 'checkout.session.completed' || event.type === 'checkout.session.async_payment_succeeded') {
    const s = event.data.object;
    const id = (s.metadata && s.metadata.order_id) || s.client_reference_id;
    if (s.payment_status === 'paid' && id) await markPaid(id, s);
  }
  return send(res, 200, { received: true });
}

/* ---------- statické súbory ---------- */
const TYPES = {
  '.html': 'text/html; charset=utf-8', '.css': 'text/css; charset=utf-8', '.js': 'text/javascript; charset=utf-8',
  '.json': 'application/json; charset=utf-8', '.svg': 'image/svg+xml', '.png': 'image/png', '.jpg': 'image/jpeg',
  '.jpeg': 'image/jpeg', '.webp': 'image/webp', '.avif': 'image/avif', '.ico': 'image/x-icon', '.woff2': 'font/woff2',
  '.mp4': 'video/mp4', '.webm': 'video/webm', '.txt': 'text/plain; charset=utf-8'
};
const BLOCKED = new Set(['server.js', 'package.json', 'README.md']);

async function serveStatic(req, res, url) {
  let rel = decodeURIComponent(url.pathname);
  if (rel.endsWith('/')) rel += 'index.html';
  const file = path.normalize(path.join(ROOT, rel));
  const relToRoot = path.relative(ROOT, file);
  if (relToRoot.startsWith('..') || path.isAbsolute(relToRoot)) return send(res, 403, 'Zakázané');
  const segs = relToRoot.split(path.sep);
  if (segs.some((s) => s.startsWith('.')) || segs[0] === 'orders' || BLOCKED.has(relToRoot) || !TYPES[path.extname(file)]) {
    return send(res, 404, 'Nenájdené');
  }
  try {
    const st = await fsp.stat(file);
    if (!st.isFile()) return send(res, 404, 'Nenájdené');
    res.writeHead(200, {
      'Content-Type': TYPES[path.extname(file)],
      'Content-Length': st.size,
      'Cache-Control': /\.(html|json)$/.test(file) ? 'no-cache' : 'public, max-age=3600',
      'X-Content-Type-Options': 'nosniff'
    });
    if (req.method === 'HEAD') return res.end();
    fs.createReadStream(file).pipe(res);
  } catch (e) {
    send(res, 404, 'Nenájdené');
  }
}

/* ---------- smerovanie ---------- */
const server = http.createServer(async (req, res) => {
  const url = new URL(req.url, 'http://localhost');
  try {
    if (url.pathname === '/api/order' && req.method === 'POST') return await apiOrder(req, res);
    if (url.pathname === '/api/order-status' && req.method === 'GET') return await apiStatus(req, res, url);
    if (url.pathname === '/api/stripe-webhook' && req.method === 'POST') return await apiWebhook(req, res);
    if (url.pathname.startsWith('/api/')) return send(res, 404, { error: 'Neznámy endpoint' });
    if (req.method === 'GET' || req.method === 'HEAD') return await serveStatic(req, res, url);
    send(res, 405, 'Metóda nie je povolená');
  } catch (e) {
    console.error(e);
    if (!res.headersSent) send(res, e.status || 500, { error: e.status ? e.message : 'Chyba servera.' });
  }
});

server.listen(PORT, () => {
  console.log(`Zlatý Žĺtok beží na http://localhost:${PORT}`);
  console.log(STRIPE_KEY ? `Stripe: ${STRIPE_KEY.startsWith('sk_live') ? 'LIVE' : 'test'} režim` : 'Stripe: DEMO režim (chýba STRIPE_SECRET_KEY)');
  if (STRIPE_KEY && !WEBHOOK_SECRET) console.log('Upozornenie: chýba STRIPE_WEBHOOK_SECRET — platby sa potvrdia len cez stránku poďakovania.');
});

/* Zlatý Žĺtok — ilustrácie generované ako SVG (fľaša, pohár, fľaky).
   Nič sa nesťahuje, všetko je vektor, takže je to ostré na každom displeji. */
(function () {
  'use strict';
  let uid = 0;
  const esc = (s) => String(s).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

  const BODY = 'M85 30 L85 148 C85 190 40 202 40 262 L40 546 Q40 572 66 572 L134 572 Q160 572 160 546 L160 262 C160 202 115 190 115 148 L115 30 Z';
  const WAX = 'M78 10 Q78 4 86 4 H114 Q122 4 122 10 V58 Q122 64 119 66 L118 82 Q116 88 114 82 L112 68 Q110 64 107 68 L106 92 Q104 99 101 92 L99 70 Q97 65 94 70 L92 80 Q90 86 88 80 L86 68 Q84 64 82 67 L81 74 Q79 78 78 72 Z';

  function icon(kind, color) {
    switch (kind) {
      case 'snow': {
        let g = '';
        for (let i = 0; i < 6; i++) {
          const a = i * 60;
          g += `<g transform="rotate(${a} 100 436)"><path d="M100 436 V414 M100 421 l-6 -6 M100 421 l6 -6 M100 428 l-4 -4 M100 428 l4 -4"/></g>`;
        }
        return `<g fill="none" stroke="${color}" stroke-width="2" stroke-linecap="round">${g}<circle cx="100" cy="436" r="3" fill="${color}"/></g>`;
      }
      case 'egg':
        return `<g><ellipse cx="100" cy="437" rx="15" ry="19" fill="none" stroke="${color}" stroke-width="2"/><circle cx="100" cy="441" r="7" fill="${color}"/></g>`;
      case 'bean':
        return `<g fill="none" stroke="${color}" stroke-width="2"><ellipse cx="100" cy="436" rx="12" ry="19" transform="rotate(-25 100 436)"/><path d="M93 420 Q104 436 107 452" stroke-linecap="round"/></g>`;
      default:
        return `<path d="M100 416 C108 428 114 436 114 443 A14 14 0 0 1 86 443 C86 436 92 428 100 416 Z" fill="${color}"/>`;
    }
  }

  /** Fľaša likéru. p = produkt z products.json */
  function bottle(p, opts = {}) {
    const id = 'zb' + (++uid);
    const c = p.colors;
    const shortName = (p.short || p.name).toUpperCase();
    const nameSize = shortName.length > 10 ? 22 : 34;
    const label = opts.plain ? '' : `
      <g>
        <rect x="44" y="300" width="112" height="198" rx="7" fill="${c.label}"/>
        <rect x="50" y="306" width="100" height="186" rx="4" fill="none" stroke="${c.accent}" stroke-width="1.3"/>
        <text x="100" y="329" text-anchor="middle" font-family="Inter, sans-serif" font-weight="700" font-size="8.2" letter-spacing="2.2" fill="${c.accent}">ZLATÝ ŽĹTOK</text>
        <path d="M70 337 H92 M108 337 H130" stroke="${c.accent}" stroke-width="1"/>
        <circle cx="100" cy="337" r="2.2" fill="${c.accent}"/>
        <text x="100" y="${shortName.length > 10 ? 376 : 382}" text-anchor="middle" font-family="Anton, Impact, sans-serif" font-size="${nameSize}" fill="${c.ink}" textLength="90" lengthAdjust="spacingAndGlyphs">${esc(shortName)}</text>
        <text x="100" y="401" text-anchor="middle" font-family="Fraunces, Georgia, serif" font-style="italic" font-size="12" fill="${c.ink}">vaječný likér</text>
        ${icon(p.icon, c.accent)}
        <text x="100" y="480" text-anchor="middle" font-family="Inter, sans-serif" font-weight="700" font-size="7.6" letter-spacing="1.6" fill="${c.accent}">${esc(p.abv)} % OBJ. · ${esc(p.volume.toUpperCase())}</text>
        <rect x="44" y="300" width="112" height="198" rx="7" fill="url(#${id}-shade)"/>
      </g>`;
    return `<svg viewBox="0 0 200 600" role="img" aria-label="${esc(p.name)}" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="${id}-liq" x1="0" x2="1">
      <stop offset="0" stop-color="${c.liquid[0]}"/><stop offset=".38" stop-color="${c.liquid[1]}"/>
      <stop offset=".62" stop-color="${c.liquid[1]}"/><stop offset="1" stop-color="${c.liquid[0]}"/>
    </linearGradient>
    <linearGradient id="${id}-shade" x1="0" x2="1">
      <stop offset="0" stop-color="#000" stop-opacity=".28"/><stop offset=".22" stop-color="#000" stop-opacity="0"/>
      <stop offset=".7" stop-color="#fff" stop-opacity=".06"/><stop offset="1" stop-color="#000" stop-opacity=".32"/>
    </linearGradient>
    <linearGradient id="${id}-wax" x1="0" x2="1">
      <stop offset="0" stop-color="${c.cap}"/><stop offset=".35" stop-color="${c.cap}" stop-opacity=".75"/><stop offset="1" stop-color="${c.cap}"/>
    </linearGradient>
    <clipPath id="${id}-clip"><path d="${BODY}"/></clipPath>
  </defs>
  <ellipse cx="100" cy="574" rx="66" ry="7" fill="#3b1a08" opacity=".16"/>
  <path d="${BODY}" fill="#FFFBF2" fill-opacity=".45"/>
  <g clip-path="url(#${id}-clip)">
    <rect x="0" y="212" width="200" height="400" fill="url(#${id}-liq)"/>
    <ellipse cx="100" cy="212" rx="70" ry="4" fill="${c.liquid[1]}"/>
    <ellipse cx="100" cy="212" rx="70" ry="4" fill="#fff" opacity=".35"/>
    <rect x="0" y="0" width="200" height="600" fill="url(#${id}-shade)"/>
  </g>
  <path d="${BODY}" fill="none" stroke="#4a2a12" stroke-opacity=".35" stroke-width="2"/>
  <path d="M55 276 V540" stroke="#fff" stroke-opacity=".55" stroke-width="7" stroke-linecap="round"/>
  <path d="M67 284 V330" stroke="#fff" stroke-opacity=".35" stroke-width="3" stroke-linecap="round"/>
  <path d="M95 44 V140" stroke="#fff" stroke-opacity=".5" stroke-width="4" stroke-linecap="round"/>
  <rect x="84" y="120" width="32" height="9" fill="${c.accent}"/>
  <path d="M100 129 l-9 22 l9 -6 l9 6 z" fill="${c.accent}"/>
  ${label}
  <path d="${WAX}" fill="url(#${id}-wax)"/>
  <path d="M86 10 Q84 30 86 52" stroke="#fff" stroke-opacity=".35" stroke-width="3" fill="none" stroke-linecap="round"/>
</svg>`;
  }

  /** Pohár s likérom, škoricou a badiánom */
  function glass() {
    const id = 'zg' + (++uid);
    let dust = '';
    const r = rng(7);
    for (let i = 0; i < 26; i++) {
      const x = 42 + r() * 76, y = 66 + r() * 10;
      dust += `<circle cx="${x.toFixed(1)}" cy="${y.toFixed(1)}" r="${(0.8 + r() * 1.4).toFixed(1)}" fill="#8A4B22" opacity=".8"/>`;
    }
    let star = '';
    for (let i = 0; i < 8; i++) star += `<ellipse cx="0" cy="-9" rx="3.4" ry="8" transform="rotate(${i * 45})" fill="#6B3418"/>`;
    return `<svg viewBox="0 0 160 240" role="img" aria-label="Pohár vaječného likéru" xmlns="http://www.w3.org/2000/svg">
  <defs>
    <linearGradient id="${id}-l" x1="0" x2="1"><stop offset="0" stop-color="#E5B04A"/><stop offset=".45" stop-color="#F9E2A0"/><stop offset="1" stop-color="#DDA43E"/></linearGradient>
    <clipPath id="${id}-c"><path d="M22 40 L34 220 Q36 230 48 230 H112 Q124 230 126 220 L138 40 Z"/></clipPath>
  </defs>
  <ellipse cx="80" cy="232" rx="56" ry="6" fill="#3b1a08" opacity=".16"/>
  <g transform="rotate(16 120 40)"><rect x="112" y="-8" width="11" height="92" rx="4" fill="#9C5428"/><rect x="114" y="-8" width="3" height="92" fill="#C27A44"/></g>
  <path d="M22 40 L34 220 Q36 230 48 230 H112 Q124 230 126 220 L138 40 Z" fill="#FFFBF2" fill-opacity=".5"/>
  <g clip-path="url(#${id}-c)">
    <rect x="0" y="70" width="160" height="170" fill="url(#${id}-l)"/>
    <ellipse cx="80" cy="70" rx="58" ry="7" fill="#FFF4D4"/>
    ${dust}
  </g>
  <path d="M22 40 L34 220 Q36 230 48 230 H112 Q124 230 126 220 L138 40" fill="none" stroke="#4a2a12" stroke-opacity=".35" stroke-width="2"/>
  <ellipse cx="80" cy="40" rx="58" ry="6" fill="none" stroke="#4a2a12" stroke-opacity=".3" stroke-width="2"/>
  <path d="M34 60 L42 200" stroke="#fff" stroke-opacity=".6" stroke-width="5" stroke-linecap="round"/>
  <g transform="translate(58 66) rotate(12)">${star}<circle r="3" fill="#C27A44"/></g>
</svg>`;
  }

  function rng(seed) {
    let a = seed >>> 0;
    return function () {
      a = (a + 0x6D2B79F5) >>> 0;
      let t = a;
      t = Math.imul(t ^ (t >>> 15), t | 1);
      t ^= t + Math.imul(t ^ (t >>> 7), t | 61);
      return ((t ^ (t >>> 14)) >>> 0) / 4294967296;
    };
  }

  /** Atramentový / žĺtkový fľak s kvapkami */
  function splat(seed, color, opts = {}) {
    const r = rng(seed);
    const N = 40, R = 100;
    const rad = Array.from({ length: N }, () => R * (0.9 + r() * 0.12));
    const spikes = opts.spikes ?? 7;
    for (let k = 0; k < spikes; k++) {
      const i = Math.floor(r() * N);
      rad[i] = R * (1.3 + r() * 0.5);
      rad[(i + 1) % N] *= 1.04;
      rad[(i - 1 + N) % N] *= 1.04;
    }
    const pts = rad.map((d, i) => {
      const a = (i / N) * Math.PI * 2;
      return [Math.cos(a) * d, Math.sin(a) * d];
    });
    let d = `M${pts[0][0].toFixed(1)} ${pts[0][1].toFixed(1)}`;
    for (let i = 0; i < N; i++) {
      const p0 = pts[(i - 1 + N) % N], p1 = pts[i], p2 = pts[(i + 1) % N], p3 = pts[(i + 2) % N];
      const c1 = [p1[0] + (p2[0] - p0[0]) / 6, p1[1] + (p2[1] - p0[1]) / 6];
      const c2 = [p2[0] - (p3[0] - p1[0]) / 6, p2[1] - (p3[1] - p1[1]) / 6];
      d += ` C${c1[0].toFixed(1)} ${c1[1].toFixed(1)} ${c2[0].toFixed(1)} ${c2[1].toFixed(1)} ${p2[0].toFixed(1)} ${p2[1].toFixed(1)}`;
    }
    let drops = '';
    const nd = opts.drops ?? 7;
    for (let k = 0; k < nd; k++) {
      const a = r() * Math.PI * 2, dist = R * (1.25 + r() * 0.5), s = 3 + r() * 9;
      drops += `<circle cx="${(Math.cos(a) * dist).toFixed(1)}" cy="${(Math.sin(a) * dist).toFixed(1)}" r="${s.toFixed(1)}"/>`;
    }
    // stekajúca kvapka
    const drip = `<path d="M${-14 + r() * 28} 80 q6 40 2 64 a7 7 0 0 1 -12 0 q-2 -26 2 -64 z"/>`;
    return `<svg viewBox="-180 -180 360 360" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"><g fill="${color}"><path d="${d} Z"/>${drops}${opts.drip === false ? '' : drip}</g></svg>`;
  }

  /** Produkt: fotka, ak je v katalógu, inak nakreslená fľaša */
  function product(p, opts = {}) {
    if (p.image) {
      return `<img class="photo" src="${esc(p.image)}" alt="${esc(p.name)}" decoding="async" ${opts.eager ? 'fetchpriority="high"' : 'loading="lazy"'} style="aspect-ratio:${p.imageRatio || 0.33}">`;
    }
    return bottle(p, opts);
  }
  const ratio = (p) => (p.image ? (p.imageRatio || 0.33) : 1 / 3);

  window.ZZArt = { bottle, glass, splat, rng, product, ratio };
})();

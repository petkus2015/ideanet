/* Kreslené ilustrácie (SVG): ovocné pozadie oválu a fľaša. Bez externých obrázkov. */
(function () {
  function rng(seed) {
    let s = seed >>> 0;
    return () => { s = (s * 1664525 + 1013904223) >>> 0; return s / 4294967296; };
  }
  const W = 300, H = 420;

  function lemons(u, r) {
    let s = `<defs>
      <radialGradient id="${u}l" cx=".38" cy=".32" r=".75"><stop offset="0" stop-color="#fff7a8"/><stop offset=".45" stop-color="#f6d42a"/><stop offset="1" stop-color="#c99307"/></radialGradient>
      <radialGradient id="${u}c" cx=".5" cy=".5" r=".5"><stop offset="0" stop-color="#fffbd2"/><stop offset="1" stop-color="#f3e07a"/></radialGradient>
    </defs><rect width="${W}" height="${H}" fill="#b98a0b"/>`;
    for (let row = -1; row < 7; row++) {
      for (let col = -1; col < 4; col++) {
        const x = col * 92 + (row % 2 ? 46 : 0) + r() * 20, y = row * 66 + r() * 18;
        const a = r() * 70 - 35;
        if (r() < 0.14) {
          s += `<g transform="translate(${x} ${y})"><circle r="46" fill="#e9c214"/><circle r="41" fill="#fff6c4"/><circle r="37" fill="url(#${u}c)"/>`;
          for (let k = 0; k < 9; k++) {
            const t = (k / 9) * Math.PI * 2, t2 = ((k + 1) / 9) * Math.PI * 2;
            s += `<path d="M0 0 L${Math.cos(t) * 35} ${Math.sin(t) * 35} A35 35 0 0 1 ${Math.cos(t2) * 35} ${Math.sin(t2) * 35}Z" fill="#f7e26b" stroke="#fffbe0" stroke-width="2.5" opacity=".9"/>`;
          }
          s += `<circle r="5" fill="#fffbe0"/></g>`;
        } else {
          s += `<g transform="translate(${x} ${y}) rotate(${a})"><ellipse rx="56" ry="40" fill="url(#${u}l)"/><ellipse cx="55" rx="9" ry="7" fill="#e3b811"/><ellipse cx="-55" rx="7" ry="6" fill="#d9ad0d"/><ellipse cx="-14" cy="-16" rx="18" ry="7" fill="#fff" opacity=".28"/></g>`;
        }
      }
    }
    return s;
  }

  function raspberries(u, r) {
    let s = `<defs><radialGradient id="${u}r" cx=".35" cy=".3" r=".8"><stop offset="0" stop-color="#ff9aac"/><stop offset=".5" stop-color="#d8234c"/><stop offset="1" stop-color="#7d0a25"/></radialGradient></defs>
      <rect width="${W}" height="${H}" fill="#5e0a1c"/>`;
    const berry = (x, y, R) => {
      let g = `<g transform="translate(${x} ${y}) rotate(${r() * 360})">`;
      for (let k = 0; k < 12; k++) { const t = k / 12 * Math.PI * 2; g += `<circle cx="${Math.cos(t) * R * .78}" cy="${Math.sin(t) * R * .78}" r="${R * .27}" fill="url(#${u}r)"/>`; }
      for (let k = 0; k < 6; k++) { const t = k / 6 * Math.PI * 2 + .3; g += `<circle cx="${Math.cos(t) * R * .42}" cy="${Math.sin(t) * R * .42}" r="${R * .28}" fill="url(#${u}r)"/>`; }
      return g + `<circle r="${R * .27}" fill="url(#${u}r)"/></g>`;
    };
    for (let row = -1; row < 8; row++) for (let col = -1; col < 5; col++) {
      s += berry(col * 66 + (row % 2 ? 33 : 0) + r() * 14, row * 58 + r() * 14, 32 + r() * 6);
    }
    return s;
  }

  function oranges(u, r) {
    let s = `<defs>
      <radialGradient id="${u}o" cx=".35" cy=".3" r=".8"><stop offset="0" stop-color="#ffc56b"/><stop offset=".55" stop-color="#f58a12"/><stop offset="1" stop-color="#b45305"/></radialGradient>
      <radialGradient id="${u}s" cx=".5" cy=".5" r=".5"><stop offset="0" stop-color="#ffd089"/><stop offset="1" stop-color="#ff9a1f"/></radialGradient>
    </defs><rect width="${W}" height="${H}" fill="#c86a06"/>`;
    for (let row = -1; row < 5; row++) for (let col = -1; col < 3; col++) {
      const x = col * 140 + (row % 2 ? 70 : 0) + r() * 24, y = row * 104 + r() * 24;
      if (r() < 0.55) {
        const R = 62 + r() * 12;
        s += `<g transform="translate(${x} ${y}) rotate(${r() * 40})"><circle r="${R}" fill="#ef8510"/><circle r="${R - 6}" fill="#fff1d6"/><circle r="${R - 10}" fill="url(#${u}s)"/>`;
        for (let k = 0; k < 10; k++) {
          const t = k / 10 * Math.PI * 2, t2 = (k + 1) / 10 * Math.PI * 2, q = R - 11;
          s += `<path d="M0 0 L${Math.cos(t) * q} ${Math.sin(t) * q} A${q} ${q} 0 0 1 ${Math.cos(t2) * q} ${Math.sin(t2) * q}Z" fill="url(#${u}s)" stroke="#fff1d6" stroke-width="3"/>`;
        }
        s += `<circle r="7" fill="#fff1d6"/></g>`;
      } else {
        s += `<circle cx="${x}" cy="${y}" r="${66 + r() * 10}" fill="url(#${u}o)"/><circle cx="${x - 20}" cy="${y - 24}" r="14" fill="#fff" opacity=".18"/>`;
      }
    }
    return s;
  }

  function greens(u, r) {
    const pal = ['#2e6b1f', '#3f8a27', '#57a535', '#73bd48', '#8fd060', '#4a9a2c'];
    let s = `<rect width="${W}" height="${H}" fill="#1f4d15"/>`;
    for (let i = 0; i < 70; i++) {
      const cx = r() * (W + 40) - 20, cy = r() * (H + 40) - 20;
      for (let k = 0; k < 11; k++) {
        const a = r() * Math.PI * 2, d = r() * 22, rad = 5 + r() * 9;
        const c = pal[(r() * pal.length) | 0];
        s += `<circle cx="${(cx + Math.cos(a) * d).toFixed(1)}" cy="${(cy + Math.sin(a) * d).toFixed(1)}" r="${rad.toFixed(1)}" fill="${c}"/>`;
        s += `<circle cx="${(cx + Math.cos(a) * d - rad * .3).toFixed(1)}" cy="${(cy + Math.sin(a) * d - rad * .3).toFixed(1)}" r="${(rad * .4).toFixed(1)}" fill="#b9ec86" opacity=".35"/>`;
      }
    }
    return s;
  }

  const KINDS = { lemon: lemons, raspberry: raspberries, orange: oranges, greens };
  let n = 0;

  window.ART = {
    fruit(kind, seed = 7) {
      const u = 'f' + (n++);
      return `<svg viewBox="0 0 ${W} ${H}" preserveAspectRatio="xMidYMid slice" aria-hidden="true">${KINDS[kind](u, rng(seed))}</svg>`;
    },
    bottle(p) {
      const u = 'b' + (n++);
      const ink = p.ink || '#fff';
      const ingr = p.ingredients.slice(0, 5).map((t, i) =>
        `<text x="54" y="${350 + i * 12}" font-size="9.5" font-weight="600">${t}</text>`).join('');
      return `<svg viewBox="0 0 200 480" aria-hidden="true">
        <defs>
          <linearGradient id="${u}g" x1="0" x2="1"><stop offset="0" stop-color="${p.dark}"/><stop offset=".3" stop-color="${p.liquid}"/><stop offset=".62" stop-color="${p.liquid}"/><stop offset="1" stop-color="${p.dark}"/></linearGradient>
          <linearGradient id="${u}c" x1="0" x2="1"><stop offset="0" stop-color="#b8b8b4"/><stop offset=".4" stop-color="#ffffff"/><stop offset=".75" stop-color="#e2e2de"/><stop offset="1" stop-color="#a9a9a5"/></linearGradient>
          <radialGradient id="${u}sh" cx=".5" cy=".5" r=".5"><stop offset="0" stop-color="#000" stop-opacity=".35"/><stop offset="1" stop-color="#000" stop-opacity="0"/></radialGradient>
        </defs>
        <ellipse cx="100" cy="468" rx="96" ry="10" fill="url(#${u}sh)"/>
        <path d="M66 58h68v14c0 12 46 22 46 58v300q0 32-32 32H52q-32 0-32-32V130c0-36 46-46 46-58Z" fill="url(#${u}g)"/>
        <path d="M66 58h68v14c0 12 46 22 46 58v300q0 32-32 32H52q-32 0-32-32V130c0-36 46-46 46-58Z" fill="none" stroke="#fff" stroke-opacity=".35" stroke-width="2"/>
        <rect x="28" y="140" width="9" height="300" rx="4.5" fill="#fff" opacity=".28"/>
        <rect x="166" y="150" width="4" height="280" rx="2" fill="#fff" opacity=".18"/>
        <rect x="60" y="6" width="80" height="58" rx="7" fill="url(#${u}c)"/>
        ${Array.from({ length: 12 }, (_, i) => `<line x1="${66 + i * 6}" y1="10" x2="${66 + i * 6}" y2="60" stroke="#000" stroke-opacity=".07" stroke-width="2"/>`).join('')}
        <g fill="${ink}" font-family="'Barlow Condensed', 'Arial Narrow', sans-serif">
          <rect x="42" y="172" width="116" height="260" rx="5" fill="#fff" fill-opacity=".1" stroke="${ink}" stroke-opacity=".5" stroke-width="1.4"/>
          <text x="100" y="230" font-size="46" font-weight="600" text-anchor="middle">${p.num}</text>
          <text x="100" y="247" font-size="9" font-weight="600" text-anchor="middle" letter-spacing="1.5">${p.day.toUpperCase()}</text>
          <line x1="54" y1="258" x2="146" y2="258" stroke="${ink}" stroke-opacity=".5"/>
          <text x="100" y="274" font-size="8.5" font-weight="600" text-anchor="middle" letter-spacing="1">LISOVANÉ ZA STUDENA</text>
          <text x="100" y="304" font-size="22" font-weight="700" text-anchor="middle" letter-spacing="1">${p.labelTop}</text>
          <text x="100" y="327" font-size="22" font-weight="700" text-anchor="middle" letter-spacing="1">DETOX</text>
          ${ingr}
          <text x="146" y="420" font-size="9" font-weight="600" text-anchor="end">300 ml</text>
        </g>
      </svg>`;
    }
  };
})();

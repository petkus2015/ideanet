const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const DIR = __dirname;
const VON = path.join(DIR, 'out');

/* ── Jediné miesto, kde sa mení dátum konca akcie. ── */
const TERMIN = process.argv[2] || 'do 30. 11.';

const KREATIVY = [
  ['a-cena.html',   'A-cena'],
  ['b-predpo.html', 'B-pred-po'],
  ['c-zoznam.html', 'C-zoznam'],
];
const FORMATY = [[1080, 1080], [1080, 1350]];

(async () => {
  fs.mkdirSync(VON, { recursive: true });
  const b = await chromium.launch();

  /* Starý web ako telefón 9:16, aby sa v ráme nič neorezalo. */
  const sp = await b.newPage({ viewport: { width: 1080, height: 1920 } });
  await sp.goto('file://' + path.join(DIR, 'stary-web.html'));
  await sp.waitForTimeout(400);
  await sp.screenshot({ path: path.join(DIR, 'stary-1.jpg'), type: 'jpeg', quality: 86 });
  await sp.close();

  for (const [subor, nazov] of KREATIVY) {
    const html = fs.readFileSync(path.join(DIR, subor), 'utf8').split('{{TERMIN}}').join(TERMIN);
    const docasny = path.join(DIR, '.render-' + subor);
    fs.writeFileSync(docasny, html);

    for (const [w, h] of FORMATY) {
      const p = await b.newPage({ viewport: { width: w, height: h } });
      await p.goto('file://' + docasny);
      await p.waitForLoadState('networkidle').catch(() => {});
      await p.waitForTimeout(600);
      await p.screenshot({ path: `${VON}/${nazov}-${w}x${h}.jpg`, type: 'jpeg', quality: 92 });
      await p.close();
    }
    fs.unlinkSync(docasny);
  }

  await b.close();
  for (const f of fs.readdirSync(VON).sort()) {
    console.log(f, Math.round(fs.statSync(path.join(VON, f)).size / 1024) + ' kB');
  }
})();

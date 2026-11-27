#!/usr/bin/env python3
"""Zostaví ukazka.html: celý web v jednom súbore na poslanie klientovi.
Funguje po otvorení dvojklikom (bez servera); objednávka je len ukážková."""
import json, pathlib, re
root = pathlib.Path(__file__).parent
read = lambda n: (root / n).read_text(encoding='utf-8')
html = read('index.html')
catalog = json.dumps(json.loads(read('data/products.json')), ensure_ascii=False)
html = html.replace('<link rel="stylesheet" href="styles.css">', '<style>\n' + read('styles.css') + '\n</style>')
scripts = ('<script>window.LIKER_DEMO = true; window.LIKER_STANDALONE = true; window.LIKER_CATALOG = ' + catalog + ';</script>\n'
           '<script>\n' + read('art.js') + '\n</script>\n<script>\n' + read('app.js') + '\n</script>')
html = re.sub(r'<script src="art.js" defer></script>\s*<script src="app.js" defer></script>', lambda m: scripts, html)
assert 'src="app.js"' not in html and 'href="styles.css"' not in html
(root / 'zlaty-zltok-ukazka.html').write_text(html, encoding='utf-8')
print('zlaty-zltok-ukazka.html', round(len(html.encode()) / 1024), 'kB')

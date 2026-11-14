"""Zloží samostatný súbor titulky-browser.html (bez servera) zo static/index.html a styles.css."""
import re
from pathlib import Path

here = Path(__file__).parent
css = (here / "static/styles.css").read_text()
html = (here / "static/index.html").read_text()
body = re.search(r"<body>(.*)<script", html, re.S).group(1)

body = body.replace('<p class="note" id="retention-note"></p>',
    '<p class="note">Všetko beží vo vašom prehliadači. Nič sa neposiela na server.</p>')
body = body.replace('''    <p class="error" id="upload-error" hidden></p>
  </section>''', '''    <div class="row">
      <label for="model">Model</label>
      <select id="model">
        <option value="tiny">Rýchly (tiny, približne 40 MB)</option>
        <option value="base" selected>Vyvážený (base, približne 80 MB)</option>
        <option value="small">Presný (small, približne 250 MB)</option>
      </select>
    </div>
    <p class="hint">Model sa pri prvom použití stiahne a prehliadač si ho zapamätá. Slovenčina je presnejšia s modelom small.</p>
    <p class="error" id="upload-error" hidden></p>
  </section>''', 1)
body = re.sub(r'accept="[^"]*"', 'accept="video/*,.mp4,.m4v,.mov,.mkv,.webm,.avi,.ts,.mts,.3gp,.mpg,.mpeg,.hevc,.h265"', body, count=1)
body = body.replace('<div class="stage-wrap">', '<p class="banner" id="noplay" hidden>Prehliadač nevie prehrať obraz tohto videa (pravdepodobne kodek H.265/HEVC alebo 10-bitový H.264). Titulky sa rozpoznali zo zvuku a môžeš ich opraviť a stiahnuť ako SRT alebo VTT. Náhľad a export videa nie sú k dispozícii. Skús Safari alebo Chrome na Macu (HEVC prehrávajú), alebo video prevedieš do MP4 s H.264 a nahráš znova.</p>\n        <div class="stage-wrap">', 1)
body = body.replace('<div class="bar" id="busy-bar" hidden><i></i></div>',
                    '<div class="bar" id="busy-bar" hidden><i></i></div>')
body = body.replace('<p class="expiry" id="expiry"></p>\n        <button class="danger" id="delete">Zmazať hneď</button>',
    '<p class="expiry" id="expiry">Nič sa neukladá na server. Po zatvorení stránky je všetko preč, preto si súbory stiahni.</p>\n        <button class="danger" id="delete">Začať odznova</button>')
body = body.replace('<p class="error" id="export-error" hidden></p>',
    '<p class="hint" id="export-hint" hidden>Export beží v reálnom čase, nechaj túto kartu otvorenú.</p>\n        <p class="error" id="export-error" hidden></p>')
body = body.replace('<p class="hint">Oprav chyby v rozpoznanom texte. Klik na čas skočí na dané miesto vo videu.</p>',
    '<p class="hint">Oprav chyby v rozpoznanom texte. Klik na čas skočí na dané miesto vo videu.</p>')
body = body.replace('<a class="dl" id="dl-mp4">Stiahnuť video (MP4)</a>', '<a class="dl" id="dl-mp4">Stiahnuť video</a>')
body = body.replace('''<div class="group">
          <div class="label">Veľkosť</div>''', '''<p class="hint">Titulky môžeš presunúť aj myšou alebo prstom priamo v náhľade.</p>
        <div class="group">
          <div class="label">Zvýraznenie slov</div>
          <div class="inline"><label><input type="checkbox" id="highlight"> Zvýrazňovať práve vyslovené slovo</label></div>
          <div class="inline" style="margin-top:8px"><input type="color" id="hl"><small class="hint">Farba zvýraznenia</small></div>
          <div class="inline" style="margin-top:8px"><label><input type="checkbox" id="roam"> Dynamický štýl: meniť polohu pri každej fráze</label></div>
        </div>
        <div class="group">
          <div class="label">Veľkosť</div>''', 1)
css = css.replace(":root{--bg:#070914;", ":root{color-scheme:dark;--bg:#070914;")
css += """
.banner{background:#2a1d12;border:1px solid #6b4a24;color:#f0c58a;border-radius:12px;padding:12px 14px;margin:0 0 12px;font-size:13px}
.cap{left:auto;right:auto;width:max-content;max-width:84%;pointer-events:auto;cursor:grab;touch-action:none;transform:translate(-50%,-50%);user-select:none}
.inline label{display:flex;gap:8px;align-items:center;cursor:pointer}
.row select{max-width:100%}
@media(prefers-reduced-motion:reduce){.spinner{animation:none}}
"""
js = (here / "browser.js").read_text()
page = f'''<!doctype html>
<html lang="sk">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Titulky</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&family=Montserrat:wght@400;700&family=Poppins:wght@400;700&family=Bebas+Neue&family=Roboto+Slab:wght@400;700&family=Playfair+Display:wght@700&display=swap">
<style>
{css}
</style>
</head>
<body>
{body}
<script type="module">
{js}
</script>
</body>
</html>
'''
(here / "titulky-browser.html").write_text(page, encoding="utf-8")
print("ok", len(page))

# --- ukážka na webe (artifact): vzorové titulky, výsledok sa prehrá na stránke ---
import sys
if len(sys.argv) > 1:
    demo_js = js.replace("const DEMO = false;", "const DEMO = true;")
    demo_body = body.replace("Všetko beží vo vašom prehliadači. Nič sa neposiela na server.", "Ukážka: titulky sú vzorové, video ostáva vo vašom prehliadači.")
    frag = f'''<title>Titulky</title>
<meta name="claude:viewport-fit-sides" content="cover">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;700;800&family=Montserrat:wght@400;700&family=Poppins:wght@400;700&family=Bebas+Neue&family=Roboto+Slab:wght@400;700&family=Playfair+Display:wght@700&display=swap">
<style>
{css}
html,body{{margin:0}}
body{{padding-left:max(16px,env(safe-area-inset-left,0px));padding-right:max(16px,env(safe-area-inset-right,0px))}}
.top,main{{padding-left:0;padding-right:0}}
</style>
{demo_body}
<script type="module">
{demo_js}
</script>
'''
    Path(sys.argv[1]).write_text(frag, encoding="utf-8")
    print("demo", sys.argv[1])

"""Zloží samostatný súbor titulky-browser.html (bez servera) zo šablóny browser.html a skriptu browser.js.

  python3 build_browser.py                 -> titulky-browser.html a php/app.html
  python3 build_browser.py ukazka.html     -> navyše fragment pre ukážku na webe (artifact)
"""
import re
import sys
from pathlib import Path

here = Path(__file__).parent
template = (here / "browser.html").read_text(encoding="utf-8")
js = (here / "browser.js").read_text(encoding="utf-8")

page = template.replace("/*JS*/", js)
(here / "titulky-browser.html").write_text(page, encoding="utf-8")
(here / "php").mkdir(exist_ok=True)
(here / "php" / "app.html").write_text(page, encoding="utf-8")
print("ok", len(page))

if len(sys.argv) > 1:  # ukážka: vzorové titulky, výsledok sa prehrá v karte
    demo = template.replace("/*JS*/", js.replace("const DEMO = false;", "const DEMO = true;"))
    demo = demo.replace("Zmeň svoj obsah na formáty pripravené na zverejnenie a pridaj automatické titulky.",
                        "Ukážka: titulky sú vzorové, video ostáva vo vašom prehliadači.")
    head_links = re.search(r'<link rel="stylesheet"[^>]*>', demo).group(0)
    style = re.search(r"<style>.*?</style>", demo, re.S).group(0)
    body = re.search(r"<body>(.*)</body>", demo, re.S).group(1)
    gutters = ("<style>.app{padding-left:max(0px,env(safe-area-inset-left,0px));padding-right:max(0px,env(safe-area-inset-right,0px))}"
               "@media(max-width:820px){.content{padding-left:max(16px,env(safe-area-inset-left,0px));padding-right:max(16px,env(safe-area-inset-right,0px))}}</style>")
    frag = f'<title>Titulky</title>\n<meta name="claude:viewport-fit-sides" content="cover">\n{head_links}\n{style}\n{gutters}\n{body}\n'
    Path(sys.argv[1]).write_text(frag, encoding="utf-8")
    print("demo", sys.argv[1])

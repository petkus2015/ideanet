#!/usr/bin/env python3
"""Zostaví WordPress plugin „Meškanie vlakov“ z webu vo vlaky/.

  python3 scripts/build_wp_plugin.py

- vlaky/app.js      -> wordpress/meskanie-vlakov/assets/app.js (bez zmeny)
- vlaky/styles.css  -> wordpress/meskanie-vlakov/assets/app.css (upravené pre Shadow DOM)
- vlaky/index.html  -> wordpress/meskanie-vlakov/templates/app.html (len obsah stránky)
- celý plugin       -> wordpress/meskanie-vlakov.zip (na nahratie do WordPressu)

PHP súbory pluginu sa needitujú tu, ale priamo vo wordpress/meskanie-vlakov/.
"""
import re
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
SRC = ROOT / "vlaky"
PLUGIN = ROOT / "wordpress" / "meskanie-vlakov"


def shadow_css(css: str) -> str:
    """Štýly samostatnej stránky -> štýly vnútri Shadow DOM (téma ich neovplyvní a ony nezasiahnu tému)."""
    css = re.sub(r"(?m)^html \{[^}]*\}\n", "", css)
    css = re.sub(r"(?m)^body::before \{[^}]*\}\n", "", css)
    css = re.sub(r"(?m)^\s*background-attachment: fixed;\n", "", css)
    css = css.replace("html.big body {", ":host(.big) .mv {")
    css = re.sub(r"(?m)^:root \{", ":host {", css)
    css = re.sub(r"(?m)^body \{", ".mv {", css)
    if re.search(r"(?m)^(html|body)\b|:root", css):
        raise SystemExit("styles.css obsahuje globálny selektor, ktorý build nevie previesť")
    head = """/* Vygenerované z vlaky/styles.css skriptom scripts/build_wp_plugin.py — needitovať ručne. */
:host { all: initial; display: block; }
[hidden] { display: none !important; }
button, input, select { font: inherit; }
.mv { container: mv / inline-size; border-radius: 18px; overflow: clip; text-align: left; }
:host([data-width="plna"]) .mv { border-radius: 0; }
"""
    tail = """
/* v rámci stránky WordPressu hlavička nepláva nad obsahom témy */
.top { position: static; }
"""
    return head + container_queries(css) + tail


def container_queries(css: str) -> str:
    """@media (max-width) -> @container: rozloženie sa riadi šírkou miesta na stránke, nie šírkou okna.
    Výnimkou je detail vlaku (dialóg), ten sa zobrazuje cez celé okno."""
    out, i = [], 0
    for m in re.finditer(r"@media \(max-width: (\d+)px\) \{", css):
        if m.start() < i:
            continue
        depth, j = 1, m.end()
        while depth:
            depth += {"{": 1, "}": -1}.get(css[j], 0)
            j += 1
        block = css[m.start():j]
        if ".tdlg {" not in block:
            block = block.replace(m.group(0), f"@container mv (max-width: {m.group(1)}px) {{", 1)
        out.append(css[i:m.start()] + block)
        i = j
    return "".join(out) + css[i:]


def template(html: str) -> str:
    body = html.split("<body>", 1)[1].split('<script src="app.js"></script>', 1)[0]
    return "<!-- Vygenerované z vlaky/index.html skriptom scripts/build_wp_plugin.py — needitovať ručne. -->\n" + body.strip() + "\n"


def main():
    (PLUGIN / "assets").mkdir(parents=True, exist_ok=True)
    (PLUGIN / "templates").mkdir(parents=True, exist_ok=True)
    (PLUGIN / "assets" / "app.js").write_text((SRC / "app.js").read_text("utf-8"), "utf-8")
    (PLUGIN / "assets" / "app.css").write_text(shadow_css((SRC / "styles.css").read_text("utf-8")), "utf-8")
    (PLUGIN / "templates" / "app.html").write_text(template((SRC / "index.html").read_text("utf-8")), "utf-8")

    out = PLUGIN.parent / "meskanie-vlakov.zip"
    with zipfile.ZipFile(out, "w", zipfile.ZIP_DEFLATED) as z:
        for f in sorted(PLUGIN.rglob("*")):
            if f.is_file() and "__pycache__" not in f.parts:
                z.write(f, f.relative_to(PLUGIN.parent))
    print(f"plugin: {out.relative_to(ROOT)} ({out.stat().st_size // 1024} kB)")


if __name__ == "__main__":
    main()

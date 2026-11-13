#!/usr/bin/env python3
"""Stiahne fotky pre blog z Wikimedia Commons (licencie CC / public domain) do img/ a zapíše autorov do img/credits.json.
Spustenie na vlastnom počítači:  python3 scripts/fetch_photos.py   (voliteľne: pip install pillow na zmenšenie)
Po stiahnutí skopíruj obsah credits.json do konštanty CREDITS v app.js, aby sa pod článkom zobrazil autor."""
import json, os, re, subprocess, sys, time, urllib.parse, urllib.request
OUT = os.path.join(os.path.dirname(os.path.abspath(__file__)), '..', 'img'); os.makedirs(OUT, exist_ok=True)
UA = {'User-Agent': 'adventuro-blog-demo/1.0 (personal project)'}
JOBS = {  # id článku -> hľadaný výraz na Commons
  'marinha': 'Praia da Marinha Algarve', 'navagio': 'Navagio beach Zakynthos', 'reynisfjara': 'Reynisfjara black sand beach',
  'dolomity': 'Tre Cime di Lavaredo', 'tatry': 'Rysy Vysoke Tatry', 'piramidy': 'Giza pyramids desert',
  'petra': 'Petra Treasury Al-Khazneh', 'borneo': 'Danum Valley rainforest', 'kyoto': 'Fushimi Inari Taisha torii path'}
def get(url):
    for k in range(6):
        try: return urllib.request.urlopen(urllib.request.Request(url, headers=UA), timeout=60).read()
        except Exception as e: print('  znova:', e); time.sleep(10 * (k + 1))
def search(term):
    q = dict(action='query', format='json', generator='search', gsrsearch=term + ' filetype:bitmap', gsrnamespace=6, gsrlimit=10,
             prop='imageinfo', iiprop='url|size|extmetadata', iiurlwidth=1000)
    r = json.loads(get('https://commons.wikimedia.org/w/api.php?' + urllib.parse.urlencode(q)) or b'{}')
    return sorted(r.get('query', {}).get('pages', {}).values(), key=lambda p: p['index'])
credits = {}
for id_, term in JOBS.items():
    print('##', id_)
    for pg in search(term):
        i = pg['imageinfo'][0]; m = i['extmetadata']; lic = m.get('LicenseShortName', {}).get('value', '')
        if not re.search(r'CC|Public', lic) or i['width'] < 1.3 * i['height'] or i['width'] < 1800: continue
        data = get(i['thumburl'])
        if not data: continue
        path = os.path.join(OUT, id_ + '.jpg'); open(path, 'wb').write(data)
        credits[id_] = {'title': pg['title'], 'license': lic, 'page': i['descriptionurl'],
                        'author': re.sub('<[^>]+>', '', m.get('Artist', {}).get('value', '')).strip()[:60]}
        print('  ->', credits[id_]['title'], lic); time.sleep(3); break
json.dump(credits, open(os.path.join(OUT, 'credits.json'), 'w'), ensure_ascii=False, indent=1)
print('Hotovo. Skontroluj fotky v img/ a skopíruj credits.json do CREDITS v app.js.')

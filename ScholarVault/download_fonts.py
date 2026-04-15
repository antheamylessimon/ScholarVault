import urllib.request
import re
import os

headers = {'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36'}

urls = {
    "google_sans.css": "https://fonts.googleapis.com/css2?family=Google+Sans:ital,opsz,wght@0,17..18,400..700;1,17..18,400..700&display=swap",
    "material_icons.css": "https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0"
}

os.makedirs('assets/fonts', exist_ok=True)
os.makedirs('assets/css', exist_ok=True)

for name, url in urls.items():
    print(f"Fetching {url}")
    req = urllib.request.Request(url, headers=headers)
    try:
        css = urllib.request.urlopen(req).read().decode('utf-8')
        links = re.findall(r'url\((https://[^)]+)\)', css)
        for link in set(links):
            fname = link.split('/')[-1]
            # some fonts might have parameters like ?v=xxx or similar in the name, clean it
            fname = fname.split('?')[0]
            if not fname.endswith('.woff2'):
                fname += '.woff2'
                
            fpath = os.path.join('assets/fonts', fname)
            print(f"Downloading {link} to {fpath}")
            if not os.path.exists(fpath):
                req_font = urllib.request.Request(link, headers=headers)
                font_data = urllib.request.urlopen(req_font).read()
                with open(fpath, 'wb') as f:
                    f.write(font_data)
            
            # Use relative URL from css dir to fonts dir
            css = css.replace(link, f'../fonts/{fname}')
        
        with open(os.path.join('assets/css', name), 'w', encoding='utf-8') as f:
            f.write(css)
    except Exception as e:
        print(f"Error processing {name}: {e}")

print("Done")

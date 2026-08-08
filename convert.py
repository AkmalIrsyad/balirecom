import pandas as pd
import json
import sys

def get_stable_index(name, num_templates):
    hash_val = 5381
    for char in name:
        hash_val = ((hash_val << 5) + hash_val) + ord(char)
    return (hash_val & 0xFFFFFFFF) % num_templates

def generate_description(name, kategori, kabupaten):
    templates = {
        "Alam": [
            f"Destinasi wisata alam {name} di {kabupaten} menawarkan panorama alam yang asri dan udara yang menyegarkan, sangat cocok untuk bersantai dan melepas penat.",
            f"Nikmati keindahan pemandangan alam tropis yang memesona di {name}, salah satu spot wisata alam terpopuler di {kabupaten}.",
            f"{name} merupakan objek wisata alam tersembunyi di {kabupaten} yang menyuguhkan suasana tenang, pemandangan hijau yang indah, dan udara bersih khas pegunungan.",
            f"Menawarkan pesona alam Bali yang eksotis, {name} di {kabupaten} adalah tempat terbaik untuk menikmati keindahan matahari terbit atau berjalan santai."
        ],
        "Budaya": [
            f"{name} adalah warisan budaya yang bernilai sejarah tinggi di {kabupaten}, menampilkan keunikan arsitektur tradisional dan adat istiadat Bali.",
            f"Rasakan kekayaan budaya Bali yang sakral di {name}, destinasi wisata budaya di {kabupaten} yang sarat akan nilai religi dan seni.",
            f"Menyajikan peninggalan bersejarah dan tradisi adat lokal, {name} di {kabupaten} sangat direkomendasikan untuk edukasi budaya Bali.",
            f"Temukan keunikan adat, pertunjukan seni tradisional, dan suasana spiritual Bali yang kental saat mengunjungi {name} di {kabupaten}."
        ],
        "Rekreasi": [
            f"Tempat wisata rekreasi {name} di {kabupaten} menyajikan berbagai wahana seru, aktivitas air menarik, dan suasana liburan keluarga yang menyenangkan.",
            f"Nikmati akhir pekan yang seru bersama teman dan keluarga di {name}, pusat rekreasi populer di {kabupaten} dengan fasilitas lengkap.",
            f"Menawarkan pemandangan pantai yang indah dan tempat santai yang nyaman, {name} di {kabupaten} adalah tempat rekreasi favorit bagi wisatawan.",
            f"Lepaskan penat dengan berkunjung ke {name} di {kabupaten}, destinasi rekreasi modern yang menawarkan aktivitas menyenangkan untuk segala usia."
        ],
        "Umum": [
            f"Sebagai salah satu ikon wisata populer di {kabupaten}, {name} menawarkan pengalaman liburan lengkap yang cocok untuk semua kalangan.",
            f"Temukan keindahan dan kenyamanan rekreasi keluarga di {name}, destinasi wisata favorit yang terletak strategis di {kabupaten}.",
            f"Objek wisata {name} di {kabupaten} memiliki berbagai spot foto menarik dan fasilitas penunjang wisata yang sangat nyaman bagi pengunjung.",
            f"Menawarkan kombinasi pemandangan indah dan aksesibilitas yang mudah, {name} di {kabupaten} patut masuk dalam rencana perjalanan Anda."
        ],
        "Lainnya": [
            f"Objek wisata {name} yang berada di {kabupaten} menawarkan daya tarik tersendiri yang unik dan menarik untuk dikunjungi saat liburan.",
            f"Jelajahi pesona keindahan dan keunikan lokal di {name}, destinasi wisata menarik yang terletak di wilayah {kabupaten}.",
            f"Menyuguhkan suasana santai khas Bali, {name} di {kabupaten} menjadi pilihan yang pas untuk mengisi waktu libur akhir pekan Anda.",
            f"Nikmati kunjungan yang berkesan di {name}, salah satu destinasi menarik di {kabupaten} dengan pemandangan yang memikat mata."
        ]
    }
    
    cat_key = "Lainnya"
    kategori_lower = str(kategori).lower()
    if "alam" in kategori_lower:
        cat_key = "Alam"
    elif "budaya" in kategori_lower or "pura" in kategori_lower or "sejarah" in kategori_lower or "candi" in kategori_lower:
        cat_key = "Budaya"
    elif "rekreasi" in kategori_lower or "pantai" in kategori_lower or "laut" in kategori_lower:
        cat_key = "Rekreasi"
    elif "umum" in kategori_lower or "taman" in kategori_lower or "kebun" in kategori_lower:
        cat_key = "Umum"
        
    idx = get_stable_index(name, len(templates[cat_key]))
    return templates[cat_key][idx]

def clean_coordinate(val):
    if pd.isna(val) or val == "":
        return 0.0
    val_str = str(val).strip().replace('"', '').replace("'", "")
    if not val_str:
        return 0.0
    val_str = val_str.replace(',', '.')
    parts = val_str.split('.')
    if len(parts) > 2:
        val_str = parts[0] + '.' + ''.join(parts[1:])
    try:
        return float(val_str)
    except ValueError:
        return 0.0

def classify_specific_kategori(row):
    name = str(row.get('nama', '')).lower()
    desc = str(row.get('deskripsi', '')).lower()
    combined = f"{name} {desc}"
    
    if any(k in combined for k in ['air terjun', 'waterfall', 'grobogan', 'grojogan']):
        return 'Air Terjun'
    if any(k in combined for k in ['snorkeling', 'diving', 'surfing', 'surf', 'dolphin', 'turtle', 'penyu', 'water sport', 'rafting', 'bahari', 'reef']):
        return 'Wisata Bahari & Water Sport'
    if any(k in combined for k in ['pantai', 'beach', 'pesisir', 'tanjung']):
        return 'Pantai'
    if any(k in combined for k in ['pura', 'temple', 'shrine', 'vihara', 'klenteng', 'pewaregan', 'religi', 'suci']):
        return 'Pura & Tempat Religi'
    if any(k in combined for k in ['gunung', 'mount', 'bukit', 'hill', 'puncak', 'volcano', 'kintamani']):
        return 'Pegunungan & Bukit'
    if any(k in combined for k in ['danau', 'lake', 'embung']):
        return 'Danau'
    if any(k in combined for k in ['air panas', 'hot spring', 'pemandian', 'mata air', 'spring', 'melukat', 'penglukatan', 'yeh panes']):
        return 'Pemandian & Mata Air'
    if any(k in combined for k in ['hutan', 'forest', 'monkey', 'suaka', 'konservasi', 'savana', 'mangrove']):
        return 'Hutan & Suaka Alam'
    if any(k in combined for k in ['museum', 'galeri', 'gallery', 'monumen', 'monument', 'tugu', 'patung']):
        return 'Museum & Monumen'
    if any(k in combined for k in ['desa wisata', 'village', 'puri', 'palace', 'istana', 'candi', 'sejarah']):
        return 'Desa Adat & Bersejarah'
    if any(k in combined for k in ['kebun', 'agrowisata', 'agro', 'kopi', 'strawberry', 'banana', 'sawah', 'rice terrace', 'subak']):
        return 'Agrowisata & Sawah'
    if any(k in combined for k in ['pasar', 'market', 'tenun', 'kerajinan', 'craft', 'souvenir']):
        return 'Pasar Seni & Kerajinan'
    if any(k in combined for k in ['taman', 'park', 'garden', 'waterpark', 'waterblow', 'swing', 'outbound', ' ridge ', 'rekreasi']):
        return 'Taman & Tempat Rekreasi'
    if any(k in combined for k in ['kuliner', 'resto', 'restaurant', 'cafe', 'warung', 'seafood']):
        return 'Wisata Kuliner'
        
    orig = str(row.get('preferensi', '')).strip()
    if 'Alam' in orig: return 'Wisata Ekosistem Alam'
    if 'Budaya' in orig: return 'Warisan Seni & Budaya'
    if 'Rekreasi' in orig: return 'Wahana & Pesisir Rekreasi'
    return 'Kawasan Pariwisata Terpadu'

try:
    import os
    import urllib.parse
    script_dir = os.path.dirname(os.path.abspath(__file__))
    
    # Primary dataset CSV source
    csv_path = os.path.join(script_dir, 'dataset terbaru.csv')
    print(f"Using CSV dataset source: {os.path.basename(csv_path)}")
    json_path = os.path.join(script_dir, 'assets', 'wisata.json')
    
    df = pd.read_csv(csv_path)
    
    df['latitude'] = df['latitude'].apply(clean_coordinate)
    df['longitude'] = df['longitude'].apply(clean_coordinate)
    
    # Identify location column
    loc_col = 'lokasi'
    if 'kabupaten_kota' in df.columns:
        loc_col = 'kabupaten_kota'
    elif 'kabupaten' in df.columns:
        loc_col = 'kabupaten'
        
    if 'deskripsi' not in df.columns:
        df['deskripsi'] = ""
        
    if 'link_foto_2' not in df.columns:
        df['link_foto_2'] = ""
        
    modified = False
    for idx, row in df.iterrows():
        if pd.isna(row['deskripsi']) or str(row['deskripsi']).strip() == "":
            desc = generate_description(row['nama'], row['preferensi'] if 'preferensi' in df.columns else row['kategori'], row[loc_col])
            df.at[idx, 'deskripsi'] = desc
            modified = True
            
    # Update kategori to be specific
    df['kategori'] = df.apply(classify_specific_kategori, axis=1)
    try:
        df.to_csv(csv_path, index=False)
        print("Saved updated specific categories back to CSV.")
    except Exception as csv_err:
        print(f"Note: CSV file open/locked by editor, JSON will still be generated accurately: {csv_err}")
    
    json_data = []
    for _, row in df.iterrows():
        # Fill NaN values for photo links with empty string
        lf1 = str(row['link_foto']) if not pd.isna(row['link_foto']) else ''
        lf2 = str(row['link_foto_2']) if not pd.isna(row['link_foto_2']) else ''
        
        csv_link = str(row['link']) if 'link' in df.columns and not pd.isna(row['link']) else (str(row['gmaps']) if 'gmaps' in df.columns and not pd.isna(row['gmaps']) else '')
        if csv_link.startswith('https://www.google.com/maps'):
            gmaps_url = csv_link
        else:
            q_str = f"{row['nama']} {row[loc_col]} Bali"
            gmaps_url = f"https://www.google.com/maps/search/?api=1&query={urllib.parse.quote_plus(q_str)}"
            
        kat_val = str(row['kategori'])
        item = {
            "Nama Wisata": str(row['nama']),
            "Kategori Wisata": kat_val,
            "Lokasi": str(row[loc_col]),
            "rating": float(row['rating']) if not pd.isna(row['rating']) else 4.0,
            "Deskripsi": str(row['deskripsi']) if not pd.isna(row['deskripsi']) else '',
            "latitude": float(row['latitude']),
            "longitude": float(row['longitude']),
            "link_foto": lf1,
            "link_foto_2": lf2,
            "Google Maps": gmaps_url,
            "preferensi": str(row['preferensi']) if 'preferensi' in df.columns and not pd.isna(row['preferensi']) else ''
        }
        json_data.append(item)
        
    with open(json_path, 'w', encoding='utf-8') as f:
        json.dump(json_data, f, ensure_ascii=False, indent=2)
        
    print(f"Successfully converted and saved {len(json_data)} records to wisata.json")
except Exception as e:
    print(f"Error: {e}")

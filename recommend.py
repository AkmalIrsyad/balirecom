import sys
import os
import json
import argparse
import numpy as np

# Force UTF-8 output encoding for Windows terminal & PHP exec compatibility
if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8')

# Import scikit-learn features
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity

def get_stop_words():
    return [
        'dan', 'di', 'ke', 'dari', 'yang', 'adalah', 'merupakan', 'untuk', 'wisata', 'tempat',
        'dengan', 'ia', 'ini', 'itu', 'atau', 'pada', 'juga', 'oleh', 'serta', 'dapat', 'dalam',
        'bagi', 'sangat', 'cocok', 'salah', 'satu', 'ada', 'akan', 'sebagai', 'menawarkan', 'menikmati'
    ]

def load_places(json_path):
    with open(json_path, 'r', encoding='utf-8') as f:
        return json.load(f)

def main():
    parser = argparse.ArgumentParser(description="TF-IDF & Cosine Similarity Recommendation Engine")
    parser.add_argument('--action', required=True, choices=['recommend', 'similar'], help='Action to perform')
    parser.add_argument('--query', help='Query string for recommendation')
    parser.add_argument('--similar_to', help='Source place name for similar recommendation')
    parser.add_argument('--json_path', default=r'assets/wisata.json', help='Path to wisata.json')
    
    args = parser.parse_args()
    
    script_dir = os.path.dirname(os.path.abspath(__file__))
    json_path = os.path.join(script_dir, args.json_path)
    
    if not os.path.exists(json_path):
        print(json.dumps({"error": f"JSON file not found: {json_path}"}))
        sys.exit(1)
        
    places = load_places(json_path)
    
    # Build text corpus
    corpus = []
    for place in places:
        pref = place.get('preferensi', '')
        text = f"{place.get('Nama Wisata', '')} {place.get('Kategori Wisata', '')} {pref} {place.get('Lokasi', '')} {place.get('Deskripsi', '')}"
        corpus.append(text)
        
    # Initialize and fit TF-IDF vectorizer
    vectorizer = TfidfVectorizer(stop_words=get_stop_words())
    tfidf_matrix = vectorizer.fit_transform(corpus)
    
    if args.action == 'recommend':
        query = args.query if args.query else ""
        if not query.strip():
            # If empty query ("Semua"), return all places sorted by highest rating
            for p in places:
                p['matchScore'] = 0.0
            places.sort(key=lambda p: float(p.get('rating', 0)), reverse=True)
            print(json.dumps(places, ensure_ascii=False))
            sys.exit(0)
            
        # Vectorize query
        query_vector = vectorizer.transform([query])
        
        # Calculate cosine similarity
        similarities = cosine_similarity(query_vector, tfidf_matrix).flatten()
        
        # Update matchScore
        for idx, score in enumerate(similarities):
            places[idx]['matchScore'] = float(score * 100)
            
        # Sort all places in corpus by matchScore descending, tie-break by rating descending
        places.sort(key=lambda p: (p.get('matchScore', 0), float(p.get('rating', 0))), reverse=True)
        
        # Return all places with scores
        print(json.dumps(places, ensure_ascii=False))
        
    elif args.action == 'similar':
        source_name = args.similar_to
        source_idx = None
        for idx, p in enumerate(places):
            if p.get('Nama Wisata') == source_name:
                source_idx = idx
                break
                
        if source_idx is None:
            print(json.dumps([]))
            sys.exit(0)
            
        # Calculate cosine similarity between the source place and all others
        source_vector = tfidf_matrix[source_idx]
        similarities = cosine_similarity(source_vector, tfidf_matrix).flatten()
        
        # Get similarities with indices
        sim_scores = []
        for idx, score in enumerate(similarities):
            if idx == source_idx:
                continue
            sim_scores.append((idx, score))
            
        # Sort by similarity descending
        sim_scores.sort(key=lambda x: x[1], reverse=True)
        
        # Take top 3
        top_3 = sim_scores[:3]
        
        results = []
        for idx, score in top_3:
            p = places[idx]
            p['matchScore'] = float(score * 100)
            p['similarity'] = float(score * 100)
            results.append(p)
            
        print(json.dumps(results, ensure_ascii=False))

if __name__ == '__main__':
    main()

<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Language switching logic
$lang_choice = isset($_GET['lang']) ? $_GET['lang'] : (isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'id');

if ($lang_choice !== 'id' && $lang_choice !== 'en') {
    $lang_choice = 'id';
}

// Save choice in cookie for 30 days
if (!isset($_COOKIE['lang']) || $_COOKIE['lang'] !== $lang_choice || isset($_GET['lang'])) {
    setcookie('lang', $lang_choice, time() + (86400 * 30), "/");
    $_COOKIE['lang'] = $lang_choice;
}

$current_lang = $lang_choice;

// 2. Translation dictionary
$translations = [
    'id' => [
        // Navbar
        'nav_home' => 'Beranda',
        'nav_assistant' => 'Asisten Rekomendasi',
        'nav_destinations' => 'Destinasi Wisata',
        'nav_map' => 'Peta Lokasi',
        'nav_about' => 'Tentang Kami',

        // Hero Section
        'hero_title_1' => 'Temukan Pesona Indah',
        'hero_title_span' => 'Bali',
        'hero_desc' => 'Rencanakan liburan Anda dengan jelajahi surga alam tersembunyi, peninggalan budaya magis, dan pantai eksotis di Pulau Dewata.',
        'search_placeholder' => 'Cari destinasi wisata (e.g., Pantai Kuta, Pura)...',
        'search_btn' => 'Cari',
        'popular_search' => 'Pencarian Populer:',

        // Featured Section
        'featured_title' => 'Destinasi Pilihan di Bali',
        'featured_subtitle' => 'Jelajahi tempat-tempat ikonik yang wajib dikunjungi di Pulau Dewata',

        // Destinations Section
        'all_destinations_title' => 'Daftar Tempat Wisata Terbaik',
        'all_destinations_subtitle' => 'Jelajahi dan saring ratusan destinasi wisata indah di seluruh Bali',
        'search_results_for' => 'Hasil pencarian untuk',
        'similar_to_label' => 'Rekomendasi serupa dengan',
        'no_results' => 'Tidak ada tempat wisata yang cocok dengan kriteria pencarian Anda.',
        'sort_label' => 'Urutkan:',
        'sort_rating' => 'Rating Tertinggi',
        'sort_name' => 'Nama (A-Z)',
        'sort_relevance' => 'Tingkat Kecocokan',
        'category_all' => 'Semua Kategori',
        'btn_detail' => 'Lihat Detail',

        // Map Section
        'map_title' => 'Peta Interaktif Destinasi Wisata Bali',
        'map_subtitle' => 'Lihat lokasi secara geografis dan temukan tempat terdekat dari rencana perjalanan Anda',

        // Footer Section
        'footer_desc' => 'Sistem rekomendasi wisata berbasis kecerdasan buatan yang membantu wisatawan menemukan destinasi terbaik di Bali sesuai preferensi perjalanan mereka.',
        'footer_contact' => 'Hubungi Kami',
        'footer_bottom' => '&copy; 2026 BaliRecom. Tugas Skripsi: Sistem Rekomendasi Tempat Wisata di Bali.',

        // Details Modal
        'modal_kabupaten' => 'Kabupaten',
        'modal_tips' => 'Tips Berkunjung',
        'modal_map_highlight' => 'Sorot di Peta',
        'modal_similar_btn' => 'Rekomendasi Serupa',
        'modal_gmaps_btn' => 'Buka di Google Maps',
        'modal_explanation_title' => 'Analisis Kecocokan',

        // Wizard (rekomendasi.php)
        'wizard_title' => 'Kuesioner Asisten Rekomendasi',
        'wizard_subtitle' => 'Bantu asisten kami menganalisis preferensi Anda untuk menemukan destinasi yang paling cocok di Bali.',
        'step_1_title' => 'Pilih Kategori Utama Wisata',
        'step_1_desc' => 'Aktivitas atau keindahan apa yang paling Anda minati?',
        'step_2_title' => 'Pilih Suasana yang Diinginkan',
        'step_2_desc' => 'Bagaimana kondisi lingkungan wisata yang Anda cari?',
        'step_3_title' => 'Teman Perjalanan Anda',
        'step_3_desc' => 'Dengan siapa Anda akan mengunjungi tempat wisata ini?',
        'step_location_title' => 'Pilih Wilayah / Kabupaten Bali',
        'step_location_desc' => 'Di kabupaten mana Anda ingin mencari destinasi wisata?',
        'step_4_title' => 'Faktor Prioritas Hasil',
        'step_4_desc' => 'Faktor penentu mana yang ingin Anda utamakan untuk hasil rekomendasi?',

        // Wizard Steps Labels
        'step_label_type' => 'Tipe',
        'step_label_atmosphere' => 'Suasana',
        'step_label_companion' => 'Teman',
        'step_label_location' => 'Wilayah',
        'step_label_priority' => 'Prioritas',

        // Wizard Step 1 Options
        'opt_all_cat_title' => 'Semua Preferensi',
        'opt_all_cat_desc' => 'Menjelajahi semua jenis preferensi secara umum',
        'opt_pantai_title' => 'Wisata Rekreasi',
        'opt_pantai_desc' => 'Destinasi rekreasi pantai, taman rekreasi air, dan aktivitas hiburan',
        'opt_alam_title' => 'Wisata Alam',
        'opt_alam_desc' => 'Air terjun, gunung, hutan lindung, danau, dan keindahan alam Bali',
        'opt_budaya_title' => 'Wisata Budaya',
        'opt_budaya_desc' => 'Desa adat, pura bersejarah, istana raja, kesenian, dan tradisi lokal',
        'opt_pura_title' => 'Wisata Buatan',
        'opt_pura_desc' => 'Taman bermain, agrowisata, kebun binatang, dan rekreasi buatan modern',
        'opt_buatan_title' => 'Wisata Umum',
        'opt_buatan_desc' => 'Monumen bersejarah, pusat wisata terpadu, dan fasilitas umum Bali',

        // Wizard Step 2 Options
        'opt_tenang_title' => 'Sunyi & Damai',
        'opt_tenang_desc' => 'Suasana tenang, sepi dari kerumunan, asri, cocok untuk healing',
        'opt_ramai_title' => 'Ramai & Aktif',
        'opt_ramai_desc' => 'Pusat keramaian, banyak turis, aktivitas seru, cafe, dan hiburan',

        // Wizard Step 3 Options
        'opt_solo_title' => 'Solo Traveler',
        'opt_solo_desc' => 'Bepergian sendiri, mencari kedamaian atau petualangan pribadi',
        'opt_pasangan_title' => 'Bersama Pasangan',
        'opt_pasangan_desc' => 'Liburan romantis berdua, bulan madu, atau berburu pemandangan indah',
        'opt_keluarga_title' => 'Keluarga / Rombongan',
        'opt_keluarga_desc' => 'Aksesibilitas mudah, aman untuk anak-anak/lansia, area bermain luas',

        // Wizard Step 4 Options
        'opt_pri_rating_title' => 'Rating Bintang',
        'opt_pri_rating_desc' => 'Utamakan tempat dengan rating tertinggi yang disukai banyak orang',
        'opt_pri_match_title' => 'Tingkat Kecocokan',
        'opt_pri_match_desc' => 'Urutkan murni berdasarkan seberapa cocok preferensi kuesioner Anda',

        // Wizard Buttons
        'btn_back' => 'Kembali',
        'btn_next' => 'Lanjut',
        'btn_submit' => 'Tampilkan Rekomendasi',
        'btn_repeat' => 'Ulangi Kuesioner',

        // Wizard Results
        'results_title' => 'Profil Preferensi Liburan Anda:',
        'results_cat' => 'Kategori',
        'results_atm' => 'Suasana',
        'results_comp' => 'Teman',
        'results_loc' => 'Wilayah',
        'results_header' => 'Hasil Rekomendasi Teratas',
        'results_desc' => 'Daftar tempat wisata terbaik yang paling cocok dengan preferensi kuesioner Anda.',
    ],
    'en' => [
        // Navbar
        'nav_home' => 'Home',
        'nav_assistant' => 'Recommendation Assistant',
        'nav_destinations' => 'Destinations',
        'nav_map' => 'Map',
        'nav_about' => 'About Bali',

        // Hero Section
        'hero_title_1' => 'Discover the Beautiful Charm of',
        'hero_title_span' => 'Bali',
        'hero_desc' => 'Plan your dream vacation with a smart assistant tailored to your travel style. Explore hidden natural paradises, magical cultural heritage, and exotic beaches on the Island of the Gods.',
        'search_placeholder' => 'Search tourist destinations (e.g., Kuta Beach, Temple)...',
        'search_btn' => 'Search',
        'popular_search' => 'Popular Search:',

        // Featured Section
        'featured_title' => 'Featured Destinations in Bali',
        'featured_subtitle' => 'Explore the iconic must-visit places on the Island of the Gods',

        // Destinations Section
        'all_destinations_title' => 'List of the Best Tourist Attractions',
        'all_destinations_subtitle' => 'Explore and filter hundreds of beautiful tourist destinations across Bali',
        'search_results_for' => 'Search results for',
        'similar_to_label' => 'Recommendations similar to',
        'no_results' => 'No tourist attractions match your search criteria.',
        'sort_label' => 'Sort by:',
        'sort_rating' => 'Highest Rating',
        'sort_name' => 'Name (A-Z)',
        'sort_relevance' => 'Relevance Level',
        'category_all' => 'All Categories',
        'btn_detail' => 'View Details',

        // Map Section
        'map_title' => 'Interactive Map of Bali Tourist Destinations',
        'map_subtitle' => 'View locations geographically and find the nearest spots for your itinerary',

        // Footer Section
        'footer_desc' => 'An AI-powered travel recommendation system that helps tourists discover the best destinations in Bali based on their personal travel preferences.',
        'footer_contact' => 'Contact Us',
        'footer_bottom' => '&copy; 2026 BaliRecom. Thesis Project: Recommendation System for Tourist Attractions in Bali.',

        // Details Modal
        'modal_kabupaten' => 'Regency',
        'modal_tips' => 'Travel Tips',
        'modal_map_highlight' => 'Highlight on Map',
        'modal_similar_btn' => 'Similar Recommendations',
        'modal_gmaps_btn' => 'Open in Google Maps',
        'modal_explanation_title' => 'Match Analysis',

        // Wizard (rekomendasi.php)
        'wizard_title' => 'Recommendation Assistant Questionnaire',
        'wizard_subtitle' => 'Help our assistant analyze your preferences to find the most suitable destinations in Bali.',
        'step_1_title' => 'Select Main Tourism Category',
        'step_1_desc' => 'What activity or beauty interests you the most?',
        'step_2_title' => 'Select Desired Atmosphere',
        'step_2_desc' => 'What kind of environmental conditions are you looking for?',
        'step_3_title' => 'Your Travel Companion',
        'step_3_desc' => 'Who will you be visiting these attractions with?',
        'step_location_title' => 'Select Region / Regency in Bali',
        'step_location_desc' => 'In which regency do you want to find tourist attractions?',
        'step_4_title' => 'Result Priority Factor',
        'step_4_desc' => 'Which determining factor do you want to prioritize for recommendation results?',

        // Wizard Steps Labels
        'step_label_type' => 'Type',
        'step_label_atmosphere' => 'Atmosphere',
        'step_label_companion' => 'Companion',
        'step_label_location' => 'Location',
        'step_label_priority' => 'Priority',

        // Wizard Step 1 Options
        'opt_all_cat_title' => 'All Preferences',
        'opt_all_cat_desc' => 'Explore all types of preferences generally',
        'opt_pantai_title' => 'Recreational Tourism',
        'opt_pantai_desc' => 'Coastal beaches, water parks, and recreational entertainment',
        'opt_alam_title' => 'Nature Tourism',
        'opt_alam_desc' => 'Waterfalls, green mountains, conservation forests, lakes, and nature beauty',
        'opt_budaya_title' => 'Cultural Tourism',
        'opt_budaya_desc' => 'Traditional villages, historic temples, royal palaces, arts, and local customs',
        'opt_pura_title' => 'Man-made Tourism',
        'opt_pura_desc' => 'Amusement parks, agrotourism, zoos, and modern man-made creations',
        'opt_buatan_title' => 'General Tourism',
        'opt_buatan_desc' => 'Historic monuments, integrated tourism areas, and general tourism landmarks',

        // Wizard Step 2 Options
        'opt_tenang_title' => 'Quiet & Peaceful',
        'opt_tenang_desc' => 'Quiet atmosphere, away from crowds, serene, perfect for healing',
        'opt_ramai_title' => 'Lively & Active',
        'opt_ramai_desc' => 'Crowd center, lots of tourists, fun activities, cafes, and entertainment',

        // Wizard Step 3 Options
        'opt_solo_title' => 'Solo Traveler',
        'opt_solo_desc' => 'Traveling alone, looking for peace or personal adventure',
        'opt_pasangan_title' => 'With Partner',
        'opt_pasangan_desc' => 'Romantic vacation for two, honeymoon, or sunset hunting',
        'opt_keluarga_title' => 'Family / Group',
        'opt_keluarga_desc' => 'Easy accessibility, safe for children/elderly, spacious play areas',

        // Wizard Step 4 Options
        'opt_pri_rating_title' => 'Star Rating',
        'opt_pri_rating_desc' => 'Prioritize places with the highest rating liked by many',
        'opt_pri_match_title' => 'Relevance Level',
        'opt_pri_match_desc' => 'Sort purely based on how well it fits your questionnaire preferences',

        // Wizard Buttons
        'btn_back' => 'Back',
        'btn_next' => 'Next',
        'btn_submit' => 'Show Recommendations',
        'btn_repeat' => 'Repeat Questionnaire',

        // Wizard Results
        'results_title' => 'Your Vacation Preference Profile:',
        'results_cat' => 'Category',
        'results_atm' => 'Atmosphere',
        'results_comp' => 'Companion',
        'results_loc' => 'Location',
        'results_header' => 'Top Recommended Results',
        'results_desc' => 'List of the best tourist attractions that best match your questionnaire preferences.',
    ]
];

// Helper translation function
if (!function_exists('__')) {
    function __($key)
    {
        global $translations, $current_lang;
        return isset($translations[$current_lang][$key]) ? $translations[$current_lang][$key] : $key;
    }
}

if (!function_exists('getCategoryTranslated')) {
    function getCategoryTranslated($category, $lang = 'id')
    {
        if ($lang !== 'en')
            return $category;
        $catMap = [
            'Pantai & Pesisir' => 'Beach & Coast',
            'Pura & Tempat Religi' => 'Temples & Sacred Sites',
            'Air Terjun & Sumber Air' => 'Waterfalls & Springs',
            'Hutan & Suaka Alam' => 'Forests & Nature Reserves',
            'Gunung & Perbukitan' => 'Mountains & Hills',
            'Danau & Waduk' => 'Lakes & Reservoirs',
            'Desa Wisata & Budaya' => 'Cultural & Heritage Villages',
            'Agrowisata & Perkebunan' => 'Agrotourism & Plantations',
            'Taman Rekreasi & Hiburan' => 'Theme Parks & Recreation',
            'Seni & Kerajinan' => 'Arts & Crafts Centers',
            'Sejarah & Peninggalan' => 'Historical Landmarks',
            'Kuliner Tradisional' => 'Traditional Culinary',
            'Belanja & Pasar Seni' => 'Shopping & Art Markets',
            'Pemandian & Spa' => 'Hot Springs & Wellness Spa',
            'Wisata Alam' => 'Nature Tourism',
            'Wisata Budaya' => 'Cultural Tourism',
            'Wisata Buatan' => 'Man-made Tourism',
            'Wisata Rekreasi' => 'Recreation Tourism',
            'Wisata Umum' => 'General Tourism',
            'Lainnya' => 'Other Attractions',
        ];
        return $catMap[$category] ?? $category;
    }
}

if (!function_exists('getLocationTranslated')) {
    function getLocationTranslated($location, $lang = 'id')
    {
        if ($lang !== 'en')
            return $location;
        $locMap = [
            'Kabupaten Badung' => 'Badung Regency',
            'Kabupaten Gianyar' => 'Gianyar Regency',
            'Kabupaten Tabanan' => 'Tabanan Regency',
            'Kabupaten Buleleng' => 'Buleleng Regency',
            'Kabupaten Bangli' => 'Bangli Regency',
            'Kabupaten Karangasem' => 'Karangasem Regency',
            'Kabupaten Klungkung' => 'Klungkung Regency',
            'Kabupaten Jembrana' => 'Jembrana Regency',
            'Kota Denpasar' => 'Denpasar City',
        ];
        return $locMap[$location] ?? $location;
    }
}

if (!function_exists('getTranslatedDescription')) {
    function getTranslatedDescription($desc, $name = '', $category = '', $location = '', $lang = 'id')
    {
        if ($lang !== 'en') {
            return !empty($desc) ? $desc : 'Nikmati pesona eksotisme keindahan pulau Bali di lokasi ini. Sangat direkomendasikan untuk healing dan mengisi waktu libur akhir pekan Anda.';
        }

        if (empty($desc)) {
            $locClean = str_replace(['Kabupaten ', 'Kota '], '', $location);
            return "Experience the exotic beauty and serene charm of Bali at {$name}" . (!empty($locClean) ? " in {$locClean}" : "") . ". Highly recommended for relaxation, cultural exploration, and memorable holiday adventures.";
        }

        $phraseMap = [
            'merupakan salah satu destinasi wisata religi dan cagar budaya yang sangat disucikan di kawasan' => 'is a revered religious and cultural heritage destination located in',
            'merupakan destinasi wisata religi dan cagar budaya yang sangat disucikan di kawasan' => 'is a sacred religious destination and cultural heritage site in',
            'merupakan kawasan hutan konservasi dan suaka alam yang rimbun dan asri di' => 'is a lush nature conservation forest and wildlife sanctuary located in',
            'merupakan salah satu destinasi wisata pantai paling terkenal di Bali dengan' => 'is one of the most famous beach destinations in Bali featuring',
            'merupakan salah satu destinasi pantai pesisir eksotis dengan hamparan pasir putih' => 'is an exotic coastal beach destination boasting scenic white sands',
            'menghadirkan pesona air terjun alami yang jernih dan segar di tengah lembah hijau nan asri di kawasan' => 'presents a pristine, refreshing natural waterfall set amidst lush green valleys in',
            'merupakan destinasi agrowisata dan perkebunan hijau yang asri di kawasan' => 'is a scenic agrotourism and lush green plantation destination in',
            'merupakan pusat seni, cagar budaya, dan peninggalan bersejarah yang agung di kawasan' => 'is a prominent center of art, cultural heritage, and magnificent history in',
            'adalah wahana rekreasi terpadu modern yang menawarkan berbagai wahana seru dan hiburan menyenangkan di kawasan' => 'is a modern integrated amusement attraction offering exciting rides and entertainment in',
            'Destinasi ini menghadirkan keindahan arsitektur tradisional Bali yang megah, ukiran batu yang artistik, serta suasana spiritual yang tenang di tengah keasrian alam pulau Dewata.' => 'This destination showcases majestic traditional Balinese architecture, artistic stone carvings, and a tranquil spiritual atmosphere embraced by the scenic nature of the Island of the Gods.',
            'Wisatawan dapat menikmati suasana hutan yang teduh, keanekaragaman flora khas lokal, serta interaksi harmonis dengan alam pulau Dewata.' => 'Visitors can enjoy the shaded forest atmosphere, rich local flora diversity, and harmonious interaction with Bali\'s pristine nature.',
            'Wisatawan dapat menikmati pemandangan matahari terbenam yang memukau, deburan ombak yang menenangkan, serta keindahan pesisir tropis pulau Bali.' => 'Visitors can take in breathtaking sunset vistas, soothing ocean waves, and the exotic tropical coastal charm of Bali.',
            'Wisatawan dapat berenang di kolam alami, menikmati udara sejuk pegunungan, serta mengabadikan panorama alam yang memanjakan mata.' => 'Visitors can swim in natural pools, breathe fresh mountain air, and capture stunning visual panoramas.',
            'Sangat direkomendasikan untuk healing dan mengisi waktu libur akhir pekan Anda bersama keluarga maupun kerabat tercinta.' => 'Highly recommended for wellness, healing, and spending memorable weekend getaways with your family and loved ones.',
            'Kabupaten Badung, Bali' => 'Badung Regency, Bali',
            'Kabupaten Gianyar, Bali' => 'Gianyar Regency, Bali',
            'Kabupaten Tabanan, Bali' => 'Tabanan Regency, Bali',
            'Kabupaten Buleleng, Bali' => 'Buleleng Regency, Bali',
            'Kabupaten Bangli, Bali' => 'Bangli Regency, Bali',
            'Kabupaten Karangasem, Bali' => 'Karangasem Regency, Bali',
            'Kabupaten Klungkung, Bali' => 'Klungkung Regency, Bali',
            'Kabupaten Jembrana, Bali' => 'Jembrana Regency, Bali',
            'Kota Denpasar, Bali' => 'Denpasar City, Bali',
            'Kabupaten Badung' => 'Badung Regency',
            'Kabupaten Gianyar' => 'Gianyar Regency',
            'Kabupaten Tabanan' => 'Tabanan Regency',
            'Kabupaten Buleleng' => 'Buleleng Regency',
            'Kabupaten Bangli' => 'Bangli Regency',
            'Kabupaten Karangasem' => 'Karangasem Regency',
            'Kabupaten Klungkung' => 'Klungkung Regency',
            'Kabupaten Jembrana' => 'Jembrana Regency',
            'Kota Denpasar' => 'Denpasar City',
            'pulau Dewata' => 'Island of the Gods',
            'Pulau Dewata' => 'Island of the Gods',
            'pulau Bali' => 'Bali island',
            'Pulau Bali' => 'Bali island',
            'di kawasan' => 'in the area of',
            'merupakan' => 'is',
            'adalah' => 'is',
            'salah satu' => 'one of the',
            'destinasi wisata' => 'tourist destination',
            'tempat wisata' => 'tourist attraction',
            'objek wisata' => 'tourist attraction',
            'cagar budaya' => 'cultural heritage',
            'wisata religi' => 'spiritual and religious site',
            'sangat disucikan' => 'deeply revered',
            'suasana yang tenang' => 'peaceful ambiance',
            'keasrian alam' => 'natural serenity',
            'keindahan alam' => 'natural beauty',
            'pemandangan alam' => 'scenic natural views',
            'matahari terbenam' => 'sunset',
            'matahari terbit' => 'sunrise',
            'pasir putih' => 'white sand',
            'air terjun' => 'waterfall',
            'danau' => 'lake',
            'gunung' => 'mountain',
            'pantai' => 'beach',
            'hutan' => 'forest',
            'pura' => 'temple',
            'desa adat' => 'traditional village',
            'arsitektur tradisional' => 'traditional architecture',
            'ukiran batu' => 'stone carvings',
            'menikmati' => 'enjoying',
            'Wisatawan dapat' => 'Visitors can',
            'Pengunjung dapat' => 'Visitors can',
            'Sangat direkomendasikan' => 'Highly recommended',
            'libur akhir pekan' => 'weekend holiday',
            'liburan' => 'vacation',
        ];

        return str_ireplace(array_keys($phraseMap), array_values($phraseMap), $desc);
    }
}

if (!function_exists('calculateHaversineDistance')) {
    function calculateHaversineDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo)
    {
        $earthRadius = 6371; // Earth radius in kilometers

        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        return $angle * $earthRadius;
    }
}

if (!function_exists('getPythonBinary')) {
    function getPythonBinary()
    {
        static $cachedBin = null;
        if ($cachedBin !== null)
            return $cachedBin;

        $candidates = [
            // 1. Path Python di Virtual Environment VPS (Prioritas Utama)
            '/var/www/balirecom/env/bin/python3',

            // 2. Candidate sistem Linux/VPS standar
            '/usr/bin/python3',
            '/usr/local/bin/python3',

            // 3. Candidate Windows / Laragon (untuk kebutuhan Local Development)
            'C:\\laragon\\bin\\python\\python-3.10\\python.exe',
            'C:\\Python312\\python.exe',
            'C:\\Python311\\python.exe',
            'C:\\Python310\\python.exe',
            'C:\\Python39\\python.exe',

            // 4. Fallback global CLI command
            'python3',
            'python'
        ];

        foreach ($candidates as $c) {
            if ($c === 'python' || $c === 'python3') {
                $out = [];
                $ret = 1;
                @exec($c . ' --version 2>&1', $out, $ret);
                if ($ret === 0) {
                    $cachedBin = $c;
                    return $cachedBin;
                }
            } elseif (file_exists($c)) {
                $cachedBin = $c;
                return $cachedBin;
            }
        }
        $cachedBin = 'python';
        return $cachedBin;
    }
}

if (!function_exists('getRecommendationsFromPython')) {
    function getRecommendationsFromPython($action, $params)
    {
        $scriptPath = __DIR__ . '/recommend.py';
        if (!file_exists($scriptPath)) {
            return null;
        }

        $pythonBin = getPythonBinary();
        $cmd = escapeshellcmd($pythonBin) . ' -X utf8 ' . escapeshellarg($scriptPath) . ' --action ' . escapeshellarg($action);
        if ($action === 'recommend') {
            $cmd .= ' --query ' . escapeshellarg($params['query'] ?? '');
        } else if ($action === 'similar') {
            $cmd .= ' --similar_to ' . escapeshellarg($params['similar_to'] ?? '');
        }

        $output = [];
        $retval = 1;
        @exec($cmd, $output, $retval);

        if ($retval === 0 && !empty($output)) {
            $jsonResult = implode("\n", $output);
            $decoded = json_decode($jsonResult, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }
        return null;
    }
}


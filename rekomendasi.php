<?php
require_once 'lang.php';
// Load dataset wisata.json
$wisataJson = @file_get_contents('assets/wisata.json');
$wisataData = json_decode($wisataJson, true);

// Dapatkan daftar lokasi kabupaten secara dinamis dari dataset
$locations = [];
if (is_array($wisataData)) {
    foreach ($wisataData as $item) {
        if (!empty($item['Lokasi'])) {
            $locations[] = trim($item['Lokasi']);
        }
    }
    $locations = array_unique($locations);
    sort($locations);
} else {
    $wisataData = [];
}

// Ajax request for similar recommendations

// Ajax request for similar recommendations
if (isset($_GET['ajax']) && $_GET['ajax'] == 1) {
    header('Content-Type: application/json; charset=utf-8');
    $ajaxAction = $_GET['action'] ?? '';
    if ($ajaxAction === 'similar') {
        $similar_to = $_GET['similar_to'] ?? '';
        $recommendations = getRecommendationsFromPython('similar', ['similar_to' => $similar_to]) ?? [];
        echo json_encode($recommendations, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// ----------------------------------------------------
// UTILITY FUNCTIONS IN PHP
// ----------------------------------------------------

function getCategorySlug($categoryName)
{
    if (!$categoryName) return 'lainnya';
    $lower = strtolower($categoryName);
    if (strpos($lower, 'pantai') !== false || strpos($lower, 'laut') !== false || strpos($lower, 'bahari') !== false) return 'pantai';
    if (strpos($lower, 'alam') !== false || strpos($lower, 'gunung') !== false || strpos($lower, 'air terjun') !== false || strpos($lower, 'danau') !== false || strpos($lower, 'hutan') !== false) return 'alam';
    if (strpos($lower, 'budaya') !== false || strpos($lower, 'desa') !== false || strpos($lower, 'seni') !== false || strpos($lower, 'sejarah') !== false || strpos($lower, 'museum') !== false) return 'budaya';
    if (strpos($lower, 'pura') !== false || strpos($lower, 'religi') !== false || strpos($lower, 'candi') !== false) return 'pura';
    if (strpos($lower, 'buatan') !== false || strpos($lower, 'taman') !== false || strpos($lower, 'rekreasi') !== false || strpos($lower, 'kebun') !== false) return 'buatan';
    return 'lainnya';
}

function getCategoryImage($category)
{
    $slug = getCategorySlug($category);
    if ($slug === 'pantai') return 'assets/images/bali_beach.png';
    if ($slug === 'pura') return 'assets/images/bali_temple.png';
    return 'assets/images/bali_hero_bg.png'; // default image
}

function getFilterUrl($newParams)
{
    $params = $_GET;
    foreach ($newParams as $k => $v) {
        if ($v === null || $v === '') {
            unset($params[$k]);
        } else {
            $params[$k] = $v;
        }
    }
    return '?' . http_build_query($params);
}

// ----------------------------------------------------
// PROCESS PARAMS & FILTERING DATA
// ----------------------------------------------------

$action = $_GET['action'] ?? '';
$pref_category = $_GET['pref_category'] ?? '';
$pref_subcategory = $_GET['pref_subcategory'] ?? '';
$pref_location = $_GET['pref_location'] ?? 'all';
$pref_priority = $_GET['pref_priority'] ?? '';

$sort = $_GET['sort'] ?? 'relevan';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$user_lat = isset($_GET['user_lat']) && $_GET['user_lat'] !== '' ? (float)$_GET['user_lat'] : null;
$user_lng = isset($_GET['user_lng']) && $_GET['user_lng'] !== '' ? (float)$_GET['user_lng'] : null;

$isWizardActive = ($action === 'recommend' || !empty($pref_category));
$filteredPlaces = [];
$totalFilteredCount = 0;
$displayedPlaces = [];
$totalPages = 1;

if ($isWizardActive) {
    $userPreferences = [
        'category' => $pref_category,
        'subcategory' => $pref_subcategory,
        'location' => $pref_location,
        'priority' => $pref_priority
    ];

    // ==========================================================
    // STEP 1: Combine preference category, subcategory, and location into a single query
    // Example: "Wisata Alam Air Terjun Kabupaten Buleleng"
    // ==========================================================
    $queryParts = [];
    if ($pref_category !== 'all' && !empty($pref_category)) {
        $queryParts[] = $pref_category;
    }
    if ($pref_subcategory !== 'all' && !empty($pref_subcategory)) {
        $queryParts[] = $pref_subcategory;
    }
    if ($pref_location !== 'all' && !empty($pref_location)) {
        $queryParts[] = $pref_location;
    }
    $queryText = implode(' ', $queryParts);

    // ==========================================================
    // STEP 2: Compute Content-Based Filtering (Cosine Similarity) across the ENTIRE dataset (741 places)
    // ==========================================================
    $scoredPlaces = getRecommendationsFromPython('recommend', ['query' => $queryText]) ?? $wisataData;

    // If query is empty ("Semua" selected), rank by highest rating across Bali
    if (empty(trim($queryText))) {
        usort($scoredPlaces, function ($a, $b) {
            return ($b['rating'] ?? 0) <=> ($a['rating'] ?? 0);
        });
    }

    // ==========================================================
    // STEP 3: Top-10 candidates are ALWAYS determined by CBF matchScore first
    // ==========================================================
    $top10Candidates = array_slice($scoredPlaces, 0, 10);

    // Calculate distance for Top-10 if user coordinates are provided
    if ($user_lat !== null && $user_lng !== null) {
        foreach ($top10Candidates as $idx => $place) {
            $pLat = isset($place['latitude']) ? (float)$place['latitude'] : 0;
            $pLng = isset($place['longitude']) ? (float)$place['longitude'] : 0;
            if ($pLat != 0 && $pLng != 0) {
                $top10Candidates[$idx]['distance'] = calculateHaversineDistance($user_lat, $user_lng, $pLat, $pLng);
            }
        }
    }

    // ==========================================================
    // STEP 4: Rating, name, and distance ONLY reorder items WITHIN the Top-10 candidates
    // (They do not replace or swap out the Top-10 recommended candidates)
    // ==========================================================
    if ($sort === 'jarak' && $user_lat !== null && $user_lng !== null) {
        usort($top10Candidates, function ($a, $b) {
            $aDist = isset($a['distance']) ? (float)$a['distance'] : 999999;
            $bDist = isset($b['distance']) ? (float)$b['distance'] : 999999;
            return $aDist <=> $bDist;
        });
    } else if ($sort === 'rating') {
        usort($top10Candidates, function ($a, $b) {
            return ($b['rating'] ?? 0) <=> ($a['rating'] ?? 0);
        });
    } else if ($sort === 'nama') {
        usort($top10Candidates, function ($a, $b) {
            return strcasecmp($a['Nama Wisata'] ?? '', $b['Nama Wisata'] ?? '');
        });
    } else {
        // Default: sort = 'relevan' (Tingkat Kecocokan / Match Score, dengan tie-breaker Rating)
        usort($top10Candidates, function ($a, $b) {
            $scoreDiff = ($b['matchScore'] ?? 0) <=> ($a['matchScore'] ?? 0);
            if ($scoreDiff === 0) {
                return ($b['rating'] ?? 0) <=> ($a['rating'] ?? 0);
            }
            return $scoreDiff;
        });
    }

    $filteredPlaces = $top10Candidates;
    $totalFilteredCount = count($filteredPlaces);
    $displayedPlaces = $filteredPlaces;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asisten Rekomendasi - BaliRecom</title>
    <meta name="description" content="Kuesioner Cerdas Personalisasi Rekomendasi Destinasi Wisata Bali">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar glass">
        <div class="container nav-container">
            <a href="index.php" class="logo">
                <i class="fa-solid fa-umbrella-beach"></i>
                <span>BaliRecom</span>
            </a>

            <!-- Navbar Search Form (TripAdvisor style) -->
            <form action="index.php#destinasi" method="GET" class="nav-search-form">
                <input type="hidden" name="action" value="filter">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" name="q" placeholder="<?php echo __('search_placeholder'); ?>" id="searchInput" autocomplete="off">
                <div id="searchSuggestions" class="search-suggestions-dropdown" style="display: none;"></div>
            </form>

            <div class="nav-links">
                <a href="index.php"><?php echo __('nav_home'); ?></a>
                <a href="destinasi.php"><?php echo __('nav_destinations'); ?></a>
                <a href="rekomendasi.php" class="active"><?php echo __('nav_assistant'); ?></a>
                <a href="tentang.php"><?php echo __('nav_about'); ?></a>
            </div>
            <div class="nav-actions">
                <a href="?lang=<?php echo $current_lang === 'id' ? 'en' : 'id'; ?><?php echo isset($_GET['action']) ? '&action=' . htmlspecialchars($_GET['action']) : ''; ?><?php echo isset($_GET['pref_category']) ? '&pref_category=' . htmlspecialchars($_GET['pref_category']) : ''; ?><?php echo isset($_GET['pref_atmosphere']) ? '&pref_atmosphere=' . htmlspecialchars($_GET['pref_atmosphere']) : ''; ?><?php echo isset($_GET['pref_companion']) ? '&pref_companion=' . htmlspecialchars($_GET['pref_companion']) : ''; ?><?php echo isset($_GET['pref_location']) ? '&pref_location=' . htmlspecialchars($_GET['pref_location']) : ''; ?><?php echo isset($_GET['pref_priority']) ? '&pref_priority=' . htmlspecialchars($_GET['pref_priority']) : ''; ?>" class="btn btn-outline lang-toggle" title="<?php echo $current_lang === 'id' ? 'Switch to English' : 'Ubah ke Bahasa Indonesia'; ?>">
                    <i class="fa-solid fa-globe"></i>
                    <span><?php echo $current_lang === 'id' ? 'EN' : 'ID'; ?></span>
                </a>
                <button class="btn btn-outline dark-mode-toggle" id="themeToggle" aria-label="Toggle Dark Mode">
                    <i class="fa-solid fa-moon"></i>
                </button>
            </div>
            <div class="mobile-menu-btn">
                <i class="fa-solid fa-bars"></i>
            </div>
        </div>
    </nav>

    <!-- Subpage Hero Banner -->
    <section class="hero assistant-hero" style="min-height: 380px; height: 45vh; padding-top: 130px; padding-bottom: 50px;">
        <div class="hero-bg" style="background-image: url('assets/images/bali_beach.png');"></div>
        <div class="hero-overlay"></div>
        <div class="container hero-content text-center" style="display: flex; flex-direction: column; justify-content: center; align-items: center; height: 100%;">
            <h1 style="font-size: 2.5rem; margin-bottom: 10px;"><?php echo ($current_lang === 'id') ? 'Asisten Rekomendasi' : 'Recommendation Assistant'; ?> <span>BaliRecom</span></h1>
            <p style="max-width: 700px; margin: 0 auto; opacity: 0.9;"><?php echo ($current_lang === 'id') ? 'Temukan destinasi liburan impian Anda di Bali secara personal yang disesuaikan dengan minat, lokasi, dan kriteria perjalanan Anda.' : 'Find your dream holiday destination in Bali personally tailored to your interests, location, and travel criteria.'; ?></p>
        </div>
    </section>

    <!-- Preference Questionnaire (Wizard Section) -->
    <section class="wizard-section container" id="kuesioner" style="padding-top: 40px; <?php if ($isWizardActive) echo 'display: none;'; ?>">
        <div class="section-header">
            <h2><?php echo __('wizard_title'); ?></h2>
            <p><?php echo __('wizard_subtitle'); ?></p>
        </div>

        <div class="wizard-container glass-card">
            <!-- Progress Bar -->
            <div class="wizard-progress-bar">
                <div class="progress-line-bg">
                    <div class="progress-line-fill" id="wizardProgress"></div>
                </div>
                <div class="progress-steps">
                    <div class="step-dot active" data-step="1">
                        <span class="step-num">1</span>
                        <span class="step-label"><?php echo ($current_lang === 'id') ? 'Preferensi' : 'Preference'; ?></span>
                    </div>
                    <div class="step-dot" data-step="2">
                        <span class="step-num">2</span>
                        <span class="step-label"><?php echo ($current_lang === 'id') ? 'Kategori' : 'Category'; ?></span>
                    </div>
                    <div class="step-dot" data-step="3">
                        <span class="step-num">3</span>
                        <span class="step-label"><?php echo __('step_label_location'); ?></span>
                    </div>
                </div>
            </div>

            <!-- Steps Form -->
            <form id="wizardForm" action="rekomendasi.php" method="GET">
                <input type="hidden" name="action" value="recommend">

                <!-- Step 1: Category -->
                <div class="wizard-step active" data-step="1">
                    <div class="step-info">
                        <h3><?php echo __('step_1_title'); ?></h3>
                        <p><?php echo __('step_1_desc'); ?></p>
                    </div>
                    <div class="wizard-options-grid">
                        <label class="wizard-option-card">
                            <input type="radio" name="pref_category" value="all" checked>
                            <div class="option-content">
                                <div class="option-icon"><i class="fa-solid fa-border-all"></i></div>
                                <h4><?php echo __('opt_all_cat_title'); ?></h4>
                                <p><?php echo __('opt_all_cat_desc'); ?></p>
                            </div>
                        </label>
                        <label class="wizard-option-card">
                            <input type="radio" name="pref_category" value="Wisata Rekreasi">
                            <div class="option-content">
                                <div class="option-icon"><i class="fa-solid fa-umbrella-beach"></i></div>
                                <h4><?php echo __('opt_pantai_title'); ?></h4>
                                <p><?php echo ($current_lang === 'id') ? 'Pesona pantai pasir putih, taman rekreasi air, dan hiburan seru' : 'Charming white sand beaches, water parks, and exciting recreation'; ?></p>
                            </div>
                        </label>
                        <label class="wizard-option-card">
                            <input type="radio" name="pref_category" value="Wisata Alam">
                            <div class="option-content">
                                <div class="option-icon"><i class="fa-solid fa-mountain-sun"></i></div>
                                <h4><?php echo __('opt_alam_title'); ?></h4>
                                <p><?php echo ($current_lang === 'id') ? 'Keindahan air terjun, pegunungan, danau, dan hutan hijau yang asri' : 'Beauty of waterfalls, mountains, lakes, and serene green forests'; ?></p>
                            </div>
                        </label>
                        <label class="wizard-option-card">
                            <input type="radio" name="pref_category" value="Wisata Budaya">
                            <div class="option-content">
                                <div class="option-icon"><i class="fa-solid fa-masks-theater"></i></div>
                                <h4><?php echo __('opt_budaya_title'); ?></h4>
                                <p><?php echo ($current_lang === 'id') ? 'Eksplorasi desa adat, pura suci kuno, seni tari, dan istana kerajaan' : 'Explore traditional villages, ancient sacred temples, dance arts, and royal palaces'; ?></p>
                            </div>
                        </label>
                        <label class="wizard-option-card">
                            <input type="radio" name="pref_category" value="Wisata Buatan">
                            <div class="option-content">
                                <div class="option-icon"><i class="fa-solid fa-camera"></i></div>
                                <h4><?php echo __('opt_pura_title'); ?></h4>
                                <p><?php echo ($current_lang === 'id') ? 'Taman bermain modern, kebun binatang, agrowisata, dan rekreasi buatan' : 'Modern amusement parks, zoos, agrotourism, and man-made recreation'; ?></p>
                            </div>
                        </label>
                        <label class="wizard-option-card">
                            <input type="radio" name="pref_category" value="Wisata Umum">
                            <div class="option-content">
                                <div class="option-icon"><i class="fa-solid fa-city"></i></div>
                                <h4><?php echo __('opt_buatan_title'); ?></h4>
                                <p><?php echo ($current_lang === 'id') ? 'Monumen bersejarah, pusat pariwisata terpadu, dan landmark kota' : 'Historic monuments, integrated tourism hubs, and city landmarks'; ?></p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Step 2: Kategori Wisata (Detail) -->
                <div class="wizard-step" data-step="2">
                    <div class="step-info">
                        <h3><?php echo ($current_lang === 'id') ? 'Pilih Kategori Spesifik Wisata' : 'Select Specific Tourism Category'; ?></h3>
                        <p><?php echo ($current_lang === 'id') ? 'Sesuaikan dengan jenis objek wisata yang lebih mendetail di Bali' : 'Tailor to a more detailed type of tourist attraction in Bali'; ?></p>
                    </div>
                    <div class="wizard-options-grid">
                        <!-- 'All' is always visible -->
                        <label class="wizard-option-card" data-parent-categories="all">
                            <input type="radio" name="pref_subcategory" value="all" checked>
                            <div class="option-content">
                                <div class="option-icon"><i class="fa-solid fa-border-all"></i></div>
                                <h4><?php echo ($current_lang === 'id') ? 'Semua Kategori' : 'All Categories'; ?></h4>
                                <p><?php echo ($current_lang === 'id') ? 'Menampilkan semua jenis kategori wisata tanpa filter detail' : 'Display all types of tourism categories without detail filter'; ?></p>
                            </div>
                        </label>

                        <!-- Wisata Rekreasi Subcategories -->
                        <label class="wizard-option-card" data-parent-categories="Wisata Rekreasi">
                            <input type="radio" name="pref_subcategory" value="Pantai">
                            <div class="option-content">
                                <div class="option-icon"><i class="fa-solid fa-umbrella-beach"></i></div>
                                <h4><?php echo ($current_lang === 'id') ? 'Pantai Pasir Putih' : 'White Sand Beach'; ?></h4>
                                <p><?php echo ($current_lang === 'id') ? 'Pesisir pasir putih, deburan ombak hangat, dan panorama laut' : 'White sandy coastline, warm waves, and ocean panoramas'; ?></p>
                            </div>
                        </label>
                        <label class="wizard-option-card" data-parent-categories="Wisata Rekreasi">
                            <input type="radio" name="pref_subcategory" value="Sunset">
                            <div class="option-content">
                                <div class="option-icon"><i class="fa-solid fa-sun"></i></div>
                                <h4><?php echo ($current_lang === 'id') ? 'Sunset Point & Beach Club' : 'Sunset Point & Beach Club'; ?></h4>
                                <p><?php echo ($current_lang === 'id') ? 'Spot foto pemandangan senja matahari terbenam dan tempat bersantai' : 'Sunset view photo spots and beachside relaxation hubs'; ?></p>
                            </div>
                        </label>
                        <label class="wizard-option-card" data-parent-categories="Wisata Rekreasi">
                            <input type="radio" name="pref_subcategory" value="Bahari">
                            <div class="option-content">
                                <div class="option-icon"><i class="fa-solid fa-person-swimming"></i></div>
                                <h4><?php echo ($current_lang === 'id') ? 'Snorkeling, Diving & Surfing' : 'Snorkeling, Diving & Surfing'; ?></h4>
                                <p><?php echo ($current_lang === 'id') ? 'Olahraga air, titik selam terumbu karang, surfing, dan wisata bahari' : 'Water sports, coral reef diving spots, surfing, and marine tourism'; ?></p>
                            </div>
                        </label>
                        <label class="wizard-option-card" data-parent-categories="Wisata Rekreasi">
                            <input type="radio" name="pref_subcategory" value="Wisata Kuliner">
                            <div class="option-content">
                                <div class="option-icon"><i class="fa-solid fa-utensils"></i></div>
                                <h4><?php echo ($current_lang === 'id') ? 'Kuliner & Seafood' : 'Culinary & Seafood'; ?></h4>
                                <p><?php echo ($current_lang === 'id') ? 'Makanan khas Bali, warung kuliner tradisional, dan tempat makan tepi pantai' : 'Balinese culinary specialties, traditional food stalls, and beachside restaurants'; ?></p>
                            </div>
                        </label>
                        <label class="wizard-option-card" data-parent-categories="Wisata Rekreasi">
                            <input type="radio" name="pref_subcategory" value="Wisata Rekreasi">
                            <div class="option-content">
                                <div class="option-icon"><i class="fa-solid fa-water"></i></div>
                                <h4><?php echo ($current_lang === 'id') ? 'Wahana Waterpark & Rafting' : 'Waterpark & Rafting Rides'; ?></h4>
                                <p><?php echo ($current_lang === 'id') ? 'Pusat watersport seru, rafting sungai, waterpark, dan rekreasi keluarga' : 'Exciting watersport hubs, river rafting, waterparks, and family recreation'; ?></p>
                            </div>
                        </label>

                        <!-- Wisata Alam Subcategories -->
                        <label class="wizard-option-card" data-parent-categories="Wisata Alam">
                            <input type="radio" name="pref_subcategory" value="Air Terjun">
                            <div class="option-content">
                                <div class="option-icon"><i class="fa-solid fa-droplet"></i></div>
                                <h4><?php echo ($current_lang === 'id') ? 'Air Terjun (Waterfall)' : 'Waterfall'; ?></h4>
                                <p><?php echo ($current_lang === 'id') ? 'Kesegaran aliran air pegunungan alami yang asri di pedalaman Bali' : 'Freshness of natural mountain waterfalls in inland Bali'; ?></p>
                            </div>
                        </label>
                        <label class="wizard-option-card" data-parent-categories="Wisata Alam">
                            <input type="radio" name="pref_subcategory" value="Gunung">
                            <div class="option-content">
                                <div class="option-icon"><i class="fa-solid fa-volcano"></i></div>
                                <h4><?php echo ($current_lang === 'id') ? 'Pegunungan & Gunung Vulkanik' : 'Mountains & Volcanoes'; ?></h4>
                                <p><?php echo ($current_lang === 'id') ? 'Jalur pendakian gunung, pemandangan kawah, dan udara segar pegunungan' : 'Mountain trekking trails, crater views, and fresh mountain air'; ?></p>
                            </div>
                        </label>
                        <label class="wizard-option-card" data-parent-categories="Wisata Alam">
                            <input type="radio" name="pref_subcategory" value="Bukit">
                            <div class="option-content">
                                <div class="option-icon"><i class="fa-solid fa-mountain"></i></div>
                                <h4><?php echo ($current_lang === 'id') ? 'Perbukitan & Camping' : 'Hills & Camping'; ?></h4>
                                <p><?php echo ($current_lang === 'id') ? 'Lembah hijau yang berangin, perbukitan sabana, dan tempat berkemah' : 'Breezy green valleys, savanna hills, and camping grounds'; ?></p>
                            </div>
                        </label>
                        <label class="wizard-option-card" data-parent-categories="Wisata Alam">
                            <input type="radio" name="pref_subcategory" value="Danau">
                            <div class="option-content">
                                <div class="option-icon"><i class="fa-solid fa-water"></i></div>
                                <h4><?php echo ($current_lang === 'id') ? 'Danau Pegunungan' : 'Mountain Lakes'; ?></h4>
                                <p><?php echo ($current_lang === 'id') ? 'Keindahan danau vulkanik yang sejuk dan tenang di daerah dataran tinggi' : 'Beauty of cool and serene volcanic lakes in highland areas'; ?></p>
                            </div>
                        </label>
                        <label class="wizard-option-card" data-parent-categories="Wisata Alam">
                            <input type="radio" name="pref_subcategory" value="Hutan">
                            <div class="option-content">
                                <div class="option-icon"><i class="fa-solid fa-tree"></i></div>
                                <h4><?php echo ($current_lang === 'id') ? 'Hutan Lindung & Mangrove' : 'Protected Forest & Mangrove'; ?></h4>
                                <p><?php echo ($current_lang === 'id') ? 'Hutan pala kuno yang dihuni kera, hutan mangrove pesisir, dan alam liar' : 'Ancient monkey forests, coastal mangroves, and pristine wilderness'; ?></p>
                            </div>
                        </label>
                        <label class="wizard-option-card" data-parent-categories="Wisata Alam">
                            <input type="radio" name="pref_subcategory" value="Pemandian Air Panas">
                            <div class="option-content">
                                <div class="option-icon"><i class="fa-solid fa-hot-tub"></i></div>
                                <h4><?php echo ($current_lang === 'id') ? 'Pemandian Air Panas Alami' : 'Natural Hot Springs'; ?></h4>
                                <p><?php echo ($current_lang === 'id') ? 'Kolam air hangat alami kaya mineral belerang di kaki gunung' : 'Natural warm pool rich in sulfur minerals at the foot of mountain'; ?></p>
                            </div>
                        </label>
                        <label class="wizard-option-card" data-parent-categories="Wisata Alam">
                            <input type="radio" name="pref_subcategory" value="Sawah Terasering">
                            <div class="option-content">
                                <div class="option-icon"><i class="fa-solid fa-wheat-awn"></i></div>
                                <h4><?php echo ($current_lang === 'id') ? 'Sawah Terasering & Subak' : 'Rice Terraces & Subak'; ?></h4>
                                <p><?php echo ($current_lang === 'id') ? 'Hamparan persawahan berundak khas Bali dengan sistem irigasi subak' : 'Layered Balinese terraced rice fields with traditional subak irrigation'; ?></p>
                            </div>
                        </label>

                        <!-- Wisata Budaya Subcategories -->
                        <label class="wizard-option-card" data-parent-categories="Wisata Budaya">
                            <input type="radio" name="pref_subcategory" value="Pura">
                            <div class="option-content">
                                <div class="option-icon"><i class="fa-solid fa-place-of-worship"></i></div>
                                <h4><?php echo ($current_lang === 'id') ? 'Pura Suci & Religi' : 'Sacred Temple & Religious'; ?></h4>
                                <p><?php echo ($current_lang === 'id') ? 'Kompleks pura Hindu bersejarah dengan arsitektur klasik Bali kuno' : 'Historic Hindu temple complexes with ancient classic Balinese architecture'; ?></p>
                            </div>
                        </label>
                        <label class="wizard-option-card" data-parent-categories="Wisata Budaya">
                            <input type="radio" name="pref_subcategory" value="Tari">
                            <div class="option-content">
                                <div class="option-icon"><i class="fa-solid fa-masks-theater"></i></div>
                                <h4><?php echo ($current_lang === 'id') ? 'Pertunjukan Seni & Tari' : 'Art Performances & Dance'; ?></h4>
                                <p><?php echo ($current_lang === 'id') ? 'Pertunjukan Tari Kecak, Barong, Legong, dan atraksi kebudayaan' : 'Kecak dance, Barong, Legong performances, and cultural attractions'; ?></p>
                            </div>
                        </label>
                        <label class="wizard-option-card" data-parent-categories="Wisata Budaya">
                            <input type="radio" name="pref_subcategory" value="Museum">
                            <div class="option-content">
                                <div class="option-icon"><i class="fa-solid fa-landmark"></i></div>
                                <h4><?php echo ($current_lang === 'id') ? 'Museum & Galeri Seni' : 'Museums & Art Galleries'; ?></h4>
                                <p><?php echo ($current_lang === 'id') ? 'Galeri lukisan legendaris, pameran seni rupa, dan warisan sejarah' : 'Legendary painting galleries, art exhibitions, and historical heritage'; ?></p>
                            </div>
                        </label>
                        <label class="wizard-option-card" data-parent-categories="Wisata Budaya">
                            <input type="radio" name="pref_subcategory" value="Desa Wisata">
                            <div class="option-content">
                                <div class="option-icon"><i class="fa-solid fa-house-chimney-window"></i></div>
                                <h4><?php echo ($current_lang === 'id') ? 'Desa Adat & Kerajinan' : 'Traditional & Craft Village'; ?></h4>
                                <p><?php echo ($current_lang === 'id') ? 'Kehidupan pedesaan asri Bali yang kaya adat istiadat dan pasar seni' : 'Serene rural life of Bali rich in traditional custom and art markets'; ?></p>
                            </div>
                        </label>
                        <label class="wizard-option-card" data-parent-categories="Wisata Budaya">
                            <input type="radio" name="pref_subcategory" value="Istana / Puri">
                            <div class="option-content">
                                <div class="option-icon"><i class="fa-solid fa-chess-rook"></i></div>
                                <h4><?php echo ($current_lang === 'id') ? 'Istana & Puri Kerajaan' : 'Royal Palaces'; ?></h4>
                                <p><?php echo ($current_lang === 'id') ? 'Kompleks kediaman raja-raja Bali masa lampau yang bersejarah' : 'Historical residence complexes of Balinese kings from the past'; ?></p>
                            </div>
                        </label>

                        <!-- Wisata Buatan Subcategories -->
                        <label class="wizard-option-card" data-parent-categories="Wisata Buatan">
                            <input type="radio" name="pref_subcategory" value="Agrowisata">
                            <div class="option-content">
                                <div class="option-icon"><i class="fa-solid fa-mug-hot"></i></div>
                                <h4><?php echo ($current_lang === 'id') ? 'Agrowisata & Kebun Kopi' : 'Agrotourism & Coffee Plantation'; ?></h4>
                                <p><?php echo ($current_lang === 'id') ? 'Perkebunan kopi Luwak, kebun buah-buahan, dan edukasi pertanian' : 'Luwak coffee plantations, fruit orchards, and agricultural tours'; ?></p>
                            </div>
                        </label>
                        <label class="wizard-option-card" data-parent-categories="Wisata Buatan">
                            <input type="radio" name="pref_subcategory" value="Taman">
                            <div class="option-content">
                                <div class="option-icon"><i class="fa-solid fa-leaf"></i></div>
                                <h4><?php echo ($current_lang === 'id') ? 'Taman Bunga & Kebun Raya' : 'Flower Gardens & Botanic Gardens'; ?></h4>
                                <p><?php echo ($current_lang === 'id') ? 'Taman bunga asri, kebun raya botani, dan kawasan hijau terbuka' : 'Beautiful flower gardens, botanical gardens, and open green areas'; ?></p>
                            </div>
                        </label>
                        <label class="wizard-option-card" data-parent-categories="Wisata Buatan">
                            <input type="radio" name="pref_subcategory" value="Kebun Binatang">
                            <div class="option-content">
                                <div class="option-icon"><i class="fa-solid fa-paw"></i></div>
                                <h4><?php echo ($current_lang === 'id') ? 'Taman Safari & Satwa' : 'Safari Parks & Wildlife'; ?></h4>
                                <p><?php echo ($current_lang === 'id') ? 'Konservasi satwa liar khas, kebun binatang edukatif, dan taman burung' : 'Conservation of exotic wildlife, educational zoos, and bird parks'; ?></p>
                            </div>
                        </label>
                        <label class="wizard-option-card" data-parent-categories="Wisata Buatan">
                            <input type="radio" name="pref_subcategory" value="Buatan">
                            <div class="option-content">
                                <div class="option-icon"><i class="fa-solid fa-shapes"></i></div>
                                <h4><?php echo ($current_lang === 'id') ? 'Spot Rekreasi Buatan' : 'Man-made Attractions'; ?></h4>
                                <p><?php echo ($current_lang === 'id') ? 'Spot foto kreatif, agrowisata modern, dan tempat bermain buatan' : 'Creative photo spots, modern agrotourism, and themed play areas'; ?></p>
                            </div>
                        </label>

                        <!-- Wisata Umum Subcategories -->
                        <label class="wizard-option-card" data-parent-categories="Wisata Umum">
                            <input type="radio" name="pref_subcategory" value="Wisata Umum">
                            <div class="option-content">
                                <div class="option-icon"><i class="fa-solid fa-city"></i></div>
                                <h4><?php echo ($current_lang === 'id') ? 'Pusat Wisata & Landmark' : 'Tourism Hubs & Landmarks'; ?></h4>
                                <p><?php echo ($current_lang === 'id') ? 'Kawasan pariwisata terpadu, monumen ikonik kota, dan jalanan ramai' : 'Integrated tourist areas, iconic city monuments, and popular streets'; ?></p>
                            </div>
                        </label>
                        <label class="wizard-option-card" data-parent-categories="Wisata Umum">
                            <input type="radio" name="pref_subcategory" value="Jembatan">
                            <div class="option-content">
                                <div class="option-icon"><i class="fa-solid fa-bridge"></i></div>
                                <h4><?php echo ($current_lang === 'id') ? 'Jembatan Ikonik' : 'Iconic Bridges'; ?></h4>
                                <p><?php echo ($current_lang === 'id') ? 'Karya jembatan dengan pemandangan lanskap alam atau laut Bali' : 'Architectural bridge works with views of Balinese nature or ocean'; ?></p>
                            </div>
                        </label>
                        <label class="wizard-option-card" data-parent-categories="Wisata Umum">
                            <input type="radio" name="pref_subcategory" value="Bendungan">
                            <div class="option-content">
                                <div class="option-icon"><i class="fa-solid fa-water"></i></div>
                                <h4><?php echo ($current_lang === 'id') ? 'Bendungan Air' : 'Water Dams'; ?></h4>
                                <p><?php echo ($current_lang === 'id') ? 'Danau bendungan buatan dengan pemandangan pegunungan dan udara segar' : 'Man-made dam lakes with mountain views and fresh breeze'; ?></p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Step 3: Location -->
                <div class="wizard-step" data-step="3">
                    <div class="step-info">
                        <h3><?php echo __('step_location_title'); ?></h3>
                        <p><?php echo __('step_location_desc'); ?></p>
                    </div>
                    <div class="wizard-options-grid location-options-grid">
                        <label class="wizard-option-card">
                            <input type="radio" name="pref_location" value="all" checked>
                            <div class="option-content">
                                <div class="option-icon"><i class="fa-solid fa-map"></i></div>
                                <h4><?php echo ($current_lang === 'id') ? 'Semua Wilayah' : 'All Regions'; ?></h4>
                                <p><?php echo ($current_lang === 'id') ? 'Tampilkan rekomendasi dari seluruh kabupaten di Bali' : 'Show recommendations from all regencies in Bali'; ?></p>
                            </div>
                        </label>
                        <?php foreach ($locations as $loc): ?>
                            <label class="wizard-option-card">
                                <input type="radio" name="pref_location" value="<?php echo htmlspecialchars($loc); ?>">
                                <div class="option-content">
                                    <div class="option-icon"><i class="fa-solid fa-location-dot"></i></div>
                                    <h4><?php echo htmlspecialchars($loc); ?></h4>
                                    <p><?php echo ($current_lang === 'id') ? 'Hanya tampilkan destinasi di ' . htmlspecialchars($loc) : 'Only show destinations in ' . htmlspecialchars($loc); ?></p>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Navigation Buttons -->
                <div class="wizard-nav">
                    <button type="button" class="btn btn-outline" id="prevBtn" disabled>
                        <i class="fa-solid fa-arrow-left"></i> <?php echo __('btn_back'); ?>
                    </button>
                    <button type="button" class="btn btn-primary" id="nextBtn">
                        <?php echo __('btn_next'); ?> <i class="fa-solid fa-arrow-right"></i>
                    </button>
                    <button type="submit" class="btn btn-primary" id="submitBtn" style="display: none;">
                        <i class="fa-solid fa-wand-magic-sparkles"></i> <?php echo __('btn_submit'); ?>
                    </button>
                </div>
            </form>
        </div>
    </section>

    <?php if ($isWizardActive): ?>
        <!-- Preferences Summary & Results Section -->
        <section class="results-section container reveal-on-scroll" id="hasil" style="padding-top: 40px;">
            <div class="preference-summary-card glass-card" style="margin-bottom: 40px; padding: 25px; border-radius: var(--radius-lg); border: 1px solid var(--glass-border); display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 20px;">
                <div style="flex: 1; min-width: 280px;">
                    <h3 style="font-size: 1.3rem; margin-bottom: 8px; font-family: var(--font-primary); color: var(--text);"><i class="fa-solid fa-circle-info" style="color: var(--primary);"></i> <?php echo __('results_title'); ?></h3>
                    <div class="pref-badges-row" style="display: flex; flex-wrap: wrap; gap: 10px;">
                        <span class="badge" style="background: rgba(var(--primary-rgb), 0.15); color: var(--primary); padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 500;">
                            <i class="fa-solid fa-tags"></i> <?php echo ($current_lang === 'id') ? 'Preferensi' : 'Preference'; ?>: <?php echo htmlspecialchars($pref_category === 'all' ? (($current_lang === 'id') ? 'Semua' : 'All') : $pref_category); ?>
                        </span>
                        <span class="badge" style="background: rgba(var(--primary-rgb), 0.15); color: var(--primary); padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 500;">
                            <i class="fa-solid fa-filter"></i> <?php echo ($current_lang === 'id') ? 'Kategori' : 'Category'; ?>: <?php echo htmlspecialchars($pref_subcategory === 'all' ? (($current_lang === 'id') ? 'Semua' : 'All') : $pref_subcategory); ?>
                        </span>
                        <span class="badge" style="background: rgba(var(--primary-rgb), 0.15); color: var(--primary); padding: 6px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: 500;">
                            <i class="fa-solid fa-location-dot"></i> <?php echo __('results_loc'); ?>: <?php echo htmlspecialchars($pref_location === 'all' ? (($current_lang === 'id') ? 'Semua Wilayah' : 'All Regions') : $pref_location); ?>
                        </span>
                    </div>
                </div>
                <a href="rekomendasi.php" class="btn btn-outline" style="white-space: nowrap; display: inline-flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-rotate-left"></i> <?php echo __('btn_repeat'); ?>
                </a>
            </div>

            <div class="section-header flex-header" style="margin-bottom: 30px;">
                <div>
                    <h2><?php echo __('results_header'); ?> <i class="fa-solid fa-sparkles" style="color:var(--primary);"></i></h2>

                </div>
                <div class="sort-by">
                    <span><?php echo __('sort_label'); ?> </span>
                    <select class="sort-select" id="sortFilter" data-baseurl="<?php echo getFilterUrl(['sort' => null, 'user_lat' => null, 'user_lng' => null]); ?>">
                        <option value="relevan" <?php if ($sort === 'relevan') echo 'selected'; ?>><?php echo __('sort_relevance'); ?></option>
                        <option value="rating" <?php if ($sort === 'rating') echo 'selected'; ?>><?php echo __('sort_rating'); ?></option>
                        <option value="nama" <?php if ($sort === 'nama') echo 'selected'; ?>><?php echo __('sort_name'); ?></option>
                        <option value="jarak" <?php if ($sort === 'jarak') echo 'selected'; ?>><?php echo ($current_lang === 'id') ? '🎯 Jarak Terdekat' : '🎯 Nearest Proximity'; ?></option>
                    </select>
                </div>
            </div>

            <!-- Dynamic Destination Cards Grid -->
            <div class="destination-grid" id="destinationGrid">
                <?php if (empty($displayedPlaces)): ?>
                    <div class="error-state">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <h3><?php echo ($current_lang === 'id') ? 'Destinasi Tidak Ditemukan' : 'Destinations Not Found'; ?></h3>
                        <p><?php echo ($current_lang === 'id') ? 'Coba sesuaikan pilihan kuesioner Anda untuk hasil yang lebih luas.' : 'Try adjusting your questionnaire choices for broader results.'; ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($displayedPlaces as $place): ?>
                        <?php
                        $nama = $place['Nama Wisata'] ?? 'Destinasi Wisata';
                        $kategori = $place['Kategori Wisata'] ?? 'Lainnya';
                        $slug = getCategorySlug($kategori);
                        $ratingVal = number_format((float)($place['rating'] ?? 4.0), 1);
                        $lokasiVal = $place['Lokasi'] ?? 'Bali';
                        $deskripsiVal = $place['Deskripsi'] ?? '';
                        if (trim($deskripsiVal) === '') {
                            $deskripsiVal = 'Nikmati pesona eksotisme keindahan pulau Bali di lokasi ini. Sangat direkomendasikan untuk healing dan mengisi waktu libur akhir pekan Anda.';
                        }
                        $imgSrc = (!empty($place['link_foto'])) ? $place['link_foto'] : getCategoryImage($kategori);
                        $placeDataJson = htmlspecialchars(json_encode($place, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                        ?>
                        <div class="dest-card" data-category="<?php echo $slug; ?>">
                            <div class="dest-img-wrap">
                                <img src="<?php echo $imgSrc; ?>" alt="<?php echo htmlspecialchars($nama); ?>" class="dest-img" loading="lazy">
                                <div class="dest-badge"><?php echo htmlspecialchars(getCategoryTranslated($kategori, $current_lang)); ?></div>
                            </div>
                            <div class="dest-content">
                                <div class="dest-meta">
                                    <span class="rating"><i class="fa-solid fa-star"></i> <?php echo $ratingVal; ?></span>
                                    <span class="location"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars(getLocationTranslated($lokasiVal, $current_lang)); ?></span>
                                    <?php if (isset($place['distance'])): ?>
                                        <span class="distance" style="color:var(--primary); font-weight:600;"><i class="fa-solid fa-location-arrow"></i> <?php echo number_format($place['distance'], 1); ?> km</span>
                                    <?php endif; ?>
                                </div>
                                <h3><?php echo htmlspecialchars($nama); ?></h3>
                                <p><?php echo htmlspecialchars(getTranslatedDescription($deskripsiVal, $nama, $kategori, $lokasiVal, $current_lang)); ?></p>

                                <div class="dest-footer">
                                    <button class="btn btn-outline btn-sm view-detail-btn" data-place="<?php echo $placeDataJson; ?>"><i class="fa-solid fa-circle-info"></i> <?php echo __('btn_detail'); ?></button>
                                    <a href="destinasi.php?action=similar&similar_to=<?php echo urlencode($nama); ?>#destinasi" class="btn btn-primary btn-sm btn-recom similar-btn"><i class="fa-solid fa-wand-magic-sparkles"></i> <?php echo __('modal_similar_btn'); ?></a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <?php if (isset($totalPages) && $totalPages > 1): ?>
                <div class="pagination-container">
                    <div class="pagination">
                        <!-- Previous Page Button -->
                        <?php if ($page > 1): ?>
                            <a href="<?php echo getFilterUrl(['page' => $page - 1]); ?>#hasil" class="page-link prev-page" title="Previous Page">
                                <i class="fa-solid fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>

                        <!-- Page Numbers -->
                        <?php
                        $range = 2; // Show 2 pages before and after current page

                        if ($page > 1 + $range) {
                            echo '<a href="' . getFilterUrl(['page' => 1]) . '#hasil" class="page-link">1</a>';
                            if ($page > 2 + $range) {
                                echo '<span class="page-dots">...</span>';
                            }
                        }

                        for ($i = max(1, $page - $range); $i <= min($totalPages, $page + $range); $i++) {
                            $activeClass = ($i === $page) ? 'active' : '';
                            echo '<a href="' . getFilterUrl(['page' => $i]) . '#hasil" class="page-link ' . $activeClass . '">' . $i . '</a>';
                        }

                        if ($page < $totalPages - $range) {
                            if ($page < $totalPages - 1 - $range) {
                                echo '<span class="page-dots">...</span>';
                            }
                            echo '<a href="' . getFilterUrl(['page' => $totalPages]) . '#hasil" class="page-link">' . $totalPages . '</a>';
                        }
                        ?>

                        <!-- Next Page Button -->
                        <?php if ($page < $totalPages): ?>
                            <a href="<?php echo getFilterUrl(['page' => $page + 1]); ?>#hasil" class="page-link next-page" title="Next Page">
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <!-- Map Section for Recommendations Only -->
        <section class="map-section container reveal-on-scroll" id="peta" style="padding-top: 40px; margin-bottom: 60px;">
            <div class="section-header">
                <h2><?php echo ($current_lang === 'id') ? 'Peta Lokasi Rekomendasi' : 'Recommended Locations Map'; ?></h2>
                <p><?php echo ($current_lang === 'id') ? 'Distribusi spasial objek wisata yang paling relevan dengan pilihan liburan Anda.' : 'Spatial distribution of tourist attractions that are most relevant to your holiday choices.'; ?></p>
            </div>
            <div class="map-container glass-card" id="map">
                <!-- Map will be rendered here by Leaflet -->
            </div>
        </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="footer">
        <div class="container footer-content">
            <div class="footer-brand">
                <a href="index.php" class="logo">
                    <i class="fa-solid fa-umbrella-beach"></i>
                    <span>BaliRecom</span>
                </a>
                <p><?php echo __('footer_desc'); ?></p>
            </div>
            <div class="footer-links">
                <h4><?php echo ($current_lang === 'id') ? 'Navigasi' : 'Navigation'; ?></h4>
                <a href="index.php"><?php echo __('nav_home'); ?></a>
                <a href="rekomendasi.php"><?php echo __('nav_assistant'); ?></a>
                <a href="index.php#destinasi"><?php echo __('nav_destinations'); ?></a>
                <a href="index.php#peta"><?php echo __('nav_map'); ?></a>
            </div>
            <div class="footer-links">
                <h4><?php echo ($current_lang === 'id') ? 'Kategori Wisata' : 'Tourism Categories'; ?></h4>
                <a href="index.php#destinasi" class="footer-cat-link" data-cat="pantai"><?php echo __('opt_pantai_title'); ?></a>
                <a href="index.php#destinasi" class="footer-cat-link" data-cat="alam"><?php echo __('opt_alam_title'); ?></a>
                <a href="index.php#destinasi" class="footer-cat-link" data-cat="budaya"><?php echo __('opt_budaya_title'); ?></a>
                <a href="index.php#destinasi" class="footer-cat-link" data-cat="pura"><?php echo __('opt_pura_title'); ?></a>
            </div>
            <div class="footer-contact">
                <h4><?php echo __('footer_contact'); ?></h4>
                <p><i class="fa-solid fa-phone"></i> +62 857-7655-7329</p>
                <p><i class="fa-solid fa-location-dot"></i> Denpasar, Bali, Indonesia</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p><?php echo __('footer_bottom'); ?></p>
        </div>
    </footer>

    <!-- Details Modal -->
    <div id="detailModal" class="modal-overlay">
        <div class="modal-card glass-card">
            <button class="modal-close-btn" id="closeModalBtn" aria-label="Tutup Detail"><i class="fa-solid fa-xmark"></i></button>
            <div class="modal-header-img-wrap">
                <div class="modal-carousel" id="modalCarousel">
                    <div class="modal-carousel-inner" id="modalCarouselInner">
                        <!-- Dynamic slides loaded by JavaScript -->
                    </div>
                    <button type="button" class="modal-carousel-btn prev" id="modalCarouselPrev" aria-label="Previous Slide">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <button type="button" class="modal-carousel-btn next" id="modalCarouselNext" aria-label="Next Slide">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                    <div class="modal-carousel-indicators" id="modalCarouselIndicators"></div>
                </div>
                <div class="modal-badge-overlay" id="modalBadge">Kategori</div>
            </div>
            <div class="modal-body-content">
                <div class="modal-meta-row">
                    <span class="modal-rating" id="modalRating"><i class="fa-solid fa-star"></i> -</span>
                    <span class="modal-location" id="modalLocation"><i class="fa-solid fa-map-pin"></i> -</span>
                </div>
                <h2 id="modalTitle">Nama Tempat Wisata</h2>
                <div class="modal-match-badge" id="modalMatchScore" style="display:none;">98% Cocok</div>
                <p id="modalDescription">Deskripsi wisata lengkap...</p>

                <!-- Explanation Box -->
                <div class="modal-explanation-box" id="modalExplanationBox" style="display:none;">
                    <h4><i class="fa-solid fa-brain"></i> <?php echo __('modal_explanation_title'); ?></h4>
                    <p id="modalExplanationText">Deskripsi kecocokan...</p>
                </div>

                <div class="modal-details-grid">

                    <div class="modal-detail-item">
                        <i class="fa-solid fa-compass"></i>
                        <div>
                            <strong><?php echo __('modal_kabupaten'); ?></strong>
                            <span id="modalDistrict">-</span>
                        </div>
                    </div>
                    <div class="modal-detail-item">
                        <i class="fa-solid fa-circle-info"></i>
                        <div>
                            <strong><?php echo __('modal_tips'); ?></strong>
                            <span id="modalTips">Gunakan tabir surya dan pakaian yang nyaman.</span>
                        </div>
                    </div>
                </div>

                <div class="modal-action-row">
                    <button class="btn btn-outline" id="modalViewOnMapBtn"><i class="fa-solid fa-map"></i> <?php echo __('modal_map_highlight'); ?></button>
                    <button class="btn btn-primary" id="modalSimilarBtn"><i class="fa-solid fa-wand-magic-sparkles"></i> <?php echo __('modal_similar_btn'); ?></button>
                </div>

                <!-- Similar Recommendations Section -->
                <div class="modal-similar-section" id="modalSimilarSection" style="display:none; margin-top:25px; padding-top:20px; border-top:1px solid var(--border-color);">
                    <h3 style="font-size: 1.1rem; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; color: var(--text-main); font-family: 'Outfit', sans-serif;"><i class="fa-solid fa-sparkles" style="color: var(--primary);"></i> <?php echo ($current_lang === 'id') ? 'Rekomendasi Serupa' : 'Similar Recommendations'; ?></h3>
                    <div class="modal-similar-grid" id="modalSimilarGrid">
                        <!-- Dynamic items loaded by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pass JSON Data from PHP to JS Safely -->
    <script>
        const wisatasRawData = <?php echo json_encode($displayedPlaces, JSON_UNESCAPED_UNICODE); ?>;
        const activeLang = '<?php echo $current_lang; ?>';
    </script>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/script.js?v=<?php echo time(); ?>"></script>
</body>

</html>
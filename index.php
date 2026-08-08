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
    if (!$categoryName)
        return 'umum';
    $lower = strtolower(trim($categoryName));
    if (strpos($lower, 'pantai') !== false || strpos($lower, 'rekreasi') !== false)
        return 'rekreasi';
    if (strpos($lower, 'alam') !== false || strpos($lower, 'gunung') !== false || strpos($lower, 'air terjun') !== false || strpos($lower, 'danau') !== false || strpos($lower, 'hutan') !== false)
        return 'alam';
    if (strpos($lower, 'budaya') !== false || strpos($lower, 'desa') !== false || strpos($lower, 'seni') !== false || strpos($lower, 'sejarah') !== false || strpos($lower, 'museum') !== false)
        return 'budaya';
    if (strpos($lower, 'pura') !== false || strpos($lower, 'religi') !== false || strpos($lower, 'candi') !== false)
        return 'pura';
    if (strpos($lower, 'umum') !== false || strpos($lower, 'taman') !== false || strpos($lower, 'buatan') !== false || strpos($lower, 'kebun') !== false)
        return 'umum';
    return 'umum';
}

function getCategoryImage($category)
{
    $slug = getCategorySlug($category);
    if ($slug === 'pantai')
        return 'assets/images/bali_beach.png';
    if ($slug === 'pura')
        return 'assets/images/bali_temple.png';
    return 'assets/images/bali_hero_bg.png'; // default image
}

function getFilterUrl($newParams)
{
    $params = $_GET;
    // Remove default or empty parameters to keep url clean
    foreach ($newParams as $k => $v) {
        if ($v === null || $v === '') {
            unset($params[$k]);
        } else {
            $params[$k] = $v;
        }
    }
    // Clean up empty params
    foreach ($params as $k => $v) {
        if ($v === 'all' && $k !== 'category' && $k !== 'location' && $k !== 'rating') {
            unset($params[$k]);
        }
    }
    return '?' . http_build_query($params);
}

// ----------------------------------------------------
// PROCESS PARAMS & FILTERING DATA
// ----------------------------------------------------

$action = $_GET['action'] ?? '';
$category = $_GET['category'] ?? 'all';
$location = $_GET['location'] ?? 'all';
$rating = $_GET['rating'] ?? 'all';
$sort = $_GET['sort'] ?? '';
$q = $_GET['q'] ?? '';
$similar_to = $_GET['similar_to'] ?? '';
$limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 9;
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$user_lat = isset($_GET['user_lat']) && $_GET['user_lat'] !== '' ? (float) $_GET['user_lat'] : null;
$user_lng = isset($_GET['user_lng']) && $_GET['user_lng'] !== '' ? (float) $_GET['user_lng'] : null;

// Default headers
$resultHeaderTitle = __('all_destinations_title');
$resultHeaderDesc = __('all_destinations_subtitle');

$filteredPlaces = [];

if ($action === 'similar' && !empty($similar_to)) {
    $resultHeaderTitle = __('similar_to_label') . " <span style='color:var(--primary); font-style:italic;'>" . htmlspecialchars($similar_to) . "</span>";
    $resultHeaderDesc = ($current_lang === 'id') ? "Daftar rekomendasi destinasi yang memiliki suasana, aktivitas, atau daya tarik yang serupa." : "Recommended destinations with similar vibes, activities, or highlights.";
    $filteredPlaces = getRecommendationsFromPython('similar', ['similar_to' => $similar_to]) ?? [];
} else {
    $isWizardActive = false;
    $scoredPlaces = $wisataData;
    if (empty($sort)) {
        $sort = 'rating';
    }

    $tempPlaces = [];
    foreach ($scoredPlaces as $place) {
        // Filter category
        if ($category !== 'all') {
            if (getCategorySlug($place['Kategori Wisata'] ?? '') !== $category) {
                continue;
            }
        }

        // Filter location
        if ($location !== 'all') {
            if (($place['Lokasi'] ?? '') !== $location) {
                continue;
            }
        }

        // Filter rating
        if ($rating !== 'all') {
            $minRating = (float) $rating;
            if ((float) ($place['rating'] ?? 0) < $minRating) {
                continue;
            }
        }

        // Filter search term
        if (!empty($q)) {
            $normalizedQ = strtolower(trim($q));
            $words = preg_split('/\s+/', $normalizedQ, -1, PREG_SPLIT_NO_EMPTY);

            $pName = strtolower($place['Nama Wisata'] ?? '');
            $pCat = strtolower($place['Kategori Wisata'] ?? '');
            $pLoc = strtolower($place['Lokasi'] ?? '');
            $pDesc = strtolower($place['Deskripsi'] ?? '');

            $matchAllWords = true;
            foreach ($words as $word) {
                // Skip common short prepositions/conjunctions to improve search flexibility
                if (strlen($word) <= 2 && in_array($word, ['di', 'ke', 'dan', 'yg', 'ia', 'da'])) {
                    continue;
                }

                if (
                    strpos($pName, $word) === false &&
                    strpos($pCat, $word) === false &&
                    strpos($pLoc, $word) === false &&
                    strpos($pDesc, $word) === false
                ) {
                    $matchAllWords = false;
                    break;
                }
            }

            if (!$matchAllWords) {
                continue;
            }
        }

        if ($user_lat !== null && $user_lng !== null) {
            $pLat = isset($place['latitude']) ? (float) $place['latitude'] : 0;
            $pLng = isset($place['longitude']) ? (float) $place['longitude'] : 0;
            if ($pLat != 0 && $pLng != 0) {
                $place['distance'] = calculateHaversineDistance($user_lat, $user_lng, $pLat, $pLng);
            }
        }
        $tempPlaces[] = $place;
    }

    // Sorting
    if (!empty($q)) {
        // 1. Detect location in search query
        $detectedLocation = null;
        $normalizedQ = strtolower($q);
        foreach ($locations as $loc) {
            $coreLoc = str_replace(['kabupaten', 'kota', ' '], '', strtolower($loc));
            if (strpos(str_replace(' ', '', $normalizedQ), $coreLoc) !== false) {
                $detectedLocation = $loc;
                break;
            }
        }

        // 2. Detect category in search query
        $detectedCategory = null;
        $catSlug = getCategorySlug($q);
        if ($catSlug !== 'lainnya') {
            $detectedCategory = $catSlug;
        }

        usort($tempPlaces, function ($a, $b) use ($detectedLocation, $detectedCategory) {
            if ($detectedLocation !== null) {
                $aLocMatch = (($a['Lokasi'] ?? '') === $detectedLocation) ? 1 : 0;
                $bLocMatch = (($b['Lokasi'] ?? '') === $detectedLocation) ? 1 : 0;

                if ($aLocMatch !== $bLocMatch) {
                    return $bLocMatch <=> $aLocMatch; // matching location first
                }

                if ($detectedCategory !== null) {
                    $aCatMatch = (getCategorySlug($a['Kategori Wisata'] ?? '') === $detectedCategory) ? 1 : 0;
                    $bCatMatch = (getCategorySlug($b['Kategori Wisata'] ?? '') === $detectedCategory) ? 1 : 0;

                    if ($aCatMatch !== $bCatMatch) {
                        return $bCatMatch <=> $aCatMatch; // matching category first
                    }
                }
            } else {
                // If no location detected, but category is detected
                if ($detectedCategory !== null) {
                    $aCatMatch = (getCategorySlug($a['Kategori Wisata'] ?? '') === $detectedCategory) ? 1 : 0;
                    $bCatMatch = (getCategorySlug($b['Kategori Wisata'] ?? '') === $detectedCategory) ? 1 : 0;

                    if ($aCatMatch !== $bCatMatch) {
                        return $bCatMatch <=> $aCatMatch;
                    }
                }
            }

            $aRating = (float) ($a['rating'] ?? 0);
            $bRating = (float) ($b['rating'] ?? 0);
            if ($bRating != $aRating) {
                return $bRating <=> $aRating; // sort by rating descending
            }
            return strcasecmp($a['Nama Wisata'] ?? '', $b['Nama Wisata'] ?? '');
        });
    } else {
        // Standard Sorting (Rating, Nama, Relevan)
        if ($sort === 'jarak' && $user_lat !== null && $user_lng !== null) {
            usort($tempPlaces, function ($a, $b) {
                $aDist = isset($a['distance']) ? (float) $a['distance'] : 999999;
                $bDist = isset($b['distance']) ? (float) $b['distance'] : 999999;
                return $aDist <=> $bDist;
            });
        } else if ($sort === 'rating') {
            usort($tempPlaces, function ($a, $b) {
                return ($b['rating'] ?? 0) <=> ($a['rating'] ?? 0);
            });
        } else if ($sort === 'nama') {
            usort($tempPlaces, function ($a, $b) {
                return strcasecmp($a['Nama Wisata'] ?? '', $b['Nama Wisata'] ?? '');
            });
        } else if ($sort === 'relevan') {
            if ($isWizardActive) {
                usort($tempPlaces, function ($a, $b) {
                    return $b['matchScore'] <=> $a['matchScore'];
                });
            } else {
                usort($tempPlaces, function ($a, $b) {
                    return ($b['rating'] ?? 0) <=> ($a['rating'] ?? 0);
                });
            }
        }
    }

    $filteredPlaces = $tempPlaces;

    if (!empty($q)) {
        $resultHeaderTitle = __('search_results_for') . ' "' . htmlspecialchars($q) . '"';
        $resultHeaderDesc = ($current_lang === 'id')
            ? 'Menampilkan destinasi wisata yang cocok dengan kata kunci pencarian Anda.'
            : 'Showing tourist destinations matching your search query.';
    } elseif ($category !== 'all') {
        $catLabel = ucfirst($category);
        if ($category === 'pantai')
            $catLabel = ($current_lang === 'id') ? 'Pantai' : 'Beach';
        else if ($category === 'alam')
            $catLabel = ($current_lang === 'id') ? 'Wisata Alam' : 'Nature';
        else if ($category === 'budaya')
            $catLabel = ($current_lang === 'id') ? 'Budaya & Sejarah' : 'Culture & History';
        else if ($category === 'pura')
            $catLabel = ($current_lang === 'id') ? 'Pura & Religi' : 'Temple & Religion';
        else if ($category === 'buatan')
            $catLabel = ($current_lang === 'id') ? 'Taman & Rekreasi' : 'Recreation & Parks';

        $resultHeaderTitle = ($current_lang === 'id') ? 'Kategori: ' . $catLabel : 'Category: ' . $catLabel;
        $resultHeaderDesc = ($current_lang === 'id')
            ? 'Menampilkan hasil penyaringan berdasarkan kategori ' . strtolower($catLabel) . '.'
            : 'Showing filtered results under ' . strtolower($catLabel) . ' category.';
    }
}

$totalFilteredCount = count($filteredPlaces);
$totalPages = (int) ceil($totalFilteredCount / $limit);
$totalPages = max(1, $totalPages);
$page = min($totalPages, max(1, $page));
$startIndex = ($page - 1) * $limit;
$displayedPlaces = array_slice($filteredPlaces, $startIndex, $limit);
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BaliRecom - Temukan Destinasi Wisata Terbaik di Bali</title>
    <meta name="description" content="Sistem Rekomendasi Tempat Wisata di Bali Berdasarkan Preferensi Anda">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=DM+Serif+Display:ital@0;1&display=swap"
        rel="stylesheet">
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

            <div class="nav-links">
                <a href="index.php" class="active">
                    <?php echo __('nav_home'); ?>
                </a>
                <a href="destinasi.php">
                    <?php echo __('nav_destinations'); ?>
                </a>
                <a href="rekomendasi.php">
                    <?php echo __('nav_assistant'); ?>
                </a>
                <a href="tentang.php">
                    <?php echo __('nav_about'); ?>
                </a>
            </div>
            <div class="nav-actions">
                <a href="?lang=<?php echo $current_lang === 'id' ? 'en' : 'id'; ?>" class="btn btn-outline lang-toggle"
                    title="<?php echo $current_lang === 'id' ? 'Switch to English' : 'Ubah ke Bahasa Indonesia'; ?>">
                    <i class="fa-solid fa-globe"></i>
                    <span>
                        <?php echo $current_lang === 'id' ? 'EN' : 'ID'; ?>
                    </span>
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

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-slideshow" id="heroSlideshow">
            <div class="hero-slide active"
                style="background-image: url('https://images.unsplash.com/photo-1537996194471-e657df975ab4?auto=format&fit=crop&w=1920&q=80');">
            </div>
            <div class="hero-slide"
                style="background-image: url('https://images.unsplash.com/photo-1555400038-63f5ba517a47?auto=format&fit=crop&w=1920&q=80');">
            </div>
            <div class="hero-slide"
                style="background-image: url('https://images.unsplash.com/photo-1518548419970-58e3b4079ab2?auto=format&fit=crop&w=1920&q=80');">
            </div>
            <div class="hero-slide"
                style="background-image: url('https://images.unsplash.com/photo-1573790387438-4da905039392?auto=format&fit=crop&w=1920&q=80');">
            </div>
            <div class="hero-slide"
                style="background-image: url('https://images.unsplash.com/photo-1604999333679-b86d54738315?auto=format&fit=crop&w=1920&q=80');">
            </div>
        </div>
        <div class="hero-overlay"></div>
        <div class="container hero-content">
            <h1>
                <?php echo __('hero_title_1'); ?> <span>
                    <?php echo __('hero_title_span'); ?>
                </span>
            </h1>
            <p>
                <?php echo __('hero_desc'); ?>
            </p>

            <!-- Hero Action Cards with Explanations -->
            <div class="hero-actions-container"
                style="margin-top: 54px; display: flex; gap: 20px; justify-content: center; flex-wrap: wrap;">

                <!-- Action Card 1: Explore Destinations -->
                <a href="destinasi.php" class="hero-action-card primary-card">
                    <div class="hero-action-icon">
                        <i class="fa-solid fa-compass"></i>
                    </div>
                    <div class="hero-action-text">
                        <h3>
                            <?php echo ($current_lang === 'id') ? 'Jelajahi Destinasi Wisata' : 'Explore Destinations'; ?>
                        </h3>
                        <p>
                            <?php echo ($current_lang === 'id') ? 'Saring & cari seluruh katalog tempat wisata Bali berdasarkan lokasi & kategori.' : 'Filter & search all Bali tourist attractions by location & category.'; ?>
                        </p>
                    </div>
                    <div class="hero-action-arrow">
                        <i class="fa-solid fa-arrow-right"></i>
                    </div>
                </a>

                <!-- Action Card 2: AI Recommendation -->
                <a href="rekomendasi.php" class="hero-action-card secondary-card">
                    <div class="hero-action-icon">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                    </div>
                    <div class="hero-action-text">
                        <h3>
                            <?php echo ($current_lang === 'id') ? 'Asisten Rekomendasi' : 'AI Recommendation'; ?>
                        </h3>
                        <p>
                            <?php echo ($current_lang === 'id') ? 'Rekomendasi pintar berbasis preferensi yang disesuaikan dengan minat liburan Anda.' : 'Smart AI recommendation tailored to your travel preferences.'; ?>
                        </p>
                    </div>
                    <div class="hero-action-arrow">
                        <i class="fa-solid fa-arrow-right"></i>
                    </div>
                </a>

            </div>
        </div>
    </section>



    <!-- Spotlight / Destinasi Pilihan Section (Modern Editorial Layout) -->
    <section class="spotlight-showcase-section reveal-on-scroll">
        <div class="container spotlight-showcase-container">
            <?php
            $popularNames = [
                'Tanah Lot',
                'Pantai Kelingking',
                'Pura Ulun Danu Beratan Bedugul',
                'Pantai Jimbaran',
                'Gitgit Waterfall',
                'Pantai Pandawa',
                'Uluwatu Temple',
                'Garuda Wisnu Kencana Cultural Park',
                'Sacred Monkey Forest Sanctuary',
                'Pantai Kuta',
            ];

            // Build index of dataset by name for fast lookup
            $wisataIndex = [];
            foreach ($wisataData as $p) {
                $key = strtolower(trim($p['Nama Wisata'] ?? ''));
                $wisataIndex[$key] = $p;
            }

            $curated = [];
            foreach ($popularNames as $popularName) {
                $key = strtolower(trim($popularName));
                if (isset($wisataIndex[$key])) {
                    $curated[] = $wisataIndex[$key];
                }
            }

            $categoryIconMap = [
                'pantai' => ['icon' => 'fa-water', 'text' => 'Pantai & Laut'],
                'alam' => ['icon' => 'fa-mountain-sun', 'text' => 'Alam & Pegunungan'],
                'budaya' => ['icon' => 'fa-masks-theater', 'text' => 'Seni & Budaya'],
                'pura' => ['icon' => 'fa-vihara', 'text' => 'Religi & Pura'],
                'buatan' => ['icon' => 'fa-camera', 'text' => 'Wisata Buatan'],
            ];
            if (!function_exists('getPlaceCatInfo')) {
                function getPlaceCatInfo($place, $map)
                {
                    $slug = getCategorySlug($place['Kategori Wisata'] ?? '');
                    return $map[$slug] ?? ['icon' => 'fa-map-pin', 'text' => $place['Kategori Wisata'] ?? 'Wisata'];
                }
            }
            $totalCurated = count($curated);
            ?>

            <!-- Left Panel: Headline, Subtitle, CTA & Progress Controls -->
            <div class="spotlight-left-panel">
                <span class="spotlight-kicker"><i class="fa-solid fa-sparkles"></i> SPOTLIGHT</span>
                <h2 class="spotlight-main-title">
                    <?php echo ($current_lang === 'id') ? 'Destinasi Pilihan Bali' : 'Bali Spotlight Destinations'; ?>
                </h2>
                <p class="spotlight-main-desc">
                    <?php echo ($current_lang === 'id') ? 'Temukan keindahan cagar budaya, pesona pantai pesisir eksotis, serta keajaiban alam terpopuler yang menjadi kebanggaan Pulau Dewata.' : 'Discover the beauty of cultural heritage, exotic beaches, and famous natural wonders that define Bali.'; ?>
                </p>
                <a href="destinasi.php" class="spotlight-explore-btn">
                    <span>
                        <?php echo ($current_lang === 'id') ? 'Temukan Pilihan Bali' : 'Explore Bali Highlights'; ?>
                    </span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>

                <!-- Slide Progress Controls (Matching Image: 01 / 07, Progress Line, Arrows) -->
                <div class="spotlight-controls-block">
                    <div class="spotlight-counter">
                        <span id="spotlightCurrentNum">01</span>
                        <span class="spotlight-total-num">/
                            <?php echo sprintf("%02d", $totalCurated); ?>
                        </span>
                    </div>
                    <div class="spotlight-progress-line-track">
                        <div class="spotlight-progress-line-fill" id="spotlightProgressFill"
                            style="width: <?php echo $totalCurated > 0 ? (1 / $totalCurated) * 100 : 100; ?>%;"></div>
                    </div>
                    <div class="spotlight-nav-buttons">
                        <button type="button" id="popularPrev" class="spotlight-nav-btn" aria-label="Previous Slide"><i
                                class="fa-solid fa-chevron-left"></i></button>
                        <button type="button" id="popularNext" class="spotlight-nav-btn" aria-label="Next Slide"><i
                                class="fa-solid fa-chevron-right"></i></button>
                    </div>
                </div>
            </div>

            <!-- Right Panel: Cards Carousel Track -->
            <div class="spotlight-right-panel" id="popularSlider">
                <div class="spotlight-viewport">
                    <div class="spotlight-track" id="popularSliderTrack">
                        <?php foreach ($curated as $index => $place):
                            $img = !empty($place['link_foto']) ? $place['link_foto'] : getCategoryImage($place['Kategori Wisata'] ?? '');
                            $pData = htmlspecialchars(json_encode($place, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
                            $pInfo = getPlaceCatInfo($place, $categoryIconMap);
                            $pName = $place['Nama Wisata'] ?? '';
                            $pRating = number_format((float) ($place['rating'] ?? 4.5), 1);
                            $pLokasi = $place['Lokasi'] ?? 'Bali';
                            $pDesc = trim($place['Deskripsi'] ?? '');
                            if ($pDesc === '')
                                $pDesc = 'Destinasi ikonik pilihan di Bali yang sangat memukau dan wajib dikunjungi.';
                            ?>
                            <div class="spotlight-card" style="background-image: url('<?php echo $img; ?>');">
                                <div class="spotlight-card-overlay"></div>
                                <span class="spotlight-card-tag"><i class="fa-solid <?php echo $pInfo['icon']; ?>"></i>
                                    <?php echo $pInfo['text']; ?>
                                </span>
                                <div class="spotlight-card-content">
                                    <h3 class="spotlight-card-title">
                                        <?php echo htmlspecialchars($pName); ?>
                                    </h3>
                                    <p class="spotlight-card-desc">
                                        <?php echo htmlspecialchars(mb_strimwidth(getTranslatedDescription($pDesc, $pName, $place['Kategori Wisata'] ?? '', $pLokasi, $current_lang), 0, 120, '…')); ?>
                                    </p>
                                    <div class="spotlight-card-action">
                                        <div class="spotlight-meta-group">
                                            <span class="spotlight-meta-star"><i class="fa-solid fa-star"></i>
                                                <?php echo $pRating; ?>
                                            </span>
                                            <span class="spotlight-meta-dot">•</span>
                                            <span class="spotlight-meta-loc"><i class="fa-solid fa-location-dot"></i>
                                                <?php echo htmlspecialchars(str_replace(['Kabupaten ', 'Kota '], '', $pLokasi)); ?>
                                            </span>
                                        </div>
                                        <button class="spotlight-card-btn view-detail-btn"
                                            data-place="<?php echo $pData; ?>">
                                            <span>
                                                <?php echo __('btn_detail'); ?>
                                            </span>
                                            <i class="fa-solid fa-arrow-right"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 9 Regencies & Cities Section (Visual Grid Cards) -->
    <section class="regencies-section container reveal-on-scroll" id="wilayah"
        style="padding-top: 50px; padding-bottom: 50px;">
        <div class="section-header text-center" style="margin-bottom: 40px;">
            <h2>
                <?php echo ($current_lang === 'id') ? 'Eksplorasi 9 Kabupaten & Kota di Bali' : 'Explore 9 Regencies & Cities in Bali'; ?>
            </h2>
            <p>
                <?php echo ($current_lang === 'id') ? 'Temukan keindahan dan keunikan pesona alam serta kebudayaan di setiap wilayah Pulau Dewata' : 'Discover the beauty and uniqueness of natural charm and culture in every regency of Bali'; ?>
            </p>
        </div>

        <?php
        $regenciesData = [
            [
                'name' => 'Kabupaten Badung',
                'title' => 'Badung',
                'tagline' => 'Pantai Eksotis & Pusat Resort',
                'count' => '120+ Wisata',
                'img' => 'https://cdn1-production-images-kly.akamaized.net/zf65U929G5oWEtQk0r6t2Ne2dGg=/800x450/smart/filters:quality(75):strip_icc():format(webp)/kly-media-production/medias/3329604/original/048957100_1608538005-shutterstock_630184619.jpg',
                'link' => 'destinasi.php?location=Kabupaten+Badung#destinasi'
            ],
            [
                'name' => 'Kabupaten Gianyar',
                'title' => 'Gianyar & Ubud',
                'tagline' => 'Jantung Seni & Kebudayaan',
                'count' => '115+ Wisata',
                'img' => 'https://static.toiimg.com/thumb/54525860/Indonesia-Bali-Gianyar-2353-Bali.jpg?width=1200&height=900',
                'link' => 'destinasi.php?location=Kabupaten+Gianyar#destinasi'
            ],
            [
                'name' => 'Kota Denpasar',
                'title' => 'Denpasar',
                'tagline' => 'Ibu Kota & Sejarah Budaya',
                'count' => '85+ Wisata',
                'img' => 'https://contents.garuda-indonesia.com/magnoliaPublic/.imaging/default/dam/EDM/202602/Denpasar-1.jpg/jcr:content.jpg',
                'link' => 'destinasi.php?location=Kota+Denpasar#destinasi'
            ],
            [
                'name' => 'Kabupaten Tabanan',
                'title' => 'Tabanan',
                'tagline' => 'Sawah Terasering & Pura Laut',
                'count' => '90+ Wisata',
                'img' => 'https://www.pelago.com/img/products/ID-Indonesia/full-day-tour-of-tabanan/8b6d4ac3-b94f-4ca6-90b8-ee283c522f34_full-day-tour-of-tabanan.jpg',
                'link' => 'destinasi.php?location=Kabupaten+Tabanan#destinasi'
            ],
            [
                'name' => 'Kabupaten Buleleng',
                'title' => 'Buleleng',
                'tagline' => 'Air Terjun & Danau Asri',
                'count' => '95+ Wisata',
                'img' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR8JxC3ljozrqh2LV9ULIxjSaH0f62kkzXVY6wICvnGKw&s=10',
                'link' => 'destinasi.php?location=Kabupaten+Buleleng#destinasi'
            ],
            [
                'name' => 'Kabupaten Bangli',
                'title' => 'Bangli & Kintamani',
                'tagline' => 'Gunung Batur & Pemandangan Alam',
                'count' => '65+ Wisata',
                'img' => 'https://asset.kompas.com/crops/YR7gj8fXP4zEdJgJNyhXTx-7XEA=/0x0:739x493/1200x800/data/photo/2020/09/20/5f67319e4d806.jpg',
                'link' => 'destinasi.php?location=Kabupaten+Bangli#destinasi'
            ],
            [
                'name' => 'Kabupaten Karangasem',
                'title' => 'Karangasem',
                'tagline' => 'Gerbang Suci & Pesisir Timur',
                'count' => '70+ Wisata',
                'img' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQtVsYkOKKMkQWN9tm_VYc76vuc539d3-Q2umN_la7bSQ&s=10',
                'link' => 'destinasi.php?location=Kabupaten+Karangasem#destinasi'
            ],
            [
                'name' => 'Kabupaten Klungkung',
                'title' => 'Klungkung & Nusa Penida',
                'tagline' => 'Tebing Laut & Pulau Eksotis',
                'count' => '55+ Wisata',
                'img' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTnf5NYEDHR2C2fZFLmj60CgtRdwwEnn1nuJg9VQIz9nQ&s=10',
                'link' => 'destinasi.php?location=Kabupaten+Klungkung#destinasi'
            ],
            [
                'name' => 'Kabupaten Jembrana',
                'title' => 'Jembrana',
                'tagline' => 'Taman Nasional Bali Barat',
                'count' => '45+ Wisata',
                'img' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcT7j-3RCSbqt2yEzF6wkci4w2u6ZPrFHjlpk_4FI21q7A&s=10',
                'link' => 'destinasi.php?location=Kabupaten+Jembrana#destinasi'
            ],
        ];
        ?>

        <div class="regencies-grid">
            <?php foreach ($regenciesData as $reg): ?>
                <a href="<?php echo $reg['link']; ?>" class="regency-card">
                    <div class="regency-card-img" style="background-image: url('<?php echo $reg['img']; ?>');"></div>
                    <div class="regency-card-overlay"></div>
                    <div class="regency-card-content">
                        <span class="regency-count-badge"><i class="fa-solid fa-location-dot"></i>
                            <?php echo $reg['count']; ?>
                        </span>
                        <h3 class="regency-title">
                            <?php echo htmlspecialchars($reg['title']); ?>
                        </h3>
                        <p class="regency-tagline">
                            <?php echo htmlspecialchars($reg['tagline']); ?>
                        </p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>



    <!-- Map Section -->
    <section class="map-section container reveal-on-scroll" id="peta">
        <div class="section-header">
            <h2>
                <?php echo __('map_title'); ?>
            </h2>
            <p>
                <?php echo __('map_subtitle'); ?>
            </p>
        </div>
        <div class="map-container glass-card" id="map">
            <!-- Map will be rendered here by Leaflet -->
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container footer-content">
            <div class="footer-brand">
                <a href="index.php" class="logo">
                    <i class="fa-solid fa-umbrella-beach"></i>
                    <span>BaliRecom</span>
                </a>
                <p>
                    <?php echo __('footer_desc'); ?>
                </p>
            </div>
            <div class="footer-links">
                <h4>
                    <?php echo ($current_lang === 'id') ? 'Navigasi' : 'Navigation'; ?>
                </h4>
                <a href="index.php">
                    <?php echo __('nav_home'); ?>
                </a>
                <a href="rekomendasi.php">
                    <?php echo __('nav_assistant'); ?>
                </a>
                <a href="index.php#destinasi">
                    <?php echo __('nav_destinations'); ?>
                </a>
                <a href="index.php#peta">
                    <?php echo __('nav_map'); ?>
                </a>
            </div>
            <div class="footer-links">
                <h4>
                    <?php echo ($current_lang === 'id') ? 'Kategori Wisata' : 'Tourism Categories'; ?>
                </h4>
                <a href="index.php#destinasi" class="footer-cat-link" data-cat="pantai">
                    <?php echo __('opt_pantai_title'); ?>
                </a>
                <a href="index.php#destinasi" class="footer-cat-link" data-cat="alam">
                    <?php echo __('opt_alam_title'); ?>
                </a>
                <a href="index.php#destinasi" class="footer-cat-link" data-cat="budaya">
                    <?php echo __('opt_budaya_title'); ?>
                </a>
                <a href="index.php#destinasi" class="footer-cat-link" data-cat="pura">
                    <?php echo __('opt_pura_title'); ?>
                </a>
            </div>
            <div class="footer-contact">
                <h4>
                    <?php echo __('footer_contact'); ?>
                </h4>
                <p><i class="fa-solid fa-phone"></i> +62 857-7655-7329</p>
                <p><i class="fa-solid fa-location-dot"></i> Denpasar, Bali, Indonesia</p>
            </div>
        </div>
        <div class="footer-bottom">
            <p>
                <?php echo __('footer_bottom'); ?>
            </p>
        </div>
    </footer>

    <!-- Details Modal -->
    <div id="detailModal" class="modal-overlay">
        <div class="modal-card glass-card">
            <button class="modal-close-btn" id="closeModalBtn" aria-label="Tutup Detail"><i
                    class="fa-solid fa-xmark"></i></button>
            <div class="modal-header-img-wrap">
                <div class="modal-carousel" id="modalCarousel">
                    <div class="modal-carousel-inner" id="modalCarouselInner">
                        <!-- Dynamic slides loaded by JavaScript -->
                    </div>
                    <button type="button" class="modal-carousel-btn prev" id="modalCarouselPrev"
                        aria-label="Previous Slide">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <button type="button" class="modal-carousel-btn next" id="modalCarouselNext"
                        aria-label="Next Slide">
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

                <!-- Explanation Box (Option 3) -->
                <div class="modal-explanation-box" id="modalExplanationBox" style="display:none;">
                    <h4><i class="fa-solid fa-brain"></i>
                        <?php echo __('modal_explanation_title'); ?>
                    </h4>
                    <p id="modalExplanationText">Deskripsi kecocokan...</p>
                </div>

                <div class="modal-details-grid">

                    <div class="modal-detail-item">
                        <i class="fa-solid fa-compass"></i>
                        <div>
                            <strong>
                                <?php echo __('modal_kabupaten'); ?>
                            </strong>
                            <span id="modalDistrict">-</span>
                        </div>
                    </div>
                    <div class="modal-detail-item">
                        <i class="fa-solid fa-circle-info"></i>
                        <div>
                            <strong>
                                <?php echo __('modal_tips'); ?>
                            </strong>
                            <span id="modalTips">Gunakan tabir surya dan pakaian yang nyaman.</span>
                        </div>
                    </div>
                </div>

                <div class="modal-action-row">
                    <button class="btn btn-outline" id="modalViewOnMapBtn"><i class="fa-solid fa-map"></i>
                        <?php echo __('modal_map_highlight'); ?>
                    </button>
                    <button class="btn btn-primary" id="modalSimilarBtn"><i class="fa-solid fa-wand-magic-sparkles"></i>
                        <?php echo __('modal_similar_btn'); ?>
                    </button>
                </div>

                <!-- Similar Recommendations Section -->
                <div class="modal-similar-section" id="modalSimilarSection"
                    style="display:none; margin-top:25px; padding-top:20px; border-top:1px solid var(--border-color);">
                    <h3
                        style="font-size: 1.1rem; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; color: var(--text-main); font-family: 'Outfit', sans-serif;">
                        <i class="fa-solid fa-sparkles" style="color: var(--primary);"></i>
                        <?php echo ($current_lang === 'id') ? 'Rekomendasi Serupa' : 'Similar Recommendations'; ?>
                    </h3>
                    <div class="modal-similar-grid" id="modalSimilarGrid">
                        <!-- Dynamic items loaded by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pass JSON Data from PHP to JS Safely -->
    <script>
        const wisatasRawData = <?php echo json_encode($filteredPlaces, JSON_UNESCAPED_UNICODE); ?>;
        const activeLang = '<?php echo $current_lang; ?>';
    </script>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Custom JS -->
    <script src="assets/js/script.js?v=<?php echo time(); ?>"></script>
</body>

</html>
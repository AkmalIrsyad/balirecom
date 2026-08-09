<?php
require_once 'lang.php';

// Load dataset wisata.json (600 destinations)
$wisataJson = @file_get_contents('assets/wisata.json');
$wisataData = json_decode($wisataJson, true);
if (!is_array($wisataData)) {
    $wisataData = [];
}

// Get dynamic list of locations
$locations = [];
foreach ($wisataData as $item) {
    if (!empty($item['Lokasi'])) {
        $locations[] = trim($item['Lokasi']);
    }
}
$locations = array_unique($locations);
sort($locations);

// Helper function for category slugs
function getCategorySlug($cat)
{
    if (!$cat) return 'umum';
    $cat = strtolower(trim($cat));
    if (strpos($cat, 'pantai') !== false || strpos($cat, 'rekreasi') !== false) return 'rekreasi';
    if (strpos($cat, 'alam') !== false || strpos($cat, 'air terjun') !== false || strpos($cat, 'gunung') !== false || strpos($cat, 'danau') !== false) return 'alam';
    if (strpos($cat, 'budaya') !== false || strpos($cat, 'desa') !== false || strpos($cat, 'sejarah') !== false) return 'budaya';
    if (strpos($cat, 'pura') !== false || strpos($cat, 'religi') !== false || strpos($cat, 'candi') !== false) return 'pura';
    if (strpos($cat, 'taman') !== false || strpos($cat, 'buatan') !== false || strpos($cat, 'umum') !== false) return 'umum';
    return 'umum';
}

function getCategoryImage($cat)
{
    $slug = getCategorySlug($cat);
    switch ($slug) {
        case 'pantai':
            return 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80';
        case 'alam':
            return 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?auto=format&fit=crop&w=800&q=80';
        case 'budaya':
            return 'https://images.unsplash.com/photo-1555400038-63f5ba517a47?auto=format&fit=crop&w=800&q=80';
        case 'pura':
            return 'https://images.unsplash.com/photo-1518548419970-58e3b4079ab2?auto=format&fit=crop&w=800&q=80';
        case 'buatan':
            return 'https://images.unsplash.com/photo-1573790387438-4da905039392?auto=format&fit=crop&w=800&q=80';
        default:
            return 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?auto=format&fit=crop&w=800&q=80';
    }
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
    echo json_encode([]);
    exit;
}

// Request parameters
$action = isset($_GET['action']) ? trim($_GET['action']) : '';
$similar_to = isset($_GET['similar_to']) ? trim($_GET['similar_to']) : '';
$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : 'all';
$location = isset($_GET['location']) ? trim($_GET['location']) : 'all';
$rating = isset($_GET['rating']) ? trim($_GET['rating']) : 'all';
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit = 9;

// Default headers
$resultHeaderTitle = __('nav_destinations');
$resultHeaderDesc = __('all_destinations_subtitle');

if ($action === 'similar' && !empty($similar_to)) {
    $resultHeaderTitle = ($current_lang === 'id' ? 'Rekomendasi Serupa dengan' : 'Recommendations similar to') . " <span style='color:var(--primary); font-style:italic;'>" . htmlspecialchars($similar_to) . "</span>";
    $resultHeaderDesc = ($current_lang === 'id') ? "Daftar rekomendasi destinasi yang memiliki suasana, aktivitas, atau daya tarik yang serupa." : "Recommended destinations with similar vibes, activities, or highlights.";
    $filteredPlaces = getRecommendationsFromPython('similar', ['similar_to' => $similar_to]) ?? [];
} else {
    if (!empty($q)) {
        $resultHeaderTitle = __('search_results_for') . ' "' . htmlspecialchars($q) . '"';
    }
    // Filter dataset
    $filteredPlaces = array_filter($wisataData, function ($place) use ($q, $category, $location, $rating) {
        // 1. Text Search Filter (q)
        if (!empty($q)) {
            $searchableText = strtolower(
                ($place['Nama Wisata'] ?? '') . ' ' .
                    ($place['Kategori Wisata'] ?? '') . ' ' .
                    ($place['Lokasi'] ?? '') . ' ' .
                    ($place['Deskripsi'] ?? '') . ' ' .
                    ($place['preferensi'] ?? '')
            );
            if (strpos($searchableText, strtolower($q)) === false) {
                return false;
            }
        }
        // 2. Category Filter
        if ($category !== 'all' && !empty($category)) {
            $slug = getCategorySlug($place['Kategori Wisata'] ?? '');
            if ($slug !== $category) {
                return false;
            }
        }
        // 3. Location Filter
        if ($location !== 'all' && !empty($location)) {
            if (trim($place['Lokasi'] ?? '') !== $location) {
                return false;
            }
        }
        // 4. Rating Filter
        if ($rating !== 'all' && !empty($rating)) {
            $minRating = (float)$rating;
            $placeRating = (float)($place['rating'] ?? 0);
            if ($placeRating < $minRating) {
                return false;
            }
        }
        return true;
    });

    $filteredPlaces = array_values($filteredPlaces);
}

// Sort
if ($sort === 'rating') {
    usort($filteredPlaces, function ($a, $b) {
        return ($b['rating'] ?? 0) <=> ($a['rating'] ?? 0);
    });
} else if ($sort === 'nama') {
    usort($filteredPlaces, function ($a, $b) {
        return strcasecmp($a['Nama Wisata'] ?? '', $b['Nama Wisata'] ?? '');
    });
}

// Total and pagination (9 items per page)
$totalFilteredCount = count($filteredPlaces);
$totalPages = (int)ceil($totalFilteredCount / $limit);
$totalPages = max(1, $totalPages);
$page = min($totalPages, max(1, $page));
$startIndex = ($page - 1) * $limit;
$displayedPlaces = array_slice($filteredPlaces, $startIndex, $limit);

// Helper for Filter URLs
function getFilterUrl($newParams = [])
{
    global $action, $similar_to, $q, $category, $location, $rating, $sort, $page;
    $params = [
        'action' => $action,
        'similar_to' => $similar_to,
        'q' => $q,
        'category' => $category,
        'location' => $location,
        'rating' => $rating,
        'sort' => $sort,
        'page' => $page
    ];
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
        if (empty($v)) {
            unset($params[$k]);
        }
    }
    return 'destinasi.php?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('nav_destinations'); ?> - BaliRecom</title>
    <meta name="description" content="Jelajahi dan saring 600 destinasi tempat wisata menarik di Bali.">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
    <!-- FontAwesome icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="destinasi-page">
    <!-- Navbar -->
    <nav class="navbar glass">
        <div class="container nav-container">
            <a href="index.php" class="logo">
                <i class="fa-solid fa-umbrella-beach"></i>
                <span>BaliRecom</span>
            </a>

            <div class="nav-links">
                <a href="index.php"><?php echo __('nav_home'); ?></a>
                <a href="destinasi.php" class="active"><?php echo __('nav_destinations'); ?></a>
                <a href="rekomendasi.php"><?php echo __('nav_assistant'); ?></a>
                <a href="tentang.php"><?php echo __('nav_about'); ?></a>
            </div>
            <div class="nav-actions">
                <a href="?lang=<?php echo $current_lang === 'id' ? 'en' : 'id'; ?>" class="btn btn-outline lang-toggle" title="<?php echo $current_lang === 'id' ? 'Switch to English' : 'Ubah ke Bahasa Indonesia'; ?>">
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

    <!-- Compact Hero Banner -->
    <section class="hero" style="min-height: 460px; padding: 120px 0 80px 0; position: relative; z-index: 100;">
        <div class="hero-slideshow">
            <div class="hero-slide active" style="background-image: url('https://images.unsplash.com/photo-1518548419970-58e3b4079ab2?auto=format&fit=crop&w=1920&q=85');"></div>
        </div>
        <div class="hero-overlay"></div>
        <div class="container hero-content" style="position: relative; z-index: 100;">
            <h1><?php echo ($current_lang === 'id') ? 'Destinasi Wisata' : 'Tourist Destinations'; ?> <span>Bali</span></h1>
            <p><?php echo ($current_lang === 'id') ? 'Temukan pesona tersembunyi dan keindahan alam di setiap sudut Pulau Dewata.' : 'Discover hidden gems and natural beauty in every corner of the Island of Gods.'; ?></p>

            <!-- Hero Search & Filter Card -->
            <form action="destinasi.php#destinasi" method="GET" class="hero-search-card" style="position: relative; z-index: 100;">
                <!-- Row 1: Search Input & Submit Button -->
                <div class="hero-search-row-top">
                    <div class="hero-search-input-wrap">
                        <i class="fa-solid fa-magnifying-glass search-icon"></i>
                        <input type="text" name="q" placeholder="<?php echo __('search_placeholder'); ?>" id="searchInput" value="<?php echo htmlspecialchars($q); ?>" autocomplete="off">
                        <div id="searchSuggestions" class="search-suggestions-dropdown" style="display: none;"></div>
                    </div>
                    <button type="submit" class="hero-search-btn">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <span><?php echo __('search_btn'); ?></span>
                    </button>
                </div>

                <!-- Row 2: Custom Interactive Filter Dropdowns -->
                <div class="hero-search-row-bottom">

                    <!-- Location Filter -->
                    <div class="custom-dropdown-wrap" id="dropdownLocation">
                        <input type="hidden" name="location" id="inputLocation" value="<?php echo htmlspecialchars($location); ?>">
                        <button type="button" class="custom-dropdown-trigger">
                            <i class="fa-solid fa-location-dot icon-left"></i>
                            <span class="trigger-label">
                                <?php
                                if ($location === 'all' || empty($location)) {
                                    echo ($current_lang === 'id') ? 'Semua Wilayah' : 'All Regions';
                                } else {
                                    echo htmlspecialchars($location);
                                }
                                ?>
                            </span>
                            <i class="fa-solid fa-chevron-down arrow-icon"></i>
                        </button>
                        <div class="custom-dropdown-menu">
                            <div class="dropdown-item <?php if ($location === 'all' || empty($location)) echo 'active'; ?>" data-value="all">
                                <span><?php echo ($current_lang === 'id') ? 'Semua Wilayah' : 'All Regions'; ?></span>
                                <i class="fa-solid fa-check check-icon"></i>
                            </div>
                            <?php foreach ($locations as $loc): ?>
                                <div class="dropdown-item <?php if ($location === $loc) echo 'active'; ?>" data-value="<?php echo htmlspecialchars($loc); ?>">
                                    <span><?php echo htmlspecialchars($loc); ?></span>
                                    <i class="fa-solid fa-check check-icon"></i>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Category Filter -->
                    <?php
                    $catLabels = [
                        'all' => ($current_lang === 'id') ? 'Semua Kategori' : 'All Categories',
                        'alam' => ($current_lang === 'id') ? 'Wisata Alam' : 'Nature Tourism',
                        'budaya' => ($current_lang === 'id') ? 'Wisata Budaya' : 'Cultural Tourism',
                        'rekreasi' => ($current_lang === 'id') ? 'Wisata Rekreasi & Pantai' : 'Recreation & Beach',
                        'pura' => ($current_lang === 'id') ? 'Wisata Pura & Religi' : 'Temples & Religious',
                        'umum' => ($current_lang === 'id') ? 'Wisata Umum & Taman' : 'General & Parks',
                    ];
                    $currentCatLabel = $catLabels[$category] ?? $catLabels['all'];
                    ?>
                    <div class="custom-dropdown-wrap" id="dropdownCategory">
                        <input type="hidden" name="category" id="inputCategory" value="<?php echo htmlspecialchars($category); ?>">
                        <button type="button" class="custom-dropdown-trigger">
                            <i class="fa-solid fa-compass icon-left"></i>
                            <span class="trigger-label"><?php echo htmlspecialchars($currentCatLabel); ?></span>
                            <i class="fa-solid fa-chevron-down arrow-icon"></i>
                        </button>
                        <div class="custom-dropdown-menu">
                            <?php foreach ($catLabels as $val => $lbl): ?>
                                <div class="dropdown-item <?php if ($category === $val) echo 'active'; ?>" data-value="<?php echo $val; ?>">
                                    <span><?php echo htmlspecialchars($lbl); ?></span>
                                    <i class="fa-solid fa-check check-icon"></i>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Rating Filter -->
                    <?php
                    $ratingLabels = [
                        'all' => ($current_lang === 'id') ? 'Semua Rating' : 'All Ratings',
                        '4.5' => '★ 4.5+ Rating',
                        '4.0' => '★ 4.0+ Rating',
                        '3.5' => '★ 3.5+ Rating',
                    ];
                    $currentRatingLabel = $ratingLabels[$rating] ?? $ratingLabels['all'];
                    ?>
                    <div class="custom-dropdown-wrap" id="dropdownRating">
                        <input type="hidden" name="rating" id="inputRating" value="<?php echo htmlspecialchars($rating); ?>">
                        <button type="button" class="custom-dropdown-trigger">
                            <i class="fa-solid fa-star icon-left"></i>
                            <span class="trigger-label"><?php echo htmlspecialchars($currentRatingLabel); ?></span>
                            <i class="fa-solid fa-chevron-down arrow-icon"></i>
                        </button>
                        <div class="custom-dropdown-menu">
                            <?php foreach ($ratingLabels as $val => $lbl): ?>
                                <div class="dropdown-item <?php if ($rating === $val) echo 'active'; ?>" data-value="<?php echo htmlspecialchars($val); ?>">
                                    <span><?php echo htmlspecialchars($lbl); ?></span>
                                    <i class="fa-solid fa-check check-icon"></i>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </section>

    <!-- Statistics Counter Section (Attraction Dataset Highlights) -->
    <section class="stats-overview-section container reveal-on-scroll" style="margin-top: -30px; margin-bottom: 20px; position: relative; z-index: 1;">
        <div class="stats-grid-card">
            <div class="stat-item">
                <div class="stat-icon"><i class="fa-solid fa-map-location-dot"></i></div>
                <div class="stat-number">700+</div>
                <div class="stat-label"><?php echo ($current_lang === 'id') ? 'Total Destinasi Wisata' : 'Total Tourist Attractions'; ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-icon"><i class="fa-solid fa-city"></i></div>
                <div class="stat-number">9</div>
                <div class="stat-label"><?php echo ($current_lang === 'id') ? 'Kabupaten & Kota Bali' : 'Regencies in Bali'; ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-icon"><i class="fa-solid fa-star"></i></div>
                <div class="stat-number">4.8</div>
                <div class="stat-label"><?php echo ($current_lang === 'id') ? 'Rating Rata-rata Wisata' : 'Average Attraction Rating'; ?></div>
            </div>
            <div class="stat-item">
                <div class="stat-icon"><i class="fa-solid fa-layer-group"></i></div>
                <div class="stat-number">15</div>
                <div class="stat-label"><?php echo ($current_lang === 'id') ? 'Kategori & Subkategori' : 'Categories & Subcategories'; ?></div>
            </div>
        </div>
    </section>

    <!-- Destinations List Section -->
    <section class="destinations container reveal-on-scroll" id="destinasi" style="padding-top: 50px;">
        <div class="section-header flex-header">
            <div>
                <h2><?php echo $resultHeaderTitle; ?></h2>
                <p><?php echo $resultHeaderDesc; ?></p>
                <?php if ($action === 'similar'): ?>
                    <div style="margin-top: 12px;">
                        <a href="destinasi.php#destinasi" class="btn btn-outline btn-sm" style="font-size: 0.82rem; padding: 6px 14px; border-radius: 20px;">
                            <i class="fa-solid fa-rotate-left"></i> <?php echo ($current_lang === 'id') ? 'Tampilkan Semua Destinasi' : 'Show All Destinations'; ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            <div class="sort-by">
                <span><?php echo __('sort_label'); ?> </span>
                <select class="sort-select" id="sortFilter" data-baseurl="<?php echo getFilterUrl(['sort' => null]); ?>">
                    <option value="" <?php if (empty($sort)) echo 'selected'; ?>><?php echo ($current_lang === 'id') ? 'Default' : 'Default'; ?></option>
                    <option value="rating" <?php if ($sort === 'rating') echo 'selected'; ?>><?php echo __('sort_rating'); ?></option>
                    <option value="nama" <?php if ($sort === 'nama') echo 'selected'; ?>><?php echo __('sort_name'); ?></option>
                </select>
            </div>
        </div>

        <!-- Dynamic Destination Cards Grid -->
        <div class="destination-grid" id="destinationGrid">
            <?php if (empty($displayedPlaces)): ?>
                <div class="error-state">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <h3><?php echo ($current_lang === 'id') ? 'Destinasi Tidak Ditemukan' : 'Destinations Not Found'; ?></h3>
                    <p><?php echo ($current_lang === 'id') ? 'Coba sesuaikan kata kunci pencarian atau filter kategori Anda.' : 'Try adjusting your search keywords or category filters.'; ?></p>
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

        <?php if ($totalPages > 1): ?>
            <div class="pagination-container">
                <div class="pagination">
                    <!-- Previous Page Button -->
                    <?php if ($page > 1): ?>
                        <a href="<?php echo getFilterUrl(['page' => $page - 1]); ?>#destinasi" class="page-link prev-page" title="Previous Page">
                            <i class="fa-solid fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <!-- Page Numbers -->
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);

                    if ($startPage > 1) {
                        echo '<a href="' . getFilterUrl(['page' => 1]) . '#destinasi" class="page-link">1</a>';
                        if ($startPage > 2) {
                            echo '<span class="page-dots">...</span>';
                        }
                    }

                    for ($i = $startPage; $i <= $endPage; $i++) {
                        $activeClass = ($i === $page) ? 'active' : '';
                        echo '<a href="' . getFilterUrl(['page' => $i]) . '#destinasi" class="page-link ' . $activeClass . '">' . $i . '</a>';
                    }

                    if ($endPage < $totalPages) {
                        if ($endPage < $totalPages - 1) {
                            echo '<span class="page-dots">...</span>';
                        }
                        echo '<a href="' . getFilterUrl(['page' => $totalPages]) . '#destinasi" class="page-link">' . $totalPages . '</a>';
                    }
                    ?>

                    <!-- Next Page Button -->
                    <?php if ($page < $totalPages): ?>
                        <a href="<?php echo getFilterUrl(['page' => $page + 1]); ?>#destinasi" class="page-link next-page" title="Next Page">
                            <i class="fa-solid fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>

    <!-- Interactive Map Section -->
    <section class="map-section container reveal-on-scroll" id="peta" style="margin-top: 60px; margin-bottom: 60px;">
        <div class="section-header">
            <h2><?php echo __('map_title'); ?></h2>
            <p><?php echo __('map_subtitle'); ?></p>
        </div>
        <div class="map-container glass-card" id="map">
            <!-- Map will be rendered here by Leaflet -->
        </div>
    </section>

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
                <a href="destinasi.php"><?php echo __('nav_destinations'); ?></a>
                <a href="rekomendasi.php"><?php echo __('nav_assistant'); ?></a>
                <a href="tentang.php"><?php echo __('nav_about'); ?></a>
            </div>
            <div class="footer-contact">
                <h4><?php echo __('footer_contact'); ?></h4>
                <p><i class="fa-solid fa-phone"></i> +62 857-7655-7329</p>
                <p><i class="fa-solid fa-location-dot"></i> Denpasar, Bali, Indonesia</p>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="container">
                <p>&copy; <?php echo date('Y'); ?> BaliRecom. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Pass JSON Data from PHP to JS Safely -->
    <script>
        const wisatasRawData = <?php echo json_encode($displayedPlaces, JSON_UNESCAPED_UNICODE); ?>;
        const activeLang = '<?php echo $current_lang; ?>';
    </script>
    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <!-- Custom JavaScript -->
    <script src="assets/js/script.js?v=<?php echo time(); ?>"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const hasQuery = (urlParams.has('q') && urlParams.get('q').trim() !== '') ||
                (urlParams.has('location') && urlParams.get('location') !== 'all') ||
                (urlParams.has('category') && urlParams.get('category') !== 'all') ||
                (urlParams.has('rating') && urlParams.get('rating') !== 'all') ||
                urlParams.has('sort') ||
                urlParams.has('page') ||
                window.location.hash === '#destinasi';

            if (hasQuery) {
                const target = document.getElementById('destinasi');
                if (target) {
                    setTimeout(function() {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }, 100);
                }
            }
        });
    </script>
</body>

</html>
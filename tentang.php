<?php
require_once 'lang.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $current_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ($current_lang === 'id') ? 'Tentang Kami – BaliRecom' : 'About Bali – BaliRecom'; ?></title>
    <meta name="description" content="<?php echo ($current_lang === 'id') ? 'Temukan keindahan Bali — budaya, alam, dan destinasi wisata terbaik Pulau Dewata dalam satu panduan lengkap.' : 'Discover the beauty of Bali — its culture, nature, and the best tourist destinations on the Island of the Gods.'; ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=DM+Serif+Display:ital@0;1&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* ─── Reveal animation ─── */
        .reveal-on-scroll {
            opacity: 0;
            transform: translateY(28px);
            transition: opacity .65s ease, transform .65s ease;
        }
        .reveal-on-scroll.visible {
            opacity: 1;
            transform: translateY(0);
        }

        :root { --about-gold: #d4a847; }

        /* Hero */
        .about-hero {
            position: relative; height: 520px; overflow: hidden;
            display: flex; align-items: center; justify-content: center; text-align: center;
        }
        .about-hero-bg {
            position: absolute; inset: 0;
            background-image: url('https://images.unsplash.com/photo-1537996194471-e657df975ab4?auto=format&fit=crop&w=1920&q=90'),
                              linear-gradient(135deg, #0a2a3a 0%, #0d4a3a 50%, #0a3a2a 100%);
            background-size: cover; background-position: center 30%;
            transform: scale(1.05); transition: transform 8s ease;
        }
        .about-hero:hover .about-hero-bg { transform: scale(1.0); }
        .about-hero-overlay {
            position: absolute; inset: 0;
            background: linear-gradient(to bottom, rgba(6,10,19,.45) 0%, rgba(6,10,19,.72) 70%, rgba(6,10,19,.95) 100%);
        }
        .about-hero-content { position: relative; z-index: 2; max-width: 800px; padding: 0 24px; }
        .about-hero-kicker {
            display: inline-flex; align-items: center; gap: 8px;
            font-size: .8rem; font-weight: 700; letter-spacing: .18em;
            color: var(--about-gold); text-transform: uppercase;
            margin-bottom: 18px; padding: 6px 16px;
            border: 1px solid rgba(212,168,71,.4); border-radius: 50px;
            background: rgba(212,168,71,.1);
        }
        .about-hero h1 {
            font-family: 'DM Serif Display', serif; font-size: clamp(2.4rem, 5vw, 3.8rem);
            font-weight: 400; color: #fff; line-height: 1.15; margin-bottom: 20px;
        }
        .about-hero h1 em { font-style: italic; color: var(--about-gold); }
        .about-hero p { font-size: 1.05rem; color: rgba(255,255,255,.82); line-height: 1.7; max-width: 580px; margin: 0 auto; }

        /* Breadcrumb */
        .about-breadcrumb { background: var(--bg-main); border-bottom: 1px solid var(--border-color); padding: 14px 0; }
        .breadcrumb-inner { display: flex; align-items: center; gap: 10px; font-size: .875rem; color: var(--text-muted); }
        .breadcrumb-inner a { color: var(--primary); text-decoration: none; }
        .breadcrumb-inner a:hover { text-decoration: underline; }
        .breadcrumb-sep { color: var(--border-color); }

        .about-main { padding: 80px 0; }

        /* Stats Bar */
        .about-stats-bar {
            display: grid; grid-template-columns: repeat(4,1fr);
            background: var(--card-bg); border: 1px solid var(--border-color);
            border-radius: 20px; overflow: hidden; margin-bottom: 70px;
            box-shadow: 0 8px 32px rgba(0,0,0,.12);
        }
        .stat-item {
            padding: 30px 24px; text-align: center;
            border-right: 1px solid var(--border-color); transition: background .3s;
        }
        .stat-item:last-child { border-right: none; }
        .stat-item:hover { background: rgba(13,148,136,.06); }
        .stat-icon {
            width: 44px; height: 44px; border-radius: 12px;
            background: rgba(13,148,136,.12); display: flex;
            align-items: center; justify-content: center;
            font-size: 1.2rem; color: var(--primary); margin: 0 auto 12px;
        }
        .stat-num { font-family: 'DM Serif Display',serif; font-size: 2rem; color: var(--text-main); line-height: 1; margin-bottom: 6px; }
        .stat-label { font-size: .82rem; color: var(--text-muted); font-weight: 500; }
        @media(max-width:768px){
            .about-stats-bar { grid-template-columns: repeat(2,1fr); }
            .stat-item:nth-child(2) { border-right: none; }
            .stat-item:nth-child(1),.stat-item:nth-child(2) { border-bottom: 1px solid var(--border-color); }
        }

        /* Section headings */
        .about-section-title { font-family: 'DM Serif Display',serif; font-size: 2rem; color: var(--text-main); margin-bottom: 12px; }
        .about-section-sub { font-size: 1rem; color: var(--text-muted); line-height: 1.6; margin-bottom: 40px; max-width: 560px; }
        .section-tag { display: inline-flex; align-items: center; gap: 7px; font-size: .75rem; font-weight: 700; letter-spacing: .15em; text-transform: uppercase; color: var(--primary); margin-bottom: 10px; }

        /* Intro Split */
        .about-intro-split { display: grid; grid-template-columns: 1fr 1fr; gap: 56px; align-items: center; margin-bottom: 80px; }
        @media(max-width:900px){ .about-intro-split { grid-template-columns: 1fr; gap: 32px; } }
        .about-intro-text p { font-size: 1rem; color: var(--text-muted); line-height: 1.85; margin-bottom: 18px; }
        .about-intro-img-grid { display: grid; grid-template-columns: 1fr 1fr; grid-template-rows: auto auto; gap: 14px; }
        .about-img-card { border-radius: 18px; overflow: hidden; position: relative; }
        .about-img-card img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .5s ease; }
        .about-img-card:hover img { transform: scale(1.06); }
        .about-img-card:first-child { grid-row: span 2; height: 380px; }
        .about-img-card:not(:first-child) { height: 180px; }
        .about-img-label { position: absolute; bottom: 0; left: 0; right: 0; padding: 14px; background: linear-gradient(to top, rgba(0,0,0,.75) 0%,transparent 100%); font-size: .8rem; font-weight: 600; color: #fff; }

        /* Culture Grid */
        .culture-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 24px; margin-bottom: 80px; }
        @media(max-width:900px){ .culture-grid { grid-template-columns: repeat(2,1fr); } }
        @media(max-width:560px){ .culture-grid { grid-template-columns: 1fr; } }
        .culture-card {
            border-radius: 20px;
            overflow: hidden;
            position: relative;
            height: 330px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.18);
            border: 1px solid rgba(255,255,255,0.1);
        }
        .culture-card-bg {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            transition: transform 0.5s ease;
        }
        .culture-card:hover .culture-card-bg { transform: scale(1.08); }
        .culture-card-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(
                to top,
                rgba(3, 7, 18, 0.96) 0%,
                rgba(3, 7, 18, 0.78) 45%,
                rgba(3, 7, 18, 0.4) 75%,
                rgba(3, 7, 18, 0.15) 100%
            );
        }
        .culture-card-body {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 24px;
            z-index: 2;
        }
        .culture-card-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: rgba(13, 148, 136, 0.95);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.35);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: #ffffff;
            margin-bottom: 12px;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.35);
        }
        .culture-card-icon i {
            display: inline-block;
            line-height: 1;
            color: #ffffff;
        }
        .culture-card-title {
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: 1.35rem;
            font-weight: 700;
            color: #ffffff !important;
            margin-bottom: 8px;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.9);
            letter-spacing: 0.01em;
        }
        .culture-card-desc {
            font-size: 0.88rem;
            color: #f1f5f9 !important;
            line-height: 1.6;
            text-shadow: 0 1px 6px rgba(0, 0, 0, 0.95);
            margin: 0;
        }
        .regency-cards-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 20px; }
        @media(max-width:900px){ .regency-cards-grid { grid-template-columns: repeat(2,1fr); } }
        @media(max-width:560px){ .regency-cards-grid { grid-template-columns: 1fr; } }
        .regency-info-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 18px; padding: 24px; transition: all .3s; position: relative; overflow: hidden; }
        .regency-info-card::before { content:''; position: absolute; top:0; left:0; right:0; height:3px; background: linear-gradient(90deg,var(--primary),#0ea5a0); transform: scaleX(0); transition: transform .35s; }
        .regency-info-card:hover { transform: translateY(-4px); box-shadow: 0 12px 40px rgba(0,0,0,.15); }
        .regency-info-card:hover::before { transform: scaleX(1); }
        .reg-header { display: flex; align-items: center; gap: 14px; margin-bottom: 14px; }
        .reg-icon { width: 44px; height: 44px; border-radius: 12px; background: linear-gradient(135deg,var(--primary),#0ea5a0); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; color: #fff; flex-shrink: 0; }
        .reg-name { font-family: 'DM Serif Display',serif; font-size: 1.1rem; color: var(--text-main); }
        .reg-tagline { font-size: .78rem; color: var(--primary); font-weight: 600; }
        .reg-desc { font-size: .875rem; color: var(--text-muted); line-height: 1.6; margin-bottom: 16px; }
        .reg-highlights { display: flex; flex-wrap: wrap; gap: 6px; }
        .reg-highlight-tag { font-size: .73rem; font-weight: 600; padding: 4px 10px; border-radius: 50px; background: rgba(13,148,136,.1); color: var(--primary); border: 1px solid rgba(13,148,136,.25); }

        /* Facts */
        .facts-section { background: linear-gradient(135deg,#060a13 0%,#0a1120 100%); border-radius: 28px; padding: 64px 56px; margin-bottom: 80px; position: relative; overflow: hidden; }
        .facts-section::before { content:'BALI'; position: absolute; right:-20px; top:50%; transform: translateY(-50%); font-family:'DM Serif Display',serif; font-size:18rem; color:rgba(255,255,255,.02); pointer-events:none; }
        @media(max-width:768px){ .facts-section { padding: 40px 28px; } }
        .facts-grid { display: grid; grid-template-columns: repeat(2,1fr); gap: 28px; }
        @media(max-width:640px){ .facts-grid { grid-template-columns: 1fr; } }
        .fact-item { display: flex; gap: 16px; align-items: flex-start; }
        .fact-icon { width: 42px; height: 42px; border-radius: 12px; background: rgba(13,148,136,.18); border: 1px solid rgba(13,148,136,.35); display: flex; align-items: center; justify-content: center; font-size: 1.05rem; color: var(--primary); flex-shrink: 0; }
        .fact-body h4 { font-size: .95rem; font-weight: 700; color: #fff; margin-bottom: 5px; }
        .fact-body p { font-size: .84rem; color: rgba(255,255,255,.62); line-height: 1.6; }

        /* Tips */
        .tips-grid { display: grid; grid-template-columns: repeat(2,1fr); gap: 20px; margin-bottom: 80px; }
        @media(max-width:640px){ .tips-grid { grid-template-columns: 1fr; } }
        .tip-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 18px; padding: 26px; display: flex; gap: 18px; align-items: flex-start; transition: all .3s; }
        .tip-card:hover { transform: translateY(-3px); box-shadow: 0 10px 30px rgba(0,0,0,.12); }
        .tip-num { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg,var(--primary),#0ea5a0); color: #fff; font-size: .9rem; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .tip-body h4 { font-size: .95rem; font-weight: 700; color: var(--text-main); margin-bottom: 6px; }
        .tip-body p { font-size: .84rem; color: var(--text-muted); line-height: 1.6; }

        /* System card */
        .system-info-section { margin-bottom: 80px; }
        .system-card { background: var(--card-bg); border: 1px solid var(--border-color); border-radius: 24px; padding: 48px; display: grid; grid-template-columns: 1fr 1.4fr; gap: 48px; align-items: center; }
        @media(max-width:768px){ .system-card { grid-template-columns: 1fr; padding: 32px 24px; } }
        .system-badge { display: inline-flex; align-items: center; gap: 8px; font-size: .75rem; font-weight: 700; letter-spacing: .15em; text-transform: uppercase; color: var(--primary); background: rgba(13,148,136,.1); border: 1px solid rgba(13,148,136,.25); padding: 5px 14px; border-radius: 50px; margin-bottom: 16px; }
        .system-card h3 { font-family: 'DM Serif Display',serif; font-size: 1.7rem; color: var(--text-main); margin-bottom: 14px; }
        .system-card p { font-size: .95rem; color: var(--text-muted); line-height: 1.75; margin-bottom: 18px; }
        .method-tags { display: flex; flex-wrap: wrap; gap: 8px; }
        .method-tag { font-size: .82rem; font-weight: 600; padding: 6px 14px; border-radius: 50px; background: rgba(13,148,136,.1); color: var(--primary); border: 1px solid rgba(13,148,136,.3); }
        .system-visual { background: linear-gradient(135deg,#060a13,#0a1120); border-radius: 18px; padding: 32px; }
        .flow-step { display: flex; align-items: center; gap: 16px; margin-bottom: 12px; padding: 14px 18px; background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.08); border-radius: 12px; }
        .flow-step:last-child { margin-bottom: 0; }
        .flow-step-num { width: 32px; height: 32px; border-radius: 50%; background: linear-gradient(135deg,var(--primary),#0ea5a0); color: #fff; font-size: .8rem; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .flow-step-text h5 { font-size: .88rem; font-weight: 700; color: #fff; margin-bottom: 2px; }
        .flow-step-text p { font-size: .78rem; color: rgba(255,255,255,.5); margin: 0; line-height: 1.4; }

        /* CTA */
        .about-cta { background: linear-gradient(135deg,var(--primary) 0%,#0ea5a0 100%); border-radius: 24px; padding: 60px 48px; text-align: center; position: relative; overflow: hidden; margin-bottom: 80px; }
        .about-cta::before { content:''; position: absolute; width:400px; height:400px; border-radius:50%; background:rgba(255,255,255,.07); top:-200px; right:-100px; }
        .about-cta h2 { font-family:'DM Serif Display',serif; font-size: 2.2rem; color: #fff; margin-bottom: 14px; position: relative; z-index: 1; }
        .about-cta p { font-size: 1rem; color: rgba(255,255,255,.85); margin-bottom: 34px; position: relative; z-index: 1; }
        .cta-buttons { display: flex; gap: 14px; justify-content: center; flex-wrap: wrap; position: relative; z-index: 1; }
        .cta-btn-primary { padding: 14px 32px; background: #fff; color: var(--primary); border: none; border-radius: 50px; font-size: .95rem; font-weight: 700; text-decoration: none; transition: all .3s; display: inline-flex; align-items: center; gap: 10px; }
        .cta-btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,.2); }
        .cta-btn-outline { padding: 14px 32px; background: transparent; color: #fff; border: 2px solid rgba(255,255,255,.55); border-radius: 50px; font-size: .95rem; font-weight: 600; text-decoration: none; transition: all .3s; display: inline-flex; align-items: center; gap: 10px; }
        .cta-btn-outline:hover { background: rgba(255,255,255,.12); border-color: rgba(255,255,255,.85); }
    </style>
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
                <a href="index.php"><?php echo __('nav_home'); ?></a>
                <a href="destinasi.php"><?php echo __('nav_destinations'); ?></a>
                <a href="rekomendasi.php"><?php echo __('nav_assistant'); ?></a>
                <a href="tentang.php" class="active"><?php echo __('nav_about'); ?></a>
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

    <!-- Hero -->
    <section class="about-hero">
        <div class="about-hero-bg"></div>
        <div class="about-hero-overlay"></div>
        <div class="about-hero-content">
            <div class="about-hero-kicker">
                <i class="fa-solid fa-umbrella-beach"></i>
                <?php echo ($current_lang === 'id') ? 'PULAU DEWATA &middot; INDONESIA' : 'ISLAND OF THE GODS &middot; INDONESIA'; ?>
            </div>
            <h1>
                <?php echo ($current_lang === 'id')
                    ? 'Mengenal <em>Bali</em> Lebih Dekat'
                    : 'Discover the Wonders of <em>Bali</em>'; ?>
            </h1>
            <p>
                <?php echo ($current_lang === 'id')
                    ? 'Surga tropis yang kaya budaya, alam memesona, dan tradisi ribuan tahun yang tetap hidup hingga kini. Bali bukan sekadar destinasi — ia adalah pengalaman jiwa yang tak terlupakan.'
                    : 'A tropical paradise rich in culture, breathtaking nature, and thousand-year-old traditions still alive today. Bali is not just a destination — it is an unforgettable experience for the soul.'; ?>
            </p>
        </div>
    </section>

    <!-- Breadcrumb -->
    <div class="about-breadcrumb">
        <div class="container">
            <div class="breadcrumb-inner">
                <a href="index.php"><i class="fa-solid fa-house"></i> <?php echo __('nav_home'); ?></a>
                <span class="breadcrumb-sep"><i class="fa-solid fa-chevron-right" style="font-size:.7rem;"></i></span>
                <span><?php echo __('nav_about'); ?></span>
            </div>
        </div>
    </div>

    <main class="about-main">
        <div class="container">

            <!-- Stats Bar -->
            <div class="about-stats-bar reveal-on-scroll">
                <div class="stat-item">
                    <div class="stat-icon"><i class="fa-solid fa-map-location-dot"></i></div>
                    <div class="stat-num">5,780 km&sup2;</div>
                    <div class="stat-label"><?php echo $current_lang==='id'?'Luas Wilayah':'Total Area'; ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon"><i class="fa-solid fa-users"></i></div>
                    <div class="stat-num">4.5 <?php echo $current_lang==='id'?'Juta':'Million'; ?></div>
                    <div class="stat-label"><?php echo $current_lang==='id'?'Penduduk (2024)':'Population (2024)'; ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon"><i class="fa-solid fa-plane-arrival"></i></div>
                    <div class="stat-num">6.3 <?php echo $current_lang==='id'?'Juta':'Million'; ?></div>
                    <div class="stat-label"><?php echo $current_lang==='id'?'Wisatawan/Tahun':'Tourists/Year'; ?></div>
                </div>
                <div class="stat-item">
                    <div class="stat-icon"><i class="fa-solid fa-location-dot"></i></div>
                    <div class="stat-num">9 <?php echo $current_lang==='id'?'Kab/Kota':'Regions'; ?></div>
                    <div class="stat-label"><?php echo $current_lang==='id'?'Kabupaten & Kota':'Regencies & City'; ?></div>
                </div>
            </div>

            <!-- Intro Split -->
            <div class="about-intro-split reveal-on-scroll">
                <div class="about-intro-text">
                    <div class="section-tag"><i class="fa-solid fa-leaf"></i> <?php echo $current_lang==='id'?'TENTANG BALI':'ABOUT BALI'; ?></div>
                    <h2 class="about-section-title"><?php echo $current_lang==='id'?'Surga Dunia di Tengah Samudra Hindia':'Heaven on Earth in the Indian Ocean'; ?></h2>
                    <?php if ($current_lang === 'id'): ?>
                    <p>Bali adalah sebuah provinsi sekaligus pulau yang terletak di Kepulauan Nusa Tenggara, Indonesia. Dengan luas sekitar 5.780 km&sup2;, pulau ini dikenal di seluruh dunia sebagai salah satu destinasi wisata paling menakjubkan — mengundang lebih dari 6 juta wisatawan setiap tahunnya.</p>
                    <p>Dikenal sebagai <strong>Pulau Dewata</strong>, Bali menawarkan perpaduan sempurna antara keindahan alam tropis yang megah — mulai dari pantai berpasir halus, hutan tropis yang rimbun, sawah terasering Subak yang mendunia, hingga gunung berapi yang anggun — berpadu harmonis dengan kekayaan budaya Hindu-Bali yang unik dan tak tertandingi.</p>
                    <p>Di setiap sudut Bali, Anda akan menemukan upacara sakral, ukiran seni yang memukau, tari-tarian khas yang membuai jiwa, serta keramahan masyarakat yang hangat dan tulus — membuat setiap kunjungan menjadi kenangan yang tak terhapuskan.</p>
                    <?php else: ?>
                    <p>Bali is a province and island located in the Lesser Sunda Islands of Indonesia. Covering approximately 5,780 km&sup2;, this island is renowned worldwide as one of the most breathtaking tourist destinations — welcoming over 6 million visitors each year.</p>
                    <p>Known as the <strong>Island of the Gods</strong>, Bali offers a perfect blend of majestic tropical natural beauty — from fine white-sand beaches, lush tropical forests, the world-famous Subak terraced rice fields, to elegant volcanic mountains — harmoniously combined with the rich Hindu-Balinese cultural heritage.</p>
                    <p>At every corner of Bali, you will discover sacred ceremonies, awe-inspiring artistic carvings, soul-stirring traditional dances, and the warmth and sincerity of the local people — making every visit a memory that lasts a lifetime.</p>
                    <?php endif; ?>
                </div>
                <div class="about-intro-img-grid">
                    <div class="about-img-card">
                        <img src="https://images.unsplash.com/photo-1537996194471-e657df975ab4?auto=format&fit=crop&w=600&q=85" alt="Tanah Lot Bali">
                        <div class="about-img-label"><i class="fa-solid fa-vihara"></i> Tanah Lot</div>
                    </div>
                    <div class="about-img-card">
                        <img src="https://images.unsplash.com/photo-1555400038-63f5ba517a47?auto=format&fit=crop&w=600&q=85" alt="Tegallalang Rice Terraces">
                        <div class="about-img-label"><i class="fa-solid fa-seedling"></i> Tegallalang</div>
                    </div>
                    <div class="about-img-card">
                        <img src="https://images.unsplash.com/photo-1604999333679-b86d54738315?auto=format&fit=crop&w=600&q=85" alt="Uluwatu Temple">
                        <div class="about-img-label"><i class="fa-solid fa-vihara"></i> Uluwatu</div>
                    </div>
                </div>
            </div>

            <!-- Culture Grid -->
            <div class="reveal-on-scroll" style="margin-bottom:40px;">
                <div class="section-tag"><i class="fa-solid fa-masks-theater"></i> <?php echo $current_lang==='id'?'BUDAYA &amp; ALAM':'CULTURE &amp; NATURE'; ?></div>
                <h2 class="about-section-title"><?php echo $current_lang==='id'?'Keajaiban yang Menjadikan Bali Istimewa':'Wonders That Make Bali Exceptional'; ?></h2>
                <p class="about-section-sub"><?php echo $current_lang==='id'?'Dari tradisi sakral hingga keindahan alam liar yang memukau — inilah enam pilar keistimewaan Bali.':'From sacred traditions to breathtaking wilderness — these are the six pillars that make Bali extraordinary.'; ?></p>
            </div>
            <div class="culture-grid reveal-on-scroll">
                <?php
                $cultures = [
                    ['id'=>'Tari Tradisional','en'=>'Traditional Dance','desc_id'=>'Tari Kecak, Legong, Barong, dan Pendet adalah ekspresi seni sakral Bali yang membawa cerita rakyat dan spiritualitas ke atas panggung dengan kostum megah dan gerakan penuh makna.','desc_en'=>'Kecak, Legong, Barong, and Pendet dances are sacred artistic expressions of Bali, bringing folklore and spirituality to stage through majestic costumes and deeply meaningful movements.','icon'=>'fa-masks-theater','img'=>'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcRapyLo13jmjfqLWUvWnirZ61GCFrwuYJnu1_hxMwz5XQ&s=10'],
                    ['id'=>'Pura & Spiritualitas','en'=>'Temples & Spirituality','desc_id'=>'Bali memiliki lebih dari 20.000 pura, dari Pura Besakih yang megah di kaki Gunung Agung hingga pura kecil di setiap sudut desa — semua menjadi saksi iman yang hidup.','desc_en'=>'Bali is home to over 20,000 temples, from the majestic Besakih at the foot of Mount Agung to small shrines in every village corner — all witnesses to a living faith.','icon'=>'fa-place-of-worship','img'=>'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQ0_FkbRtc8ZzOz28eCbAUvBXVXETSr59SJ-a2nfiXYfYkg7C1lWHuiq8k&s=10'],
                    ['id'=>'Sawah Terasering Subak','en'=>'Subak Rice Terraces','desc_id'=>'Sistem irigasi Subak yang diwariskan selama lebih dari 1.000 tahun telah diakui UNESCO sebagai Warisan Budaya Dunia yang mencerminkan filosofi Tri Hita Karana.','desc_en'=>'The Subak irrigation system, inherited for over 1,000 years, is recognized by UNESCO as a World Cultural Heritage reflecting the philosophy of Tri Hita Karana.','icon'=>'fa-seedling','img'=>'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS61kXheA76Ohnfq3QUyV14ddp9AhD6nDa1UJGQ2tY8ZFFsBc4F3aNxhnlP&s=10'],
                    ['id'=>'Pantai Eksotis','en'=>'Exotic Beaches','desc_id'=>'Dari Pantai Kuta yang legendaris, Seminyak yang mewah, hingga Pantai Kelingking di Nusa Penida yang dramatis — Bali menawarkan pantai untuk setiap jiwa petualang.','desc_en'=>'From the legendary Kuta Beach, luxurious Seminyak, to the dramatic Kelingking Beach in Nusa Penida — Bali offers a beach for every adventurous soul.','icon'=>'fa-umbrella-beach','img'=>'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=600&q=80'],
                    ['id'=>'Seni & Kerajinan','en'=>'Arts & Crafts','desc_id'=>'Ubud adalah pusat seni Bali dengan galeri ukiran kayu, lukisan tradisional, perak, tenun, dan berbagai kerajinan tangan dari para pengrajin berbakat lintas generasi.','desc_en'=>'Ubud is Bali\'s arts hub with galleries of wood carvings, traditional paintings, silverwork, weaving, and crafts created by talented artisans across generations.','icon'=>'fa-palette','img'=>'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR1XmEMiAG9Y4laZ1UDNyrUb1bbssMy4yLHPAQcZCH1racuZ2t16eaR7PE&s=10'],
                    ['id'=>'Kuliner Khas','en'=>'Local Culinary','desc_id'=>'Babi Guling, Bebek Betutu, Lawar, Nasi Jinggo, dan Sate Lilit adalah cita rasa autentik Bali menggunakan rempah pilihan dan dimasak dengan teknik tradisional turun-temurun.','desc_en'=>'Babi Guling, Bebek Betutu, Lawar, Nasi Jinggo, and Sate Lilit are authentic Balinese flavors using selected spices and cooked with generations-old traditional techniques.','icon'=>'fa-utensils','img'=>'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcQMCfygvz6RFPGRvTCOPEK0qIN9oktJKSvMHfzzBUt6cerLYCZxFPrzgl5A&s=10'],
                ];
                foreach ($cultures as $c):
                ?>
                <div class="culture-card">
                    <div class="culture-card-bg" style="background-image:url('<?php echo $c['img']; ?>');"></div>
                    <div class="culture-card-overlay"></div>
                    <div class="culture-card-body">
                        <div class="culture-card-icon"><i class="fa-solid <?php echo $c['icon']; ?>"></i></div>
                        <div class="culture-card-title"><?php echo $current_lang==='id'?$c['id']:$c['en']; ?></div>
                        <div class="culture-card-desc"><?php echo $current_lang==='id'?$c['desc_id']:$c['desc_en']; ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Regencies -->
            <div class="regency-showcase reveal-on-scroll">
                <div class="section-tag"><i class="fa-solid fa-map-location-dot"></i> <?php echo $current_lang==='id'?'9 KABUPATEN &amp; KOTA':'9 REGENCIES &amp; CITY'; ?></div>
                <h2 class="about-section-title"><?php echo $current_lang==='id'?'Jelajahi Seluruh Penjuru Bali':'Explore Every Corner of Bali'; ?></h2>
                <p class="about-section-sub"><?php echo $current_lang==='id'?'Setiap kabupaten di Bali memiliki keunikan dan pesona tersendiri yang tak tertandingi.':'Each regency in Bali has its own unique charm and unparalleled allure.'; ?></p>
                <div class="regency-cards-grid">
                    <?php
                    $regencies = [
                        ['name'=>'Kabupaten Badung','name_en'=>'Badung Regency','icon'=>'fa-umbrella-beach','tag'=>'Pusat Wisata','tag_en'=>'Tourism Hub','desc_id'=>'Jantung pariwisata Bali dengan Seminyak, Kuta, Nusa Dua, dan Uluwatu. Menawarkan resort mewah, pantai biru, serta kehidupan malam yang semarak.','desc_en'=>'The heart of Bali tourism with Seminyak, Kuta, Nusa Dua, and Uluwatu. Offering luxury resorts, blue beaches, and vibrant nightlife.','hl_id'=>['Pantai Kuta','Uluwatu Temple','GWK Cultural Park','Seminyak'],'hl_en'=>['Kuta Beach','Uluwatu Temple','GWK Cultural Park','Seminyak']],
                        ['name'=>'Kabupaten Gianyar','name_en'=>'Gianyar Regency','icon'=>'fa-palette','tag'=>'Pusat Seni','tag_en'=>'Arts Capital','desc_id'=>'Rumah bagi Ubud — ibu kota seni dan budaya Bali. Penuh galeri seni, sawah terasering Tegallalang, pertunjukan tari, dan museum kelas dunia.','desc_en'=>'Home to Ubud — Bali\'s arts and culture capital. Full of art galleries, Tegallalang rice terraces, dance performances, and world-class museums.','hl_id'=>['Sacred Monkey Forest','Tegallalang','Museum Puri Lukisan','Goa Gajah'],'hl_en'=>['Sacred Monkey Forest','Tegallalang','Museum Puri Lukisan','Goa Gajah']],
                        ['name'=>'Kabupaten Tabanan','name_en'=>'Tabanan Regency','icon'=>'fa-mountain-sun','tag'=>'Alam & Sawah','tag_en'=>'Nature & Fields','desc_id'=>'Dikenal dengan sawah hijau yang membentang luas dan Pura Tanah Lot yang ikonik. Wilayah ini menyimpan pesona alam Bali yang paling autentik.','desc_en'=>'Known for its vast green rice fields and the iconic Tanah Lot temple. This region holds Bali\'s most authentic natural charm.','hl_id'=>['Tanah Lot','Ulun Danu Beratan','Jatiluwih','Alas Kedaton'],'hl_en'=>['Tanah Lot','Ulun Danu Beratan','Jatiluwih','Alas Kedaton']],
                        ['name'=>'Kabupaten Buleleng','name_en'=>'Buleleng Regency','icon'=>'fa-fish','tag'=>'Wisata Utara','tag_en'=>'North Bali','desc_id'=>'Singaraja dan kawasan utara Bali menawarkan pantai hitam yang unik, air panas Banjar, Danau Tamblingan, serta sejarah Kerajaan Buleleng yang kaya.','desc_en'=>'Singaraja and northern Bali offer unique black sand beaches, Banjar hot springs, Lake Tamblingan, and the rich history of the Buleleng Kingdom.','hl_id'=>['Pantai Lovina','Air Panas Banjar','Danau Tamblingan','Air Terjun Gitgit'],'hl_en'=>['Lovina Beach','Banjar Hot Springs','Tamblingan Lake','Gitgit Waterfall']],
                        ['name'=>'Kabupaten Karangasem','name_en'=>'Karangasem Regency','icon'=>'fa-volcano','tag'=>'Gunung Agung','tag_en'=>'Mount Agung','desc_id'=>'Berdiri megah Gunung Agung, puncak tertinggi Bali yang sakral. Kawasan ini juga memiliki Tirta Gangga, Taman Ujung, dan pantai tersembunyi yang menakjubkan.','desc_en'=>'Home to the majestic Mount Agung, Bali\'s highest and most sacred peak. Also features Tirta Gangga, Taman Ujung, and stunning hidden beaches.','hl_id'=>['Gunung Agung','Tirta Gangga','Taman Ujung','Pantai Amed'],'hl_en'=>['Mount Agung','Tirta Gangga','Taman Ujung','Amed Beach']],
                        ['name'=>'Kabupaten Klungkung','name_en'=>'Klungkung Regency','icon'=>'fa-landmark','tag'=>'Warisan Budaya','tag_en'=>'Heritage','desc_id'=>'Ibukota bersejarah Bali dengan Kertha Gosa yang terkenal. Sebagai pintu gerbang ke Nusa Penida, tempat Pantai Kelingking yang dramatis berdiri.','desc_en'=>'Bali\'s historic capital with the famous Kertha Gosa. Gateway to Nusa Penida, home of the dramatic Kelingking Beach.','hl_id'=>['Pantai Kelingking','Kertha Gosa','Nusa Lembongan','Crystal Bay'],'hl_en'=>['Kelingking Beach','Kertha Gosa','Nusa Lembongan','Crystal Bay']],
                        ['name'=>'Kabupaten Bangli','name_en'=>'Bangli Regency','icon'=>'fa-water','tag'=>'Danau Batur','tag_en'=>'Lake Batur','desc_id'=>'Satu-satunya kabupaten di Bali yang tidak berbatasan langsung dengan laut. Menawarkan kaldera megah Gunung Batur, Danau Batur, dan Desa Kintamani yang sejuk.','desc_en'=>'The only regency in Bali without direct sea access. Offering the majestic caldera of Mount Batur, Lake Batur, and the cool Kintamani village.','hl_id'=>['Gunung Batur','Danau Batur','Kintamani','Penelokan'],'hl_en'=>['Mount Batur','Lake Batur','Kintamani','Penelokan Viewpoint']],
                        ['name'=>'Kabupaten Jembrana','name_en'=>'Jembrana Regency','icon'=>'fa-tree','tag'=>'Alam Tersembunyi','tag_en'=>'Hidden Nature','desc_id'=>'Surga tersembunyi di ujung barat Bali. Pantai Medewi yang tenang, serta Taman Nasional Bali Barat yang masih alami menanti Anda di sini.','desc_en'=>'A hidden paradise on Bali\'s western tip. Serene Medewi Beach and the pristine West Bali National Park await you here.','hl_id'=>['TN Bali Barat','Pantai Medewi','Pura Rambut Siwi','Gilimanuk'],'hl_en'=>['West Bali NP','Medewi Beach','Rambut Siwi Temple','Gilimanuk']],
                        ['name'=>'Kota Denpasar','name_en'=>'Denpasar City','icon'=>'fa-city','tag'=>'Ibu Kota Bali','tag_en'=>'Bali Capital','desc_id'=>'Pusat pemerintahan, bisnis, dan pendidikan Bali. Kota yang dinamis dengan Pasar Badung, Museum Bali, Pura Jagatnatha, serta kuliner tradisional yang menggoda.','desc_en'=>'Bali\'s center of government, business, and education. A dynamic city with Badung Market, Bali Museum, Jagatnatha Temple, and tempting traditional cuisine.','hl_id'=>['Museum Bali','Pasar Badung','Pura Jagatnatha','Pantai Sanur'],'hl_en'=>['Bali Museum','Badung Market','Jagatnatha Temple','Sanur Beach']],
                    ];
                    foreach ($regencies as $reg):
                    $hl = $current_lang==='id' ? $reg['hl_id'] : $reg['hl_en'];
                    ?>
                    <div class="regency-info-card">
                        <div class="reg-header">
                            <div class="reg-icon"><i class="fa-solid <?php echo $reg['icon']; ?>"></i></div>
                            <div>
                                <div class="reg-name"><?php echo $current_lang==='id'?$reg['name']:$reg['name_en']; ?></div>
                                <div class="reg-tagline"><?php echo $current_lang==='id'?$reg['tag']:$reg['tag_en']; ?></div>
                            </div>
                        </div>
                        <p class="reg-desc"><?php echo $current_lang==='id'?$reg['desc_id']:$reg['desc_en']; ?></p>
                        <div class="reg-highlights">
                            <?php foreach($hl as $h): ?><span class="reg-highlight-tag"><?php echo $h; ?></span><?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Facts -->
            <div class="facts-section reveal-on-scroll">
                <div class="section-tag" style="color:var(--primary);"><i class="fa-solid fa-circle-info"></i> <?php echo $current_lang==='id'?'FAKTA MENARIK':'FASCINATING FACTS'; ?></div>
                <h2 class="about-section-title" style="color:#fff;margin-bottom:36px;"><?php echo $current_lang==='id'?'Hal yang Membuat Bali Unik di Dunia':'What Makes Bali Unique in the World'; ?></h2>
                <div class="facts-grid">
                    <?php
                    $facts = [
                        ['icon'=>'fa-calendar-days','ti'=>'Dua Sistem Kalender Unik','te'=>'Two Unique Calendar Systems','di'=>'Bali menggunakan dua kalender tradisional: Saka (365 hari) dan Pawukon (210 hari), yang menentukan jadwal upacara adat sepanjang tahun.','de'=>'Bali uses two traditional calendars: Saka (365 days) and Pawukon (210 days), which determine the schedule of traditional ceremonies throughout the year.'],
                        ['icon'=>'fa-om','ti'=>'Satu-satunya Provinsi Hindu di Indonesia','te'=>'Only Hindu-Majority Province in Indonesia','di'=>'Sekitar 85% penduduk Bali menganut Hindu Dharma, menjadikan Bali sebagai satu-satunya provinsi di Indonesia dengan mayoritas pemeluk Hindu.','de'=>'About 85% of Bali\'s population practices Hindu Dharma, making Bali the only province in Indonesia with a Hindu majority.'],
                        ['icon'=>'fa-award','ti'=>'Warisan Budaya UNESCO','te'=>'UNESCO Cultural Heritage','di'=>'Sistem irigasi Subak dan Tari Kecak telah diakui UNESCO sebagai Warisan Budaya Tak Benda yang patut dilestarikan oleh seluruh umat manusia.','de'=>'The Subak irrigation system and Kecak dance have been recognized by UNESCO as Intangible Cultural Heritage worthy of preservation by all of humanity.'],
                        ['icon'=>'fa-volcano','ti'=>'Empat Gunung Berapi Aktif','te'=>'Four Active Volcanoes','di'=>'Bali memiliki empat gunung berapi, dengan Gunung Agung (3.142 m) sebagai yang tertinggi dan paling sakral — dianggap sebagai rumah para dewa.','de'=>'Bali has four volcanoes, with Mount Agung (3,142 m) being the highest and most sacred — considered the home of the gods by the Balinese people.'],
                        ['icon'=>'fa-hands-praying','ti'=>'Upacara Nyepi yang Unik','te'=>'The Unique Nyepi Ceremony','di'=>'Nyepi adalah Tahun Baru Saka Bali di mana seluruh pulau benar-benar diam — tidak ada kegiatan, listrik dimatikan, dan bandara internasional tutup 24 jam penuh.','de'=>'Nyepi is Bali\'s Saka New Year where the entire island goes completely silent — no activities, electricity is cut, and the international airport closes for a full 24 hours.'],
                        ['icon'=>'fa-music','ti'=>'Gamelan — Orkestra Tradisional','te'=>'Gamelan — Traditional Orchestra','di'=>'Gamelan Bali adalah ansambel musik tradisional yang mengiringi seluruh upacara sakral. Setiap gamelan dianggap memiliki jiwa dan diperlakukan dengan penuh penghormatan.','de'=>'Balinese Gamelan is a traditional musical ensemble accompanying all sacred ceremonies. Each gamelan is considered to have a soul and is treated with full reverence.'],
                        ['icon'=>'fa-leaf','ti'=>'Tri Hita Karana — Filsafat Hidup','te'=>'Tri Hita Karana — Philosophy of Life','di'=>'Filosofi Tri Hita Karana (keseimbangan manusia dengan Tuhan, sesama, dan alam) menjadi landasan seluruh sendi kehidupan masyarakat Bali.','de'=>'The philosophy of Tri Hita Karana (harmony between humans with God, fellow humans, and nature) is the foundation of all aspects of Balinese life.'],
                        ['icon'=>'fa-star','ti'=>'Penghargaan Wisata Terbaik Dunia','te'=>'World\'s Best Tourism Awards','di'=>'Bali telah berulang kali dinobatkan sebagai destinasi wisata terbaik dunia oleh TripAdvisor Travelers\' Choice dan World Travel Awards.','de'=>'Bali has repeatedly been named the world\'s best tourist destination by TripAdvisor Travelers\' Choice and World Travel Awards.'],
                    ];
                    foreach ($facts as $f):
                    ?>
                    <div class="fact-item">
                        <div class="fact-icon"><i class="fa-solid <?php echo $f['icon']; ?>"></i></div>
                        <div class="fact-body">
                            <h4><?php echo $current_lang==='id'?$f['ti']:$f['te']; ?></h4>
                            <p><?php echo $current_lang==='id'?$f['di']:$f['de']; ?></p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Travel Tips -->
            <div class="reveal-on-scroll" style="margin-bottom:40px;">
                <div class="section-tag"><i class="fa-solid fa-lightbulb"></i> <?php echo $current_lang==='id'?'TIPS PERJALANAN':'TRAVEL TIPS'; ?></div>
                <h2 class="about-section-title"><?php echo $current_lang==='id'?'Panduan Sebelum Mengunjungi Bali':'Guide Before Visiting Bali'; ?></h2>
                <p class="about-section-sub"><?php echo $current_lang==='id'?'Persiapkan perjalanan impian Anda dengan tips berharga dari para pelancong berpengalaman.':'Prepare your dream trip with invaluable tips from experienced travelers.'; ?></p>
            </div>
            <div class="tips-grid reveal-on-scroll">
                <?php
                $tips = [
                    ['ti'=>'Pilih Waktu Kunjungan yang Tepat','te'=>'Choose the Right Time to Visit','di'=>'Musim kering (April–Oktober) adalah waktu terbaik untuk mengunjungi Bali. Hindari bulan puncak (Juli–Agustus) jika ingin suasana yang lebih tenang dan harga terjangkau.','de'=>'The dry season (April–October) is the best time to visit Bali. Avoid peak months (July–August) if you want a quieter atmosphere and more affordable prices.'],
                    ['ti'=>'Hormati Adat dan Tradisi Lokal','te'=>'Respect Local Customs and Traditions','di'=>'Selalu kenakan sarung saat memasuki area pura. Jangan melewati sesajen di jalan, dan ikuti aturan setempat selama upacara berlangsung.','de'=>'Always wear a sarong when entering temple areas. Do not step over offerings on the road, and follow local rules during ceremonies.'],
                    ['ti'=>'Gunakan Transportasi Lokal','te'=>'Use Local Transportation','di'=>'Sewa motor atau gunakan layanan ojek online (Gojek/Grab) untuk mobilitas yang fleksibel. Pastikan Anda memiliki SIM Internasional dan selalu gunakan helm.','de'=>'Rent a motorbike or use online ride-hailing (Gojek/Grab) for flexible mobility. Ensure you have an International License if driving, and always wear a helmet.'],
                    ['ti'=>'Tukar Uang di Tempat Resmi','te'=>'Exchange Money at Official Outlets','di'=>'Gunakan money changer resmi atau ATM bank berlisensi. Hindari penukaran uang di tempat tidak resmi yang menawarkan kurs menggiurkan.','de'=>'Use official money changers or licensed bank ATMs. Avoid exchanging money at unofficial places offering enticing rates — the risk of fraud is very high.'],
                    ['ti'=>'Cicipi Kuliner Lokal Autentik','te'=>'Try Authentic Local Cuisine','di'=>'Jangan lewatkan Babi Guling, Bebek Betutu, dan Lawar. Kunjungi warung dan pasar lokal untuk pengalaman kuliner yang sesungguhnya dengan harga sangat terjangkau.','de'=>'Don\'t miss Babi Guling, Bebek Betutu, and Lawar. Visit local warungs and markets for a genuine culinary experience at very affordable prices.'],
                    ['ti'=>'Jaga Lingkungan Bali','te'=>'Protect Bali\'s Environment','di'=>'Bali berjuang melawan sampah plastik. Bawa tas belanja sendiri, hindari produk plastik sekali pakai, dan dukung program lingkungan lokal.','de'=>'Bali is fighting plastic pollution. Bring your own shopping bags, avoid single-use plastic, and support local environmental programs.'],
                ];
                foreach($tips as $i => $tip):
                ?>
                <div class="tip-card">
                    <div class="tip-num"><?php echo str_pad($i+1,2,'0',STR_PAD_LEFT); ?></div>
                    <div class="tip-body">
                        <h4><?php echo $current_lang==='id'?$tip['ti']:$tip['te']; ?></h4>
                        <p><?php echo $current_lang==='id'?$tip['di']:$tip['de']; ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- System Info Card -->
            <div class="system-info-section reveal-on-scroll">
                <div class="system-card">
                    <div>
                        <div class="system-badge"><i class="fa-solid fa-microchip"></i> BALIRECOM SYSTEM</div>
                        <h3><?php echo $current_lang==='id'?'Tentang Sistem Rekomendasi Ini':'About This Recommendation System'; ?></h3>
                        <p><?php echo $current_lang==='id'
                            ?'BaliRecom adalah sistem rekomendasi destinasi wisata Bali berbasis kecerdasan buatan yang dikembangkan sebagai proyek skripsi. Sistem ini menganalisis preferensi pengguna dan mencocokkannya dengan ratusan destinasi wisata menggunakan algoritma terdepan.'
                            :'BaliRecom is an AI-powered Bali tourist destination recommendation system developed as a thesis project. It analyzes user preferences and matches them with hundreds of tourist destinations using cutting-edge algorithms.'; ?>
                        </p>
                        <p><?php echo $current_lang==='id'
                            ?'Dataset mencakup lebih dari 700 destinasi wisata dari seluruh 9 kabupaten/kota di Bali, dengan informasi lokasi GPS, rating, kategori, deskripsi, dan foto untuk setiap destinasi.'
                            :'The dataset covers over 700 tourist destinations across all 9 regencies/city in Bali, with GPS location, rating, category, description, and photo information for each destination.'; ?>
                        </p>
                        <div class="method-tags">
                            <span class="method-tag"><i class="fa-solid fa-brain"></i> <?php echo $current_lang==='id'?'Analisis Teks Cerdas':'Smart Text Analysis'; ?></span>
                            <span class="method-tag"><i class="fa-solid fa-bullseye"></i> <?php echo $current_lang==='id'?'Pencocokan Preferensi':'Preference Matching'; ?></span>
                            <span class="method-tag"><i class="fa-solid fa-sliders"></i> <?php echo $current_lang==='id'?'Filter Berbasis Konten':'Content-Based Filtering'; ?></span>
                            <span class="method-tag"><i class="fa-solid fa-database"></i> 700+ <?php echo $current_lang==='id'?'Destinasi':'Destinations'; ?></span>
                        </div>
                    </div>
                    <div class="system-visual">
                        <?php
                        $steps = [
                            ['ti'=>'Isi Kuesioner Preferensi','te'=>'Fill Preference Questionnaire','di'=>'Kuesioner 5 langkah: kategori, suasana, teman, wilayah, prioritas','de'=>'5-step questionnaire: category, atmosphere, companion, region, priority'],
                            ['ti'=>'Analisis Kata Kunci Wisata','te'=>'Travel Keyword Analysis','di'=>'Sistem membaca dan memahami deskripsi setiap destinasi wisata secara otomatis','de'=>'The system automatically reads and understands the description of each tourist destination'],
                            ['ti'=>'Pencocokan Profil Pengguna','te'=>'User Profile Matching','di'=>'Menghitung tingkat kemiripan antara profil pengguna dan setiap destinasi wisata','de'=>'Calculate the similarity level between the user profile and every tourist destination'],
                            ['ti'=>'Hasil Rekomendasi Terbaik','te'=>'Best Recommendation Results','di'=>'Destinasi terbaik ditampilkan berdasarkan tingkat kecocokan tertinggi','de'=>'Best destinations displayed based on the highest compatibility level'],
                        ];
                        foreach($steps as $n => $s):
                        ?>
                        <div class="flow-step">
                            <div class="flow-step-num"><?php echo $n+1; ?></div>
                            <div class="flow-step-text">
                                <h5><?php echo $current_lang==='id'?$s['ti']:$s['te']; ?></h5>
                                <p><?php echo $current_lang==='id'?$s['di']:$s['de']; ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- CTA -->
            <div class="about-cta reveal-on-scroll">
                <h2><?php echo $current_lang==='id'?'Siap Menjelajahi Bali?':'Ready to Explore Bali?'; ?></h2>
                <p><?php echo $current_lang==='id'
                    ?'Gunakan asisten rekomendasi kami untuk menemukan destinasi Bali yang paling cocok dengan keinginan Anda.'
                    :'Use our recommendation assistant to find the Bali destination that best matches your wishes.'; ?>
                </p>
                <div class="cta-buttons">
                    <a href="rekomendasi.php" class="cta-btn-primary">
                        <i class="fa-solid fa-wand-magic-sparkles"></i>
                        <?php echo $current_lang==='id'?'Mulai Kuesioner':'Start Questionnaire'; ?>
                    </a>
                    <a href="destinasi.php" class="cta-btn-outline">
                        <i class="fa-solid fa-map-location-dot"></i>
                        <?php echo $current_lang==='id'?'Jelajahi Destinasi':'Explore Destinations'; ?>
                    </a>
                </div>
            </div>

        </div>
    </main>

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
                <h4><?php echo $current_lang==='id'?'Navigasi':'Navigation'; ?></h4>
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

    <script>
        // Theme toggle
        const themeToggle = document.getElementById('themeToggle');
        const savedTheme = localStorage.getItem('theme') || 'dark';
        if (savedTheme === 'light') {
            document.documentElement.setAttribute('data-theme', 'light');
            themeToggle.innerHTML = '<i class="fa-solid fa-sun"></i>';
        }
        themeToggle.addEventListener('click', () => {
            const isDark = document.documentElement.getAttribute('data-theme') !== 'light';
            document.documentElement.setAttribute('data-theme', isDark ? 'light' : 'dark');
            themeToggle.innerHTML = isDark ? '<i class="fa-solid fa-moon"></i>' : '<i class="fa-solid fa-sun"></i>';
            localStorage.setItem('theme', isDark ? 'light' : 'dark');
        });

        // Mobile menu
        const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
        const navLinks = document.querySelector('.nav-links');
        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', () => {
                navLinks.classList.toggle('open');
                const icon = mobileMenuBtn.querySelector('i');
                icon.className = navLinks.classList.contains('open') ? 'fa-solid fa-xmark' : 'fa-solid fa-bars';
            });
        }

        // Reveal on scroll — with immediate trigger for already-visible elements
        const revealEls = document.querySelectorAll('.reveal-on-scroll');
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (e.isIntersecting) { e.target.classList.add('visible'); observer.unobserve(e.target); }
            });
        }, { threshold: 0.05, rootMargin: '0px 0px -20px 0px' });
        revealEls.forEach(el => observer.observe(el));

        // Fallback: if observer never fires (e.g. hidden page), show all after 800ms
        setTimeout(() => {
            revealEls.forEach(el => el.classList.add('visible'));
        }, 800);

        // Stat number fade-in (no hidden-by-default, just animate in)
        const statNums = document.querySelectorAll('.stat-num');
        statNums.forEach((el, i) => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(10px)';
            el.style.transition = `opacity .5s ease ${i * 0.1}s, transform .5s ease ${i * 0.1}s`;
            setTimeout(() => {
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            }, 300 + i * 120);
        });
    </script>
</body>
</html>

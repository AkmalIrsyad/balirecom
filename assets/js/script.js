document.addEventListener('DOMContentLoaded', () => {
    // State variables
    let map = null;
    let markersGroup = null;
    let currentStep = 1;

    // Theme Toggle
    const themeToggle = document.getElementById('themeToggle');
    const htmlElement = document.documentElement;
    const themeIcon = themeToggle.querySelector('i');
    
    const savedTheme = localStorage.getItem('theme') || 'light';
    setTheme(savedTheme);

    themeToggle.addEventListener('click', () => {
        const currentTheme = htmlElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        setTheme(newTheme);
    });

    function setTheme(theme) {
        if (theme === 'dark') {
            htmlElement.setAttribute('data-theme', 'dark');
            themeIcon.classList.remove('fa-moon');
            themeIcon.classList.add('fa-sun');
            localStorage.setItem('theme', 'dark');
            updateMapTiles('dark');
        } else {
            htmlElement.removeAttribute('data-theme');
            themeIcon.classList.remove('fa-sun');
            themeIcon.classList.add('fa-moon');
            localStorage.setItem('theme', 'light');
            updateMapTiles('light');
        }
    }

    // Navbar Scroll Effect
    const navbar = document.querySelector('.navbar');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            navbar.style.boxShadow = 'var(--shadow-md)';
            navbar.style.padding = '5px 0';
        } else {
            navbar.style.boxShadow = 'none';
            navbar.style.padding = '0';
        }
    });

    // Mobile Menu Toggle
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const navLinks = document.querySelector('.nav-links');
    mobileMenuBtn.addEventListener('click', () => {
        const icon = mobileMenuBtn.querySelector('i');
        navLinks.classList.toggle('active');
        if (navLinks.classList.contains('active')) {
            navLinks.style.display = 'flex';
            navLinks.style.flexDirection = 'column';
            navLinks.style.position = 'absolute';
            navLinks.style.top = '80px';
            navLinks.style.left = '0';
            navLinks.style.width = '100%';
            navLinks.style.background = 'var(--card-bg)';
            navLinks.style.padding = '20px';
            navLinks.style.boxShadow = 'var(--shadow-md)';
            icon.classList.remove('fa-bars');
            icon.classList.add('fa-xmark');
        } else {
            navLinks.style.display = '';
            icon.classList.remove('fa-xmark');
            icon.classList.add('fa-bars');
        }
    });

    // Setup modal & card button events for all pages
    setupModal();
    attachCardEvents();

    // Initialize with data passed from PHP
    if (typeof wisatasRawData !== 'undefined' && Array.isArray(wisatasRawData)) {
        initializeData(wisatasRawData);
    }

    function initializeData(data) {
        initMap(data);
        setupWizard();
    }

    // Hero Background Slideshow
    (function setupHeroSlideshow() {
        const slides = document.querySelectorAll(".hero-slide");
        const dots = document.querySelectorAll(".slide-dot");
        if (slides.length === 0) return;

        let current = 0;
        let interval = null;
        const DELAY = 5000; // 5 seconds

        function goToSlide(index) {
            slides[current].classList.remove("active");
            if (dots && dots[current]) dots[current].classList.remove("active");
            // Reset scale on outgoing slide for the next entrance
            slides[current].style.transform = "scale(1.05)";
            current = index % slides.length;
            slides[current].classList.add("active");
            if (dots && dots[current]) dots[current].classList.add("active");
        }

        function nextSlide() {
            goToSlide(current + 1);
        }

        function startAutoPlay() {
            if (interval) clearInterval(interval);
            interval = setInterval(nextSlide, DELAY);
        }

        // Allow dot click to jump to specific slide
        dots.forEach(dot => {
            dot.addEventListener("click", () => {
                const idx = parseInt(dot.getAttribute("data-slide"), 10);
                if (idx !== current) {
                    goToSlide(idx);
                    startAutoPlay(); // Reset timer on manual click
                }
            });
        });

        startAutoPlay();
    })();

    // Hash name to get coordinates near Bali
    function getLatLng(place) {
        if (place && typeof place === 'object') {
            const lat = parseFloat(place.latitude);
            const lng = parseFloat(place.longitude);
            if (!isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0) {
                return [lat, lng];
            }
        }
        
        const placeName = typeof place === 'string' ? place : (place ? place['Nama Wisata'] : '');
        let hash = 0;
        for (let i = 0; i < placeName.length; i++) {
            hash = placeName.charCodeAt(i) + ((hash << 5) - hash);
        }
        const latSeed = Math.abs((hash % 1000) / 1000);
        const lngSeed = Math.abs(((hash >> 4) % 1000) / 1000);
        const lat = -8.8 + (latSeed * 0.65);
        const lng = 114.6 + (lngSeed * 1.0);
        return [lat, lng];
    }

    function getCategorySlug(categoryName) {
        if (!categoryName) return "lainnya";
        const lower = categoryName.toLowerCase();
        if (lower.includes("pantai") || lower.includes("laut") || lower.includes("bahari")) return "pantai";
        if (lower.includes("alam") || lower.includes("gunung") || lower.includes("air terjun") || lower.includes("danau") || lower.includes("hutan")) return "alam";
        if (lower.includes("budaya") || lower.includes("desa") || lower.includes("seni") || lower.includes("sejarah") || lower.includes("museum")) return "budaya";
        if (lower.includes("pura") || lower.includes("religi") || lower.includes("candi")) return "pura";
        if (lower.includes("buatan") || lower.includes("taman") || lower.includes("rekreasi") || lower.includes("kebun")) return "buatan";
        return "lainnya";
    }

    function getCategoryImage(category) {
        const slug = getCategorySlug(category);
        if (slug === 'pantai') return 'assets/images/bali_beach.png';
        if (slug === 'pura') return 'assets/images/bali_temple.png';
        return 'assets/images/bali_hero_bg.png';
    }

    function getMarkerColor(category) {
        const slug = getCategorySlug(category);
        switch(slug) {
            case 'pantai': return '#3498db';
            case 'alam': return '#2ecc71';
            case 'budaya': return '#e67e22';
            case 'pura': return '#e74c3c';
            case 'buatan': return '#9b59b6';
            default: return '#f1c40f';
        }
    }

    function attachCardEvents() {
        // Wishlist hearts
        const wishlistBtns = document.querySelectorAll('.wishlist-btn');
        wishlistBtns.forEach(btn => {
            btn.onclick = function(e) {
                e.preventDefault();
                e.stopPropagation();
                const icon = this.querySelector('i');
                if (icon.classList.contains('fa-regular')) {
                    icon.classList.remove('fa-regular');
                    icon.classList.add('fa-solid');
                    icon.style.color = 'var(--primary)';
                } else {
                    icon.classList.remove('fa-solid');
                    icon.classList.add('fa-regular');
                    icon.style.color = 'inherit';
                }
            };
        });

        // Detail modals
        const detailBtns = document.querySelectorAll('.view-detail-btn');
        detailBtns.forEach(btn => {
            btn.onclick = function(e) {
                e.preventDefault();
                const placeData = JSON.parse(this.getAttribute('data-place'));
                openDetailModal(placeData);
            };
        });

        // Rekomendasi Serupa loading overlay
        document.querySelectorAll('a.similar-btn, a[href*="action=similar"]').forEach(link => {
            link.addEventListener('click', function(e) {
                // If it's a regular navigation link, show the loading modal
                const targetUrl = this.getAttribute('href');
                if (!targetUrl || targetUrl.startsWith('#')) return;

                e.preventDefault();

                // Get place name from card or query param
                let placeName = '';
                const card = this.closest('.dest-card');
                if (card) {
                    const titleEl = card.querySelector('h3');
                    if (titleEl) placeName = titleEl.innerText.trim();
                }
                if (!placeName) {
                    const match = targetUrl.match(/similar_to=([^&#]+)/);
                    if (match) placeName = decodeURIComponent(match[1].replace(/\+/g, ' '));
                }

                const isEn = document.documentElement.lang === 'en' || window.location.search.includes('lang=en');

                // Create loading overlay matching the recommendation assistant
                const overlay = document.createElement('div');
                overlay.id = 'similarLoadingOverlay';
                overlay.innerHTML = `
                    <div style="display:flex;flex-direction:column;align-items:center;gap:20px;padding:35px 30px;background:var(--card-bg);border-radius:24px;border:1px solid var(--glass-border);box-shadow:var(--shadow-lg);backdrop-filter:blur(16px);-webkit-backdrop-filter:blur(16px);max-width:440px;width:90%;text-align:center;animation:scaleUp 0.3s ease;">
                        <div style="width:58px;height:58px;border-radius:50%;background:var(--primary-gradient);display:flex;align-items:center;justify-content:center;animation:glowPulse 1.5s infinite;box-shadow:0 8px 25px rgba(13,148,136,0.35);">
                            <i class="fa-solid fa-wand-magic-sparkles" style="color:white;font-size:1.4rem;"></i>
                        </div>
                        <h3 style="font-size:1.2rem;color:var(--text-main);margin:0;font-family:var(--font-primary);">${isEn ? 'Mencari Rekomendasi Serupa...' : 'Sedang Menganalisis Tempat Serupa...'}</h3>
                        <p style="font-size:0.88rem;color:var(--text-muted);margin:0;line-height:1.5;">${isEn ? `Our AI engine is finding destinations with similar ambiance, activities, and highlights to <strong style="color:var(--primary);">${placeName || 'selected destination'}</strong>.` : `Sistem AI sedang menganalisis destinasi dengan karakteristik, suasana, dan daya tarik yang serupa dengan <strong style="color:var(--primary);">${placeName || 'destinasi pilihan Anda'}</strong>.`}</p>
                        <div style="display:flex;gap:6px;margin-top:2px;">
                            <span class="loading-dot" style="width:8px;height:8px;border-radius:50%;background:var(--primary);animation:loadingDots 1.4s infinite ease-in-out;animation-delay:0s;"></span>
                            <span class="loading-dot" style="width:8px;height:8px;border-radius:50%;background:var(--primary);animation:loadingDots 1.4s infinite ease-in-out;animation-delay:0.2s;"></span>
                            <span class="loading-dot" style="width:8px;height:8px;border-radius:50%;background:var(--primary);animation:loadingDots 1.4s infinite ease-in-out;animation-delay:0.4s;"></span>
                        </div>
                    </div>
                `;
                overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(6,10,19,0.65);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);z-index:99999;display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity 0.3s ease;';
                document.body.appendChild(overlay);
                
                requestAnimationFrame(() => {
                    overlay.style.opacity = '1';
                });

                setTimeout(() => {
                    window.location.href = targetUrl;
                }, 450);
            });
        });
    }

    // Set up Preference Wizard Questionnaire transitions (steps 1-5)
    function setupWizard() {
        const wizardProgress = document.getElementById('wizardProgress');
        const steps = document.querySelectorAll('.wizard-step');
        const dots = document.querySelectorAll('.step-dot');
        const prevBtn = document.getElementById('prevBtn');
        const nextBtn = document.getElementById('nextBtn');
        const submitBtn = document.getElementById('submitBtn');
        const wizardForm = document.getElementById('wizardForm');

        if (!wizardForm) return;

        function updateWizardUI() {
            steps.forEach((step, idx) => {
                if (idx + 1 === currentStep) {
                    step.classList.add('active');
                } else {
                    step.classList.remove('active');
                }
            });

            dots.forEach((dot, idx) => {
                const stepNum = idx + 1;
                if (stepNum === currentStep) {
                    dot.classList.add('active');
                    dot.classList.remove('completed');
                } else if (stepNum < currentStep) {
                    dot.classList.remove('active');
                    dot.classList.add('completed');
                } else {
                    dot.classList.remove('active');
                    dot.classList.remove('completed');
                }
            });

            const progressPct = ((currentStep - 1) / (dots.length - 1)) * 100;
            if (wizardProgress) {
                wizardProgress.style.width = `${progressPct}%`;
            }

            if (prevBtn) {
                if (currentStep === 1) {
                    prevBtn.setAttribute('disabled', 'true');
                } else {
                    prevBtn.removeAttribute('disabled');
                }
            }

            if (nextBtn && submitBtn) {
                if (currentStep === steps.length) {
                    nextBtn.style.display = 'none';
                    submitBtn.style.display = 'inline-flex';
                } else {
                    nextBtn.style.display = 'inline-flex';
                    submitBtn.style.display = 'none';
                }
            }
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', () => {
                if (currentStep < steps.length) {
                    currentStep++;
                    updateWizardUI();
                }
            });
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', () => {
                if (currentStep > 1) {
                    currentStep--;
                    updateWizardUI();
                }
            });
        }

        dots.forEach(dot => {
            dot.addEventListener('click', () => {
                const targetStep = parseInt(dot.getAttribute('data-step'));
                if (targetStep < currentStep || targetStep === currentStep + 1) {
                    currentStep = targetStep;
                    updateWizardUI();
                }
            });
        });

        // Show loading state when form submits
        wizardForm.addEventListener('submit', () => {
            // Change button to loading state
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> <span>Menganalisis preferensi Anda...</span>';
                submitBtn.style.opacity = '0.85';
                submitBtn.style.pointerEvents = 'none';
            }

            // Create full-screen loading overlay
            const overlay = document.createElement('div');
            overlay.id = 'wizardLoadingOverlay';
            overlay.innerHTML = `
                <div style="display:flex;flex-direction:column;align-items:center;gap:20px;padding:40px;background:var(--card-bg);border-radius:24px;border:1px solid var(--glass-border);box-shadow:var(--shadow-lg);backdrop-filter:blur(16px);max-width:400px;width:90%;text-align:center;">
                    <div style="width:56px;height:56px;border-radius:50%;background:var(--primary-gradient);display:flex;align-items:center;justify-content:center;animation:glowPulse 1.5s infinite;">
                        <i class="fa-solid fa-wand-magic-sparkles" style="color:white;font-size:1.4rem;"></i>
                    </div>
                    <h3 style="font-size:1.2rem;color:var(--text-main);margin:0;">Sedang Mencari Rekomendasi...</h3>
                    <p style="font-size:0.9rem;color:var(--text-muted);margin:0;line-height:1.5;">Asisten cerdas kami sedang menganalisis preferensi Anda untuk menemukan destinasi terbaik di Bali.</p>
                    <div style="display:flex;gap:6px;">
                        <span class="loading-dot" style="width:8px;height:8px;border-radius:50%;background:var(--primary);animation:loadingDots 1.4s infinite ease-in-out;animation-delay:0s;"></span>
                        <span class="loading-dot" style="width:8px;height:8px;border-radius:50%;background:var(--primary);animation:loadingDots 1.4s infinite ease-in-out;animation-delay:0.2s;"></span>
                        <span class="loading-dot" style="width:8px;height:8px;border-radius:50%;background:var(--primary);animation:loadingDots 1.4s infinite ease-in-out;animation-delay:0.4s;"></span>
                    </div>
                </div>
            `;
            overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(6,10,19,0.6);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);z-index:9999;display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity 0.3s ease;';
            document.body.appendChild(overlay);
            // Trigger fade-in
            requestAnimationFrame(() => { overlay.style.opacity = '1'; });
        });

        // Parse active step from PHP if form was submitted
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('action') && urlParams.get('action') === 'recommend') {
            currentStep = steps.length + 1; // jump past all steps so user can see selection details
            updateWizardUI();
        } else {
            updateWizardUI();
        }

        // Dynamically filter Step 2 Subcategories based on Step 1 Category selection
        function filterSubcategories() {
            const selectedCategoryEl = wizardForm.querySelector('input[name="pref_category"]:checked');
            if (!selectedCategoryEl) return;
            const selectedCategory = selectedCategoryEl.value;

            const subcategoryCards = wizardForm.querySelectorAll('.wizard-step[data-step="2"] .wizard-option-card');
            let anySelectedVisible = false;

            subcategoryCards.forEach(card => {
                const parentCats = card.getAttribute('data-parent-categories') || '';
                const isAll = parentCats === 'all';
                const matches = isAll || selectedCategory === 'all' || parentCats.split(',').includes(selectedCategory);

                if (matches) {
                    card.style.display = 'block';
                    // Check if this card's radio is checked
                    const radio = card.querySelector('input[type="radio"]');
                    if (radio && radio.checked) {
                        anySelectedVisible = true;
                    }
                } else {
                    card.style.display = 'none';
                }
            });

            // If the checked subcategory was hidden, reset it to 'all'
            if (!anySelectedVisible) {
                const allRadio = wizardForm.querySelector('input[name="pref_subcategory"][value="all"]');
                if (allRadio) {
                    allRadio.checked = true;
                }
            }
        }

        // Add event listeners to Category radios in Step 1
        const categoryRadios = wizardForm.querySelectorAll('input[name="pref_category"]');
        categoryRadios.forEach(radio => {
            radio.addEventListener('change', filterSubcategories);
        });

        // Initialize filtering on load
        filterSubcategories();
    }

    // Set up Detailed Modal
    function setupModal() {
        const modal = document.getElementById('detailModal');
        const closeBtn = document.getElementById('closeModalBtn');
        if (!modal || !closeBtn) return;

        closeBtn.addEventListener('click', () => {
            modal.classList.remove('active');
        });

        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('active');
            }
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modal.classList.contains('active')) {
                modal.classList.remove('active');
            }
        });
    }

    function translateDescriptionJs(desc, nama = '', kategori = '', lokasi = '', isEn = false) {
        if (!isEn) return desc || 'Nikmati pesona eksotisme keindahan pulau Bali di lokasi ini. Sangat direkomendasikan untuk healing dan mengisi waktu libur akhir pekan Anda.';
        
        if (!desc || desc.trim() === '') {
            const cleanLoc = lokasi.replace(/Kabupaten |Kota /g, '');
            return `Experience the exotic beauty and serene charm of Bali at ${nama}${cleanLoc ? ` in ${cleanLoc}` : ''}. Highly recommended for relaxation, cultural exploration, and memorable holiday adventures.`;
        }

        const dict = [
            ['merupakan salah satu destinasi wisata religi dan cagar budaya yang sangat disucikan di kawasan', 'is a revered religious and cultural heritage destination located in'],
            ['merupakan destinasi wisata religi dan cagar budaya yang sangat disucikan di kawasan', 'is a sacred religious destination and cultural heritage site in'],
            ['merupakan kawasan hutan konservasi dan suaka alam yang rimbun dan asri di', 'is a lush nature conservation forest and wildlife sanctuary located in'],
            ['merupakan salah satu destinasi wisata pantai paling terkenal di Bali dengan', 'is one of the most famous beach destinations in Bali featuring'],
            ['merupakan salah satu destinasi pantai pesisir eksotis dengan hamparan pasir putih', 'is an exotic coastal beach destination boasting scenic white sands'],
            ['menghadirkan pesona air terjun alami yang jernih dan segar di tengah lembah hijau nan asri di kawasan', 'presents a pristine, refreshing natural waterfall set amidst lush green valleys in'],
            ['merupakan destinasi agrowisata dan perkebunan hijau yang asri di kawasan', 'is a scenic agrotourism and lush green plantation destination in'],
            ['merupakan pusat seni, cagar budaya, dan peninggalan bersejarah yang agung di kawasan', 'is a prominent center of art, cultural heritage, and magnificent history in'],
            ['adalah wahana rekreasi terpadu modern yang menawarkan berbagai wahana seru dan hiburan menyenangkan di kawasan', 'is a modern integrated amusement attraction offering exciting rides and entertainment in'],
            ['Destinasi ini menghadirkan keindahan arsitektur tradisional Bali yang megah, ukiran batu yang artistik, serta suasana spiritual yang tenang di tengah keasrian alam pulau Dewata.', 'This destination showcases majestic traditional Balinese architecture, artistic stone carvings, and a tranquil spiritual atmosphere embraced by the scenic nature of the Island of the Gods.'],
            ['Wisatawan dapat menikmati suasana hutan yang teduh, keanekaragaman flora khas lokal, serta interaksi harmonis dengan alam pulau Dewata.', 'Visitors can enjoy the shaded forest atmosphere, rich local flora diversity, and harmonious interaction with Bali\'s pristine nature.'],
            ['Wisatawan dapat menikmati pemandangan matahari terbenam yang memukau, deburan ombak yang menenangkan, serta keindahan pesisir tropis pulau Bali.', 'Visitors can take in breathtaking sunset vistas, soothing ocean waves, and the exotic tropical coastal charm of Bali.'],
            ['Wisatawan dapat berenang di kolam alami, menikmati udara sejuk pegunungan, serta mengabadikan panorama alam yang memanjakan mata.', 'Visitors can swim in natural pools, breathe fresh mountain air, and capture stunning visual panoramas.'],
            ['Sangat direkomendasikan untuk healing dan mengisi waktu libur akhir pekan Anda bersama keluarga maupun kerabat tercinta.', 'Highly recommended for wellness, healing, and spending memorable weekend getaways with your family and loved ones.'],
            ['Kabupaten Badung, Bali', 'Badung Regency, Bali'],
            ['Kabupaten Gianyar, Bali', 'Gianyar Regency, Bali'],
            ['Kabupaten Tabanan, Bali', 'Tabanan Regency, Bali'],
            ['Kabupaten Buleleng, Bali', 'Buleleng Regency, Bali'],
            ['Kabupaten Bangli, Bali', 'Bangli Regency, Bali'],
            ['Kabupaten Karangasem, Bali', 'Karangasem Regency, Bali'],
            ['Kabupaten Klungkung, Bali', 'Klungkung Regency, Bali'],
            ['Kabupaten Jembrana, Bali', 'Jembrana Regency, Bali'],
            ['Kota Denpasar, Bali', 'Denpasar City, Bali'],
            ['Kabupaten Badung', 'Badung Regency'],
            ['Kabupaten Gianyar', 'Gianyar Regency'],
            ['Kabupaten Tabanan', 'Tabanan Regency'],
            ['Kabupaten Buleleng', 'Buleleng Regency'],
            ['Kabupaten Bangli', 'Bangli Regency'],
            ['Kabupaten Karangasem', 'Karangasem Regency'],
            ['Kabupaten Klungkung', 'Klungkung Regency'],
            ['Kabupaten Jembrana', 'Jembrana Regency'],
            ['Kota Denpasar', 'Denpasar City'],
            ['pulau Dewata', 'Island of the Gods'],
            ['Pulau Dewata', 'Island of the Gods'],
            ['pulau Bali', 'Bali island'],
            ['Pulau Bali', 'Bali island'],
            ['di kawasan', 'in the area of'],
            ['merupakan', 'is'],
            ['adalah', 'is'],
            ['salah satu', 'one of the'],
            ['destinasi wisata', 'tourist destination'],
            ['tempat wisata', 'tourist attraction'],
            ['objek wisata', 'tourist attraction'],
            ['cagar budaya', 'cultural heritage'],
            ['wisata religi', 'spiritual and religious site'],
            ['sangat disucikan', 'deeply revered'],
            ['suasana yang tenang', 'peaceful ambiance'],
            ['keasrian alam', 'natural serenity'],
            ['keindahan alam', 'natural beauty'],
            ['pemandangan alam', 'scenic natural views'],
            ['matahari terbenam', 'sunset'],
            ['matahari terbit', 'sunrise'],
            ['pasir putih', 'white sand'],
            ['air terjun', 'waterfall'],
            ['danau', 'lake'],
            ['gunung', 'mountain'],
            ['pantai', 'beach'],
            ['hutan', 'forest'],
            ['pura', 'temple'],
            ['desa adat', 'traditional village'],
            ['arsitektur tradisional', 'traditional architecture'],
            ['ukiran batu', 'stone carvings'],
            ['menikmati', 'enjoying'],
            ['Wisatawan dapat', 'Visitors can'],
            ['Pengunjung dapat', 'Visitors can'],
            ['Sangat direkomendasikan', 'Highly recommended'],
            ['libur akhir pekan', 'weekend holiday'],
            ['liburan', 'vacation']
        ];

        let result = desc;
        dict.forEach(([indo, eng]) => {
            const regex = new RegExp(indo, 'gi');
            result = result.replace(regex, eng);
        });
        return result;
    }

    function translateCategoryJs(category, isEn = false) {
        if (!isEn) return category;
        const catMap = {
            'Pantai & Pesisir': 'Beach & Coast',
            'Pura & Tempat Religi': 'Temples & Sacred Sites',
            'Air Terjun & Sumber Air': 'Waterfalls & Springs',
            'Hutan & Suaka Alam': 'Forests & Nature Reserves',
            'Gunung & Perbukitan': 'Mountains & Hills',
            'Danau & Waduk': 'Lakes & Reservoirs',
            'Desa Wisata & Budaya': 'Cultural & Heritage Villages',
            'Agrowisata & Perkebunan': 'Agrotourism & Plantations',
            'Taman Rekreasi & Hiburan': 'Theme Parks & Recreation',
            'Seni & Kerajinan': 'Arts & Crafts Centers',
            'Sejarah & Peninggalan': 'Historical Landmarks',
            'Kuliner Tradisional': 'Traditional Culinary',
            'Belanja & Pasar Seni': 'Shopping & Art Markets',
            'Pemandian & Spa': 'Hot Springs & Wellness Spa',
            'Wisata Alam': 'Nature Tourism',
            'Wisata Budaya': 'Cultural Tourism',
            'Wisata Buatan': 'Man-made Tourism',
            'Wisata Rekreasi': 'Recreation Tourism',
            'Wisata Umum': 'General Tourism',
            'Lainnya': 'Other Attractions'
        };
        return catMap[category] || category;
    }

    function translateLocationJs(location, isEn = false) {
        if (!isEn) return location;
        const locMap = {
            'Kabupaten Badung': 'Badung Regency',
            'Kabupaten Gianyar': 'Gianyar Regency',
            'Kabupaten Tabanan': 'Tabanan Regency',
            'Kabupaten Buleleng': 'Buleleng Regency',
            'Kabupaten Bangli': 'Bangli Regency',
            'Kabupaten Karangasem': 'Karangasem Regency',
            'Kabupaten Klungkung': 'Klungkung Regency',
            'Kabupaten Jembrana': 'Jembrana Regency',
            'Kota Denpasar': 'Denpasar City'
        };
        return locMap[location] || location;
    }

    function translateTipsJs(tips, isEn = false) {
        if (!isEn) return tips;
        const tipsMap = {
            'Gunakan tabir surya dan pakaian yang nyaman.': 'Use sunscreen and wear comfortable, breathable clothing.',
            'Bawa pakaian ganti jika ingin bermain air atau berenang.': 'Bring extra clothing if you plan to swim or enjoy water activities.',
            'Gunakan pakaian sopan dan kain sarung saat memasuki area suci pura.': 'Wear modest attire and a traditional sarong when entering sacred temple grounds.',
            'Bawa air minum yang cukup dan kenakan alas kaki yang tidak licin.': 'Bring adequate drinking water and wear slip-resistant footwear.'
        };
        return tipsMap[tips] || tips;
    }

    function openDetailModal(place) {
        const modal = document.getElementById('detailModal');
        const similarSection = document.getElementById('modalSimilarSection');
        if (similarSection) similarSection.style.display = 'none';
        const carouselInner = document.getElementById('modalCarouselInner');
        const carouselIndicators = document.getElementById('modalCarouselIndicators');
        const prevBtn = document.getElementById('modalCarouselPrev');
        const nextBtn = document.getElementById('modalCarouselNext');
        const modalBadge = document.getElementById('modalBadge');
        const modalRating = document.getElementById('modalRating');
        const modalLocation = document.getElementById('modalLocation');
        const modalTitle = document.getElementById('modalTitle');
        const modalMatchScore = document.getElementById('modalMatchScore');
        const modalDescription = document.getElementById('modalDescription');

        const modalDistrict = document.getElementById('modalDistrict');
        const modalTips = document.getElementById('modalTips');
        
        const isEn = (typeof activeLang !== 'undefined' && activeLang === 'en') || document.documentElement.lang === 'en' || window.location.search.includes('lang=en') || (document.cookie.includes('lang=en'));
        const nama = place['Nama Wisata'] || 'Destinasi';
        const kategori = place['Kategori Wisata'] || 'Umum';
        const rating = parseFloat(place['rating'] || 4.5).toFixed(1);
        const lokasi = place['Lokasi'] || 'Bali';
        let deskripsi = place['Deskripsi'] || '';
        
        const translatedDesc = translateDescriptionJs(deskripsi, nama, kategori, lokasi, isEn);
        const translatedCategory = translateCategoryJs(kategori, isEn);
        const translatedLocation = translateLocationJs(lokasi, isEn);
        const tipsText = place['tips'] || 'Gunakan tabir surya dan pakaian yang nyaman.';
        const translatedTips = translateTipsJs(tipsText, isEn);

        // Load deterministic secondary images for 3-photo slide display
        const secondaryImages = [
            "https://images.unsplash.com/photo-1537996194471-e657df975ab4?auto=format&fit=crop&w=800&q=80",
            "https://images.unsplash.com/photo-1555400038-63f5ba517a47?auto=format&fit=crop&w=800&q=80",
            "https://images.unsplash.com/photo-1518548419970-58e3b4079ab2?auto=format&fit=crop&w=800&q=80",
            "https://images.unsplash.com/photo-1573790387438-4da905039392?auto=format&fit=crop&w=800&q=80",
            "https://images.unsplash.com/photo-1604999333679-b86d54738315?auto=format&fit=crop&w=800&q=80",
            "https://images.unsplash.com/photo-1520250497591-112f2f40a3f4?auto=format&fit=crop&w=800&q=80",
            "https://images.unsplash.com/photo-1539367628448-4bc5c9d171c8?auto=format&fit=crop&w=800&q=80",
            "https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80",
            "https://images.unsplash.com/photo-1540959733332-eab4deceeaf7?auto=format&fit=crop&w=800&q=80",
            "https://images.unsplash.com/photo-1508873696983-2df519f0397e?auto=format&fit=crop&w=800&q=80"
        ];
        let nameHash = 0;
        for (let i = 0; i < nama.length; i++) {
            nameHash = nama.charCodeAt(i) + ((nameHash << 5) - nameHash);
        }
        const index1 = Math.abs(nameHash) % secondaryImages.length;
        const index2 = (index1 + 1) % secondaryImages.length;

        const mainImg = place.link_foto ? place.link_foto : getCategoryImage(kategori);
        const imgUrls = [
            mainImg,
            (place.link_foto_2 && place.link_foto_2.trim() !== '') ? place.link_foto_2 : secondaryImages[index1]
        ];

        // Clear and populate carousel slides and dots
        carouselInner.innerHTML = '';
        carouselIndicators.innerHTML = '';
        
        imgUrls.forEach((url, idx) => {
            const slide = document.createElement('div');
            slide.className = 'modal-carousel-slide';
            slide.innerHTML = `
                <div class="carousel-bg-blur" style="background-image: url('${url}');"></div>
                <img src="${url}" alt="${nama} - ${idx + 1}" loading="lazy">
            `;
            carouselInner.appendChild(slide);

            const dot = document.createElement('span');
            dot.className = `modal-carousel-dot${idx === 0 ? ' active' : ''}`;
            dot.dataset.slideIndex = idx;
            carouselIndicators.appendChild(dot);
        });

        let currentSlide = 0;
        const totalSlides = imgUrls.length;

        function goToSlide(slideIdx) {
            currentSlide = slideIdx;
            carouselInner.style.transform = `translateX(-${currentSlide * 100}%)`;
            
            const dots = carouselIndicators.querySelectorAll('.modal-carousel-dot');
            dots.forEach((dot, idx) => {
                if (idx === currentSlide) {
                    dot.classList.add('active');
                } else {
                    dot.classList.remove('active');
                }
            });
        }

        prevBtn.onclick = (e) => {
            e.stopPropagation();
            let nextIdx = currentSlide - 1;
            if (nextIdx < 0) nextIdx = totalSlides - 1;
            goToSlide(nextIdx);
        };

        nextBtn.onclick = (e) => {
            e.stopPropagation();
            let nextIdx = currentSlide + 1;
            if (nextIdx >= totalSlides) nextIdx = 0;
            goToSlide(nextIdx);
        };

        carouselIndicators.querySelectorAll('.modal-carousel-dot').forEach(dot => {
            dot.onclick = (e) => {
                e.stopPropagation();
                const idx = parseInt(dot.dataset.slideIndex);
                goToSlide(idx);
            };
        });

        goToSlide(0);

        modalBadge.innerText = translatedCategory;
        modalRating.innerHTML = `<i class="fa-solid fa-star"></i> ${rating}`;
        if (place.distance !== undefined && place.distance !== null) {
            modalLocation.innerHTML = `<i class="fa-solid fa-map-pin"></i> ${translatedLocation} (<i class="fa-solid fa-location-arrow" style="font-size:0.8rem;"></i> ${parseFloat(place.distance).toFixed(1)} km)`;
        } else {
            modalLocation.innerHTML = `<i class="fa-solid fa-map-pin"></i> ${translatedLocation}`;
        }
        modalTitle.innerText = nama;
        modalDistrict.innerText = translatedLocation;
        if (modalTips) {
            modalTips.innerText = translatedTips;
        }

        if (place.matchScore !== undefined && Math.round(place.matchScore) > 0) {
            modalMatchScore.innerText = isEn ? `${Math.round(place.matchScore)}% Match` : `${Math.round(place.matchScore)}% Cocok`;
            modalMatchScore.style.display = 'inline-block';
        } else {
            modalMatchScore.style.display = 'none';
        }

        modalDescription.innerText = translatedDesc;

        // Option 3: Explanation Box (Content-Based Matching analysis)
        const modalExplanationBox = document.getElementById('modalExplanationBox');
        const modalExplanationText = document.getElementById('modalExplanationText');
        
        if (modalExplanationBox && modalExplanationText) {
            if (place.matchScore !== undefined && place.matchScore > 0) {
                const urlParams = new URLSearchParams(window.location.search);
                const prefCategory = urlParams.get('pref_category') || '';
                const prefAtmosphere = urlParams.get('pref_atmosphere') || '';
                const prefCompanion = urlParams.get('pref_companion') || '';
                
                const textToSearch = `${nama} ${kategori} ${lokasi} ${deskripsi}`.toLowerCase();
                const matchedWords = [];
                
                const prefMapping = {
                    'pantai': ['pantai', 'laut', 'bahari', 'surfing', 'pasir putih'],
                    'alam': ['alam', 'gunung', 'air terjun', 'danau', 'hutan', 'bukit', 'sawah', 'terasering', 'trekking'],
                    'budaya': ['budaya', 'desa', 'seni', 'sejarah', 'museum', 'tarian', 'patung', 'adat', 'tradisional'],
                    'pura': ['pura', 'religi', 'candi', 'spiritual', 'suci'],
                    'buatan': ['buatan', 'taman', 'rekreasi', 'kebun', 'zoo', 'playground', 'club', 'waterpark', 'waterblow'],
                    'tenang': ['tenang', 'sunyi', 'damai', 'asri', 'hijau', 'tersembunyi', 'sejuk', 'pegunungan'],
                    'ramai': ['ramai', 'populer', 'aktif', 'hidup', 'turis', 'kuliner', 'warung', 'club', 'mall'],
                    'edukasi': ['edukasi', 'sejarah', 'museum', 'belajar', 'wawasan', 'purbakala', 'arkeologi', 'informasi'],
                    'solo': ['solo', 'petualangan', 'tenang', 'bebas', 'santai'],
                    'pasangan': ['romantis', 'berdua', 'pasangan', 'sunset', 'indah', 'honeymoon', 'romance'],
                    'keluarga': ['keluarga', 'anak-anak', 'lansia', 'rombongan', 'nyaman', 'aman', 'rekreasi']
                };

                const activePrefs = [];
                if (prefCategory && prefCategory !== 'all') activePrefs.push(prefCategory.toLowerCase());
                if (prefAtmosphere) activePrefs.push(prefAtmosphere.toLowerCase());
                if (prefCompanion) activePrefs.push(prefCompanion.toLowerCase());

                activePrefs.forEach(pref => {
                    const keywords = prefMapping[pref];
                    if (keywords) {
                        keywords.forEach(kw => {
                            if (textToSearch.includes(kw) && !matchedWords.includes(kw)) {
                                matchedWords.push(kw);
                            }
                        });
                    }
                });

                if (matchedWords.length > 0) {
                    const capitalizedWords = matchedWords.map(w => w.charAt(0).toUpperCase() + w.slice(1));
                    modalExplanationText.innerHTML = isEn
                        ? `This destination is recommended because it has a match level of <strong>${Math.round(place.matchScore)}%</strong> based on the Cosine Similarity calculation of your preferences. The most relevant keywords for this destination are:<br><div class="matched-keywords-container">${capitalizedWords.map(w => `<span class="badge-kw"><i class="fa-solid fa-tag"></i> ${w}</span>`).join('')}</div>`
                        : `Destinasi ini direkomendasikan karena memiliki tingkat kecocokan <strong>${Math.round(place.matchScore)}%</strong> berdasarkan perhitungan Cosine Similarity preferensi Anda. Kata kunci yang paling relevan pada destinasi ini adalah:<br><div class="matched-keywords-container">${capitalizedWords.map(w => `<span class="badge-kw"><i class="fa-solid fa-tag"></i> ${w}</span>`).join('')}</div>`;
                } else {
                    modalExplanationText.innerHTML = isEn
                        ? `This destination matches your search profile by <strong>${Math.round(place.matchScore)}%</strong> based on description similarity and tourism category.`
                        : `Destinasi ini cocok dengan profil pencarian Anda sebesar <strong>${Math.round(place.matchScore)}%</strong> berdasarkan kemiripan deskripsi dan kategori pariwisata.`;
                }
                modalExplanationBox.style.display = 'block';
            } else {
                modalExplanationBox.style.display = 'none';
            }
        }

        const slug = getCategorySlug(kategori);
        if (slug === 'pantai') {
            modalTips.innerText = isEn
                ? 'Bring sunglasses, sunscreen, and visit in the afternoon towards sunset.'
                : 'Bawa kacamata hitam, sunscreen, dan berkunjunglah saat sore menjelang matahari terbenam.';
        } else if (slug === 'pura') {
            modalTips.innerText = isEn
                ? 'Must wear Balinese traditional sarong (kamen) to respect the sacredness of the temple, and maintain good manners.'
                : 'Wajib mengenakan sarung adat Bali (kamen) untuk menghormati kesucian pura, serta jaga sopan santun.';
        } else if (slug === 'alam') {
            modalTips.innerText = isEn
                ? 'Use non-slip trekking shoes, prepare a spare raincoat, and visit in the morning.'
                : 'Gunakan sepatu trekking anti slip, siapkan jas hujan cadangan, dan berkunjunglah di pagi hari.';
        } else {
            modalTips.innerText = isEn
                ? 'Prepare your camera as there are many interesting instagramable photo spots here.'
                : 'Siapkan kamera Anda karena banyak sekali spot foto instagramable yang menarik di sini.';
        }

        const viewOnMapBtn = document.getElementById('modalViewOnMapBtn');
        const similarBtn = document.getElementById('modalSimilarBtn');

        // Reset button text & active class when opening modal
        if (similarBtn) {
            similarBtn.innerHTML = `<i class="fa-solid fa-wand-magic-sparkles"></i> ${isEn ? 'Similar Recommendations' : 'Rekomendasi Serupa'}`;
            similarBtn.classList.remove('active');
        }

        viewOnMapBtn.onclick = () => {
            modal.classList.remove('active');
            highlightOnMap(place);
        };

        similarBtn.onclick = () => {
            const similarSection = document.getElementById('modalSimilarSection');
            const similarGrid = document.getElementById('modalSimilarGrid');
            if (!similarSection || !similarGrid) return;
            
            // Toggle visibility if already showing
            if (similarSection.style.display === 'block') {
                similarSection.style.display = 'none';
                similarBtn.innerHTML = `<i class="fa-solid fa-wand-magic-sparkles"></i> ${isEn ? 'Similar Recommendations' : 'Rekomendasi Serupa'}`;
                similarBtn.classList.remove('active');
                return;
            }
            
            // Change button state to active
            similarBtn.innerHTML = `<i class="fa-solid fa-chevron-up"></i> ${isEn ? 'Hide Similar' : 'Sembunyikan Rekomendasi'}`;
            similarBtn.classList.add('active');
            
            similarSection.style.display = 'block';
            similarGrid.innerHTML = `
                <div style="grid-column: 1/-1; text-align: center; padding: 20px; color: var(--text-muted); font-size: 0.9rem;">
                    <i class="fa-solid fa-spinner fa-spin"></i> ${isEn ? 'Finding similar places...' : 'Mencari rekomendasi serupa...'}
                </div>
            `;
            
            const modalCard = modal.querySelector('.modal-card');
            setTimeout(() => {
                modalCard.scrollTo({ top: modalCard.scrollHeight, behavior: 'smooth' });
            }, 100);

            fetch(`index.php?ajax=1&action=similar&similar_to=${encodeURIComponent(nama)}`)
                .then(res => {
                    if (!res.ok) throw new Error('API response error');
                    return res.json();
                })
                .then(data => {
                    similarGrid.innerHTML = '';
                    if (!data || data.length === 0) {
                        similarGrid.innerHTML = `
                            <div style="grid-column: 1/-1; text-align: center; padding: 15px; color: var(--text-muted);">
                                ${isEn ? 'No similar places found.' : 'Tidak ditemukan tempat serupa.'}
                            </div>
                        `;
                        return;
                    }
                    data.forEach(item => {
                        const img = item.link_foto ? item.link_foto : getCategoryImage(item['Kategori Wisata']);
                        const ratingVal = parseFloat(item.rating || 4.0).toFixed(1);
                        const card = document.createElement('div');
                        card.className = 'similar-place-card';
                        card.innerHTML = `
                            <div class="similar-place-img" style="background-image: url('${img}');"></div>
                            <div class="similar-place-info">
                                <h4>${item['Nama Wisata']}</h4>
                                <div class="similar-place-meta">
                                    <span><i class="fa-solid fa-star"></i> ${ratingVal}</span>
                                    <span><i class="fa-solid fa-map-pin"></i> ${item['Lokasi']}</span>
                                </div>
                            </div>
                        `;
                        
                        card.onclick = (e) => {
                            e.stopPropagation();
                            openDetailModal(item);
                            modalCard.scrollTo({ top: 0, behavior: 'smooth' });
                        };
                        
                        similarGrid.appendChild(card);
                    });
                    
                    setTimeout(() => {
                        modalCard.scrollTo({ top: modalCard.scrollHeight, behavior: 'smooth' });
                    }, 200);
                })
                .catch(err => {
                    console.error(err);
                    similarGrid.innerHTML = `
                        <div style="grid-column: 1/-1; text-align: center; padding: 15px; color: #e74c3c;">
                            ${isEn ? 'Failed to load recommendations.' : 'Gagal memuat rekomendasi.'}
                        </div>
                    `;
                });
        };

        modal.classList.add('active');
    }

    function highlightOnMap(place) {
        const name = place['Nama Wisata'] || 'Destinasi';
        const location = place['Lokasi'] || 'Bali';
        const category = place['Kategori Wisata'] || '-';
        const rating = place['rating'] || '-';
        const gmapsLink = place['Google Maps'] || `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(name)}+${encodeURIComponent(location)}`;
        
        document.getElementById('peta').scrollIntoView({ behavior: 'smooth' });
        const [lat, lng] = getLatLng(place);
        if (map) {
            setTimeout(() => {
                map.flyTo([lat, lng], 14, {
                    animate: true,
                    duration: 1.5
                });
                
                const distanceLine = place.distance !== undefined && place.distance !== null 
                    ? `<i class="fa-solid fa-location-arrow" style="color:var(--primary)"></i> ${parseFloat(place.distance).toFixed(1)} km dari Anda<br>` 
                    : '';
                L.popup()
                    .setLatLng([lat, lng])
                    .setContent(`
                        <div class="map-popup-card">
                            <strong style="font-size: 1rem; color: var(--text-main); font-family: 'Outfit', sans-serif;">${name}</strong>
                            <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 4px; line-height: 1.5;">
                                <i class="fa-solid fa-tag" style="color:var(--primary)"></i> ${category}<br>
                                <i class="fa-solid fa-map-pin" style="color:var(--primary)"></i> ${location}<br>
                                ${distanceLine}
                                <i class="fa-solid fa-star" style="color:#FFB100"></i> ${rating} Bintang
                            </div>
                            <a href="${gmapsLink}" target="_blank" style="display:inline-block; margin-top:8px; color:var(--primary); font-weight:bold; text-decoration:none; font-size:0.85rem;"><i class="fa-solid fa-arrow-up-right-from-square"></i> Buka di Google Maps</a>
                        </div>
                    `)
                    .openOn(map);
            }, 600);
        }
    }

    // Initialize Leaflet Map
    function initMap(places) {
        if (!document.getElementById('map')) return;
        
        map = L.map('map', {
            scrollWheelZoom: false
        }).setView([-8.409518, 115.188919], 10); 
        
        map.on('focus', () => { map.scrollWheelZoom.enable(); });
        map.on('blur', () => { map.scrollWheelZoom.disable(); });

        updateMapTiles(savedTheme);

        markersGroup = L.featureGroup().addTo(map);
        updateMapMarkers(places);
    }

    function updateMapTiles(theme) {
        if (!map) return;
        
        map.eachLayer((layer) => {
            if (layer instanceof L.TileLayer) {
                map.removeLayer(layer);
            }
        });

        let tileUrl = 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png';
        let attr = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>';

        if (theme === 'dark') {
            tileUrl = 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png';
            attr = '&copy; OpenStreetMap &copy; CARTO';
        }

        L.tileLayer(tileUrl, {
            attribution: attr,
            maxZoom: 19
        }).addTo(map);
    }

    function updateMapMarkers(placesList) {
        if (!map || !markersGroup) return;
        
        markersGroup.clearLayers();

        // Limit map markers to avoid clutter
        const mapPlaces = placesList.slice(0, 40);

        mapPlaces.forEach(place => {
            const nama = place['Nama Wisata'] || 'Destinasi';
            const kategori = place['Kategori Wisata'] || '-';
            const rating = place['rating'] || '-';
            const lokasi = place['Lokasi'] || 'Bali';
            
            const [lat, lng] = getLatLng(place);
            const color = getMarkerColor(kategori);

            const customIcon = L.divIcon({
                html: `<div style="background-color: ${color}; width: 14px; height: 14px; border-radius: 50%; border: 2px solid white; box-shadow: 0 0 8px rgba(0,0,0,0.3);"></div>`,
                className: 'custom-map-marker',
                iconSize: [14, 14],
                iconAnchor: [7, 7]
            });

            const marker = L.marker([lat, lng], { icon: customIcon });
            
            const isEn = (typeof activeLang !== 'undefined' && activeLang === 'en');
            const gmapsLink = place['Google Maps'] || `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(nama)}+${encodeURIComponent(lokasi)}`;
            const distanceLine = place.distance !== undefined && place.distance !== null 
                ? `<i class="fa-solid fa-location-arrow" style="color:var(--primary)"></i> ${parseFloat(place.distance).toFixed(1)} km dari Anda<br>` 
                : '';
            const popupContent = `
                <div class="map-popup-card">
                    <strong style="font-size: 1rem; color: var(--text-main); font-family: 'Outfit', sans-serif;">${nama}</strong>
                    <div style="font-size: 0.85rem; color: var(--text-muted); margin-top: 4px; line-height: 1.5;">
                        <i class="fa-solid fa-tags" style="color:${color}"></i> ${kategori}<br>
                        <i class="fa-solid fa-map-pin" style="color:var(--primary)"></i> ${lokasi}<br>
                        ${distanceLine}
                        <i class="fa-solid fa-star" style="color:#FFB100"></i> ${rating} ${isEn ? 'Stars' : 'Bintang'}
                    </div>
                    <a href="${gmapsLink}" target="_blank" style="display:inline-block; margin-top:8px; color:var(--primary); font-weight:bold; text-decoration:none; font-size:0.85rem;"><i class="fa-solid fa-arrow-up-right-from-square"></i> ${isEn ? 'Open in Google Maps' : 'Buka di Google Maps'}</a>
                </div>
            `;
            
            marker.bindPopup(popupContent);
            markersGroup.addLayer(marker);
        });

        if (mapPlaces.length > 0) {
            try {
                map.fitBounds(markersGroup.getBounds(), { padding: [30, 30], maxZoom: 12 });
} catch (e) {
                // Ignore bounds error
            }
        }
    }
    // ─── Popular Spotlight Destinations Infinite Loop Carousel ───
    (function initPopularSlider() {
        const track = document.getElementById('popularSliderTrack');
        const dots  = document.querySelectorAll('.popular-dot');
        const prevBtns = [document.getElementById('popularPrev'), document.getElementById('spotlightPrev')].filter(Boolean);
        const nextBtns = [document.getElementById('popularNext'), document.getElementById('spotlightNext')].filter(Boolean);
        if (!track) return;

        const originalSlides = Array.from(track.querySelectorAll('.spotlight-card'));
        const total = originalSlides.length;
        if (total === 0) return;

        // Clone count: prepend and append 4 slides so no empty space is EVER visible
        const CLONES = Math.min(4, total);

        // Prepend clones of the last CLONES slides
        for (let i = total - CLONES; i < total; i++) {
            const clone = originalSlides[i].cloneNode(true);
            clone.classList.add('is-clone');
            track.insertBefore(clone, track.firstChild);
        }

        // Append clones of the first CLONES slides
        for (let i = 0; i < CLONES; i++) {
            const clone = originalSlides[i].cloneNode(true);
            clone.classList.add('is-clone');
            track.appendChild(clone);
        }

        // Bind detail modal clicks to all cards including clones
        track.querySelectorAll('.view-detail-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const placeData = JSON.parse(btn.getAttribute('data-place'));
                openDetailModal(placeData);
            });
        });

        let current = 0; // logical index: 0 to total - 1
        let isTransitioning = false;

        function updateLayout(domPos, smooth = true) {
            const allSlides = track.querySelectorAll('.spotlight-card');
            if (allSlides.length === 0) return;
            const slideWidth = allSlides[0].getBoundingClientRect().width || 300;
            const gap = 20;

            const translation = -(domPos * (slideWidth + gap));
            
            if (smooth) {
                track.style.transition = 'transform 0.52s cubic-bezier(0.25, 1, 0.5, 1)';
            } else {
                track.style.transition = 'none';
            }
            
            track.style.transform = `translateX(${translation}px)`;

            // Logical active slide index
            const activeIndex = ((current % total) + total) % total;

            // Highlight active slide & dots
            allSlides.forEach((slide, i) => {
                slide.classList.toggle('active', i === domPos);
            });

            if (dots && dots.length > 0) {
                dots.forEach((d, i) => d.classList.toggle('active', i === activeIndex));
            }

            // Update Spotlight Counter (01 / 10) & Progress Line
            const currentNumEl = document.getElementById('spotlightCurrentNum');
            const progressFillEl = document.getElementById('spotlightProgressFill');
            if (currentNumEl) {
                currentNumEl.innerText = String(activeIndex + 1).padStart(2, '0');
            }
            if (progressFillEl) {
                progressFillEl.style.width = `${((activeIndex + 1) / total) * 100}%`;
            }
        }

        function goTo(targetIndex) {
            if (isTransitioning) return;

            current = targetIndex;
            let domPos = current + CLONES;
            isTransitioning = true;
            updateLayout(domPos, true);

            setTimeout(() => {
                // If we went past the end, wrap to beginning seamlessly
                if (current >= total) {
                    current = current % total;
                    updateLayout(current + CLONES, false);
                    track.offsetHeight; // trigger reflow
                } else if (current < 0) {
                    current = (current % total + total) % total;
                    updateLayout(current + CLONES, false);
                    track.offsetHeight; // trigger reflow
                }
                isTransitioning = false;
            }, 530);
        }

        prevBtns.forEach(b => {
            b.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                goTo(current - 1);
                resetAutoPlay();
            });
        });

        nextBtns.forEach(b => {
            b.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                goTo(current + 1);
                resetAutoPlay();
            });
        });

        if (dots && dots.length > 0) {
            dots.forEach(d => d.addEventListener('click', () => { 
                goTo(+d.dataset.index); 
                resetAutoPlay(); 
            }));
        }

        // Dynamic Auto-play
        let autoTimeoutId = null;
        const slider = document.getElementById('popularSlider') || track.parentElement;

        function startAutoPlay() {
            clearTimeout(autoTimeoutId);
            autoTimeoutId = setTimeout(() => {
                goTo(current + 1);
                startAutoPlay();
            }, 5500);
        }

        function stopAutoPlay() {
            clearTimeout(autoTimeoutId);
        }

        function resetAutoPlay() {
            stopAutoPlay();
            startAutoPlay();
        }

        // Initialize slide layout at logical index 0 (dom index = CLONES)
        current = 0;
        updateLayout(CLONES, false);
        startAutoPlay();

        if (slider) {
            slider.addEventListener('mouseenter', stopAutoPlay);
            slider.addEventListener('mouseleave', startAutoPlay);

            let touchStartX = 0;
            slider.addEventListener('touchstart', e => {
                touchStartX = e.changedTouches[0].clientX;
            }, { passive: true });

            slider.addEventListener('touchend', e => {
                const diff = touchStartX - e.changedTouches[0].clientX;
                if (Math.abs(diff) > 40) {
                    goTo(current + (diff > 0 ? 1 : -1));
                    resetAutoPlay();
                }
            });

            slider.addEventListener('wheel', e => {
                if (Math.abs(e.deltaX) > 20 || Math.abs(e.deltaY) > 20) {
                    if (e.deltaX > 20 || e.deltaY > 20) {
                        goTo(current + 1);
                    } else if (e.deltaX < -20 || e.deltaY < -20) {
                        goTo(current - 1);
                    }
                    resetAutoPlay();
                }
            }, { passive: true });
        }

        window.addEventListener('resize', () => {
            updateLayout(current + CLONES, false);
        });
    })();

    // Autocomplete / Search Suggestions
    (() => {
        const searchInput = document.getElementById('searchInput');
        const suggestionsDropdown = document.getElementById('searchSuggestions');
        
        if (searchInput && suggestionsDropdown) {
            let wisataList = [];
            
            // Fetch dataset once on page load
            fetch('assets/wisata.json')
                .then(response => response.json())
                .then(data => {
                    wisataList = data;
                })
                .catch(err => console.error('Error loading suggestions:', err));
                
            let highlightedIndex = -1;
            
            searchInput.addEventListener('input', () => {
                const val = searchInput.value.toLowerCase().trim();
                if (!val) {
                    suggestionsDropdown.style.display = 'none';
                    suggestionsDropdown.innerHTML = '';
                    return;
                }
                
                // Filter matches by name, category, or location
                const matches = wisataList.filter(item => {
                    const name = item['Nama Wisata'] || '';
                    const cat = item['Kategori Wisata'] || '';
                    const loc = item['Lokasi'] || '';
                    return name.toLowerCase().includes(val) || 
                           cat.toLowerCase().includes(val) || 
                           loc.toLowerCase().includes(val);
                })
                .sort((a, b) => parseFloat(b.rating || 0) - parseFloat(a.rating || 0))
                .slice(0, 6); // limit to top 6 suggestions
                
                if (matches.length === 0) {
                    suggestionsDropdown.style.display = 'none';
                    suggestionsDropdown.innerHTML = '';
                    return;
                }
                
                highlightedIndex = -1;
                suggestionsDropdown.innerHTML = matches.map((item, idx) => {
                    const fallbackImg = getCategoryImage(item['Kategori Wisata']);
                    const imgUrl = item.link_foto ? item.link_foto : fallbackImg;
                    return `
                        <div class="suggestion-item" data-name="${item['Nama Wisata']}">
                            <div class="suggestion-left">
                                <img src="${imgUrl}" onerror="this.onerror=null; this.src='${fallbackImg}';" alt="${item['Nama Wisata']}" class="suggestion-img">
                                <div class="suggestion-info">
                                    <span class="suggestion-name">${item['Nama Wisata']}</span>
                                    <div class="suggestion-meta">
                                        <span><i class="fa-solid fa-map-pin"></i> ${item['Lokasi']}</span>
                                        <span><i class="fa-solid fa-star" style="color:#fbbf24;"></i> ${parseFloat(item['rating']).toFixed(1)}</span>
                                    </div>
                                </div>
                            </div>
                            <span class="suggestion-badge">${item['Kategori Wisata']}</span>
                        </div>
                    `;
                }).join('');
                
                suggestionsDropdown.style.display = 'block';
                
                // Click event handler for items
                document.querySelectorAll('.suggestion-item').forEach(item => {
                    item.addEventListener('click', () => {
                        searchInput.value = item.getAttribute('data-name');
                        suggestionsDropdown.style.display = 'none';
                        searchInput.focus();
                    });
                });
            });
            
            // Handle Arrow keys and Enter
            searchInput.addEventListener('keydown', (e) => {
                const items = document.querySelectorAll('.suggestion-item');
                if (suggestionsDropdown.style.display === 'none' || items.length === 0) return;
                
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    highlightedIndex = (highlightedIndex + 1) % items.length;
                    updateHighlight(items);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    highlightedIndex = (highlightedIndex - 1 + items.length) % items.length;
                    updateHighlight(items);
                } else if (e.key === 'Enter') {
                    if (highlightedIndex > -1) {
                        e.preventDefault();
                        items[highlightedIndex].click();
                    }
                } else if (e.key === 'Escape') {
                    suggestionsDropdown.style.display = 'none';
                }
            });
            
            function updateHighlight(items) {
                items.forEach((item, idx) => {
                    if (idx === highlightedIndex) {
                        item.classList.add('highlighted');
                        item.scrollIntoView({ block: 'nearest' });
                    } else {
                        item.classList.remove('highlighted');
                    }
                });
            }
            
            // Close dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (e.target !== searchInput && e.target !== suggestionsDropdown && !suggestionsDropdown.contains(e.target)) {
                    suggestionsDropdown.style.display = 'none';
                }
            });
        }
    })();

    // Geolocation Sorting listener
    (() => {
        const sortFilter = document.getElementById('sortFilter');
        if (sortFilter) {
            // Remove the inline onchange if it still exists (precautionary)
            sortFilter.removeAttribute('onchange');
            
            sortFilter.addEventListener('change', (e) => {
                const val = e.target.value;
                const baseUrl = sortFilter.getAttribute('data-baseurl');
                const isRekomendasi = window.location.pathname.includes('rekomendasi.php');
                const targetHash = isRekomendasi ? '#hasil' : '#destinasi';
                const activeLang = document.documentElement.lang || 'id';
                
                if (val === 'jarak') {
                    if (navigator.geolocation) {
                        sortFilter.disabled = true;
                        navigator.geolocation.getCurrentPosition((position) => {
                            const lat = position.coords.latitude;
                            const lng = position.coords.longitude;
                            
                            // Build redirect URL
                            const separator = baseUrl.includes('?') ? '&' : '?';
                            const redirectUrl = `${baseUrl}${separator}sort=jarak&user_lat=${lat}&user_lng=${lng}${targetHash}`;
                            window.location.href = redirectUrl;
                        }, (error) => {
                            let errorMsg = 'Gagal mengakses lokasi Anda. Menggunakan urutan rating sebagai cadangan.';
                            if (activeLang === 'en') {
                                errorMsg = 'Failed to obtain your location. Using star rating sort as a fallback.';
                            }
                            alert(errorMsg);
                            sortFilter.value = 'rating';
                            sortFilter.disabled = false;
                        });
                    } else {
                        let errorMsg = 'Geolokasi tidak didukung oleh browser Anda.';
                        if (activeLang === 'en') {
                            errorMsg = 'Geolocation is not supported by your browser.';
                        }
                        alert(errorMsg);
                        sortFilter.value = 'rating';
                    }
                } else {
                    const separator = baseUrl.includes('?') ? '&' : '?';
                    window.location.href = `${baseUrl}${separator}sort=${val}${targetHash}`;
                }
            });
        }

        const limitFilter = document.getElementById('limitFilter');
        if (limitFilter) {
            limitFilter.addEventListener('change', (e) => {
                const val = e.target.value;
                const baseUrl = limitFilter.getAttribute('data-baseurl');
                const separator = baseUrl.includes('?') ? '&' : '?';
                window.location.href = `${baseUrl}${separator}limit=${val}#destinasi`;
            });
        }
    })();

    // ==========================================
    // SCROLL REVEAL ANIMATION (IntersectionObserver)
    // ==========================================
    const revealElements = document.querySelectorAll('.reveal-on-scroll');
    if (revealElements.length > 0) {
        const revealObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                    revealObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

        revealElements.forEach(el => revealObserver.observe(el));
    }

    // ==========================================
    // CUSTOM INTERACTIVE DROPDOWNS (Hero Search Card)
    // ==========================================
    (() => {
        const dropdownWraps = document.querySelectorAll('.custom-dropdown-wrap');
        if (dropdownWraps.length === 0) return;

        dropdownWraps.forEach(wrap => {
            const trigger = wrap.querySelector('.custom-dropdown-trigger');
            const hiddenInput = wrap.querySelector('input[type="hidden"]');
            const label = wrap.querySelector('.trigger-label');
            const items = wrap.querySelectorAll('.dropdown-item');

            if (!trigger || !hiddenInput || !items.length) return;

            // Toggle dropdown open/close on click
            trigger.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdownWraps.forEach(other => {
                    if (other !== wrap) other.classList.remove('open');
                });
                wrap.classList.toggle('open');
            });

            // Handle option item selection
            items.forEach(item => {
                item.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const val = item.getAttribute('data-value');
                    const textEl = item.querySelector('span');
                    const text = textEl ? textEl.innerText.trim() : item.innerText.trim();

                    // Update hidden input and trigger label text
                    hiddenInput.value = val;
                    if (label) label.innerText = text;

                    // Update active highlight state
                    items.forEach(i => i.classList.remove('active'));
                    item.classList.add('active');

                    // Close dropdown menu
                    wrap.classList.remove('open');
                });
            });
        });

        // Close all dropdowns when clicking outside
        document.addEventListener('click', () => {
            dropdownWraps.forEach(wrap => wrap.classList.remove('open'));
        });
    })();
});

/* ============================================================
   ✨ PREMIUM MICRO-INTERACTION & POLISH SCRIPTS
   ============================================================ */

// ── 1. CURSOR GLOW ─────────────────────────────────────────
(function initCursorGlow() {
    const glow = document.createElement('div');
    glow.className = 'cursor-glow';
    document.body.appendChild(glow);

    let mouseX = 0, mouseY = 0;
    let glowX = 0, glowY = 0;
    let rafId;

    document.addEventListener('mousemove', (e) => {
        mouseX = e.clientX;
        mouseY = e.clientY;
    });

    document.addEventListener('mouseleave', () => {
        glow.style.opacity = '0';
    });

    document.addEventListener('mouseenter', () => {
        glow.style.opacity = '1';
    });

    function animateGlow() {
        glowX += (mouseX - glowX) * 0.1;
        glowY += (mouseY - glowY) * 0.1;
        glow.style.left = glowX + 'px';
        glow.style.top = glowY + 'px';
        rafId = requestAnimationFrame(animateGlow);
    }
    animateGlow();
})();

// ── 2. SCROLL REVEAL ───────────────────────────────────────
(function initScrollReveal() {
    // Apply reveal class to key sections
    const targets = [
        '.section-header',
        '.dest-card',
        '.category-card',
        '.stat-item',
        '.wizard-container',
        '.how-step',
        '.glass-card',
        '.footer-brand',
        '.footer-links',
        '.footer-contact'
    ];

    targets.forEach(sel => {
        document.querySelectorAll(sel).forEach((el, i) => {
            el.classList.add('reveal-on-scroll');
            el.style.transitionDelay = (i * 0.07) + 's';
        });
    });

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

    document.querySelectorAll('.reveal-on-scroll').forEach(el => observer.observe(el));
})();

// ── 3. BUTTON RIPPLE EFFECT ────────────────────────────────
(function initButtonRipple() {
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn');
        if (!btn) return;

        const ripple = document.createElement('span');
        ripple.className = 'ripple';
        const rect = btn.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        ripple.style.cssText = `
            width: ${size}px;
            height: ${size}px;
            left: ${e.clientX - rect.left - size / 2}px;
            top: ${e.clientY - rect.top - size / 2}px;
        `;
        btn.appendChild(ripple);
        ripple.addEventListener('animationend', () => ripple.remove());
    });
})();

// ── 4. ANIMATED COUNTER FOR STATS ─────────────────────────
(function initCounters() {
    const statValues = document.querySelectorAll('.stat-value, .hab-stat strong');

    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            const el = entry.target;
            const text = el.textContent;
            const match = text.match(/([\d,]+)/);
            if (!match) return;

            const rawNum = parseInt(match[1].replace(/,/g, ''));
            const prefix = text.split(match[1])[0];
            const suffix = text.split(match[1])[1] || '';
            const duration = 1500;
            const start = performance.now();

            function animate(now) {
                const elapsed = now - start;
                const progress = Math.min(elapsed / duration, 1);
                const eased = 1 - Math.pow(1 - progress, 3);
                const current = Math.floor(eased * rawNum);
                el.textContent = prefix + current.toLocaleString('id-ID') + suffix;
                if (progress < 1) requestAnimationFrame(animate);
            }
            requestAnimationFrame(animate);
            counterObserver.unobserve(el);
        });
    }, { threshold: 0.5 });

    statValues.forEach(el => counterObserver.observe(el));
})();

// ── 5. HERO SCROLL INDICATOR ───────────────────────────────
(function initScrollIndicator() {
    const heroSection = document.querySelector('.hero-section');
    if (!heroSection) return;

    // Only show if not already added
    if (heroSection.querySelector('.hero-scroll-indicator')) return;

    const indicator = document.createElement('div');
    indicator.className = 'hero-scroll-indicator';
    indicator.innerHTML = `
        <div class="scroll-arrow-box">
            <div class="scroll-dot"></div>
        </div>
        <span>Scroll</span>
    `;
    indicator.addEventListener('click', () => {
        const next = document.querySelector('.destinasi-section, .section, main > section:nth-child(2)');
        if (next) next.scrollIntoView({ behavior: 'smooth' });
    });
    heroSection.appendChild(indicator);

    // Hide after first scroll
    const handleScroll = () => {
        if (window.scrollY > 80) {
            indicator.style.opacity = '0';
            indicator.style.pointerEvents = 'none';
        } else {
            indicator.style.opacity = '';
            indicator.style.pointerEvents = '';
        }
    };
    window.addEventListener('scroll', handleScroll, { passive: true });
})();

// ── 6. PAGE LOAD PROGRESS BAR ─────────────────────────────
(function initProgressBar() {
    const bar = document.createElement('div');
    bar.id = 'page-progress-bar';
    bar.style.cssText = `
        position: fixed;
        top: 0; left: 0;
        height: 3px;
        width: 0%;
        background: linear-gradient(90deg, #0D9488, #14b8a6, #0EA5E9);
        z-index: 99999;
        transition: width 0.2s ease, opacity 0.5s ease;
        border-radius: 0 3px 3px 0;
        box-shadow: 0 0 10px rgba(13, 148, 136, 0.6);
        pointer-events: none;
    `;
    document.body.appendChild(bar);

    // Simulate page load
    let w = 0;
    const step = () => {
        if (w < 85) { w += (100 - w) * 0.08; bar.style.width = w + '%'; setTimeout(step, 60); }
    };
    step();

    window.addEventListener('load', () => {
        bar.style.width = '100%';
        setTimeout(() => { bar.style.opacity = '0'; }, 500);
        setTimeout(() => { bar.remove(); }, 1000);
    });
})();

// ── 7. CARD MICRO-TILT ON MOUSE MOVE ─────────────────────
(function initCardTilt() {
    document.querySelectorAll('.dest-card').forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const cx = rect.left + rect.width / 2;
            const cy = rect.top + rect.height / 2;
            const dx = (e.clientX - cx) / (rect.width / 2);
            const dy = (e.clientY - cy) / (rect.height / 2);
            card.style.transform = `perspective(900px) rotateY(${dx * 4}deg) rotateX(${-dy * 4}deg) translateZ(4px)`;
        });

        card.addEventListener('mouseleave', () => {
            card.style.transform = '';
        });
    });
})();

// ── 8. TYPEWRITER EFFECT FOR HERO TITLE ──────────────────
(function initTypewriter() {
    const span = document.querySelector('.hero-content h1 span.hero-type-word');
    if (!span) return;

    const words = span.getAttribute('data-words');
    if (!words) return;

    const list = words.split(',').map(w => w.trim());
    let wordIdx = 0, charIdx = 0, deleting = false;

    function tick() {
        const word = list[wordIdx];
        if (!deleting) {
            span.textContent = word.slice(0, charIdx + 1);
            charIdx++;
            if (charIdx === word.length) {
                deleting = true;
                setTimeout(tick, 1800);
                return;
            }
        } else {
            span.textContent = word.slice(0, charIdx - 1);
            charIdx--;
            if (charIdx === 0) {
                deleting = false;
                wordIdx = (wordIdx + 1) % list.length;
            }
        }
        setTimeout(tick, deleting ? 60 : 110);
    }
    tick();
})();

// ── 9. FLOATING PARTICLES IN HERO ────────────────────────
(function initHeroParticles() {
    const hero = document.querySelector('.hero-section');
    if (!hero) return;

    const container = document.createElement('div');
    container.style.cssText = `
        position: absolute; inset: 0;
        overflow: hidden; pointer-events: none; z-index: 1;
    `;
    hero.style.position = 'relative';
    hero.appendChild(container);

    for (let i = 0; i < 18; i++) {
        const p = document.createElement('div');
        const size = Math.random() * 80 + 20;
        const left = Math.random() * 100;
        const delay = Math.random() * 12;
        const dur = Math.random() * 12 + 10;
        p.style.cssText = `
            position: absolute;
            left: ${left}%;
            bottom: -120px;
            width: ${size}px;
            height: ${size}px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(13,148,136,${0.04 + Math.random() * 0.08}), transparent 70%);
            animation: floatUp ${dur}s ${delay}s ease-in-out infinite;
            pointer-events: none;
        `;
        container.appendChild(p);
    }

    // Add keyframe if not already added
    if (!document.getElementById('floatUpKF')) {
        const style = document.createElement('style');
        style.id = 'floatUpKF';
        style.textContent = `
            @keyframes floatUp {
                0% { transform: translateY(0) scale(0.8); opacity: 0; }
                10% { opacity: 1; }
                90% { opacity: 0.3; }
                100% { transform: translateY(-110vh) scale(1.2); opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    }
})();

// ── 10. SMOOTH ACTIVE NAV HIGHLIGHT ──────────────────────
(function initActiveNav() {
    const currentPage = window.location.pathname.split('/').pop() || 'index.php';
    document.querySelectorAll('.nav-links a').forEach(link => {
        const href = link.getAttribute('href');
        if (href && href.includes(currentPage)) {
            link.classList.add('active');
        }
    });
})();

// ── 11. FAVORITE / WISHLIST MANAGEMENT ──────────────────────────
(function initWishlistSystem() {
    function getFavorites() {
        try {
            return JSON.parse(localStorage.getItem('balirecom_favorites')) || [];
        } catch (e) {
            return [];
        }
    }

    function saveFavorites(favs) {
        localStorage.setItem('balirecom_favorites', JSON.stringify(favs));
        updateWishlistUI();
    }

    function updateWishlistUI() {
        const favs = getFavorites();
        const badge = document.getElementById('wishlistCountBadge');
        if (badge) {
            badge.textContent = favs.length;
        }

        // Update heart icons on cards
        document.querySelectorAll('.favorite-btn').forEach(btn => {
            const name = btn.getAttribute('data-name');
            const icon = btn.querySelector('i');
            if (favs.includes(name)) {
                btn.classList.add('active');
                if (icon) {
                    icon.className = 'fa-solid fa-heart';
                }
            } else {
                btn.classList.remove('active');
                if (icon) {
                    icon.className = 'fa-regular fa-heart';
                }
            }
        });
    }

    // Toggle favorite item
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.favorite-btn');
        if (btn) {
            e.preventDefault();
            e.stopPropagation();
            const name = btn.getAttribute('data-name');
            let favs = getFavorites();
            if (favs.includes(name)) {
                favs = favs.filter(item => item !== name);
            } else {
                favs.push(name);
            }
            saveFavorites(favs);
        }
    });

    // Wishlist Toggle Filter
    let showingWishlistOnly = false;
    const toggleBtn = document.getElementById('wishlistToggleBtn');
    if (toggleBtn) {
        toggleBtn.addEventListener('click', function() {
            showingWishlistOnly = !showingWishlistOnly;
            toggleBtn.classList.toggle('active', showingWishlistOnly);
            
            const favs = getFavorites();
            document.querySelectorAll('.dest-card').forEach(card => {
                const name = card.getAttribute('data-name');
                if (showingWishlistOnly) {
                    if (favs.includes(name)) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                } else {
                    card.style.display = '';
                }
            });
        });
    }

    // ── 11. BUTTON CLICK RIPPLE ANIMATION ────────────────────────
    document.addEventListener('click', function(e) {
        const btn = e.target.closest('.btn, .hero-search-btn, .hero-actions a, .pref-btn');
        if (!btn) return;

        const rect = btn.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;

        const ripple = document.createElement('span');
        ripple.className = 'btn-ripple';
        ripple.style.width = ripple.style.height = `${size}px`;
        ripple.style.left = `${x}px`;
        ripple.style.top = `${y}px`;

        btn.appendChild(ripple);

        setTimeout(() => {
            ripple.remove();
        }, 600);
    });

    // Init on load
    document.addEventListener('DOMContentLoaded', updateWishlistUI);
    updateWishlistUI();
})();

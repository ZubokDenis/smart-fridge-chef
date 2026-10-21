/**
 * Smart Fridge Chef - Application Logic
 * Clean Standard Architecture (Google Material & Instagram Web)
 * 
 * Key Highlights:
 * - NO default Moscow: strictly gets user's location via geolocation or explicit city input.
 * - Instant recipe & meal plan generation immediately after photo upload.
 * - Client-side Canvas WebP conversion before uploading.
 * - Clean editorial design with zero stickers / emojis.
 * - True meal variability: "Из холодильника" vs "С докупкой" + instant meal swapping.
 */

(function () {
    'use strict';

    // Application State
    const state = {
        products: [],
        recipes: { all: [], ready_now: [], need_more: [] },
        weeklyPlan: [],
        recipesByType: {},
        shoppingList: [],
        groupedShopping: {},
        userLocation: (sfcConfig.defaultLat && sfcConfig.defaultLon)
            ? { lat: parseFloat(sfcConfig.defaultLat), lon: parseFloat(sfcConfig.defaultLon) }
            : null,
        userCityName: sfcConfig.defaultCity || '',
        stores: [],
        recommendation: null,
        activeDayIndex: 0,
        map: null,
        mapMarkers: [],
        userMarker: null,
        aiStatus: null,
        currentSwapSlot: null, // { dayIndex, slotKey, mealType }
    };

    // DOM Elements Cache
    let dom = {};

    function initDOM() {
        dom = {
            tabs: document.querySelectorAll('.sfc-nav-item'),
            views: document.querySelectorAll('.sfc-view'),
            badgeProducts: document.getElementById('sfc-badge-products-count'),
            badgeRecipes: document.getElementById('sfc-badge-recipes-count'),
            badgeShopping: document.getElementById('sfc-badge-shopping-count'),
            aiStatusText: document.getElementById('sfc-ai-status-text'),

            // Tab 1: Fridge
            btnModePhoto: document.getElementById('btn-mode-photo'),
            btnModeText: document.getElementById('btn-mode-text'),
            photoArea: document.getElementById('sfc-photo-area'),
            textArea: document.getElementById('sfc-text-area'),
            fileInput: document.getElementById('sfc-file-input'),
            dropzone: document.getElementById('sfc-dropzone'),
            btnChooseFile: document.getElementById('sfc-btn-choose-file'),
            btnCamera: document.getElementById('sfc-btn-camera'),
            previewWrapper: document.getElementById('sfc-image-preview-wrapper'),
            previewImg: document.getElementById('sfc-image-preview'),
            scanStatusText: document.getElementById('sfc-scan-status-text'),
            btnRemovePhoto: document.getElementById('sfc-btn-remove-photo'),
            textProducts: document.getElementById('sfc-text-products'),
            btnParseText: document.getElementById('sfc-btn-parse-text'),
            productsEmpty: document.getElementById('sfc-products-empty'),
            productsListWrapper: document.getElementById('sfc-products-list-wrapper'),
            productsTbody: document.getElementById('sfc-products-tbody'),
            btnAddManualProduct: document.getElementById('sfc-btn-add-manual-product'),
            btnStartCook: document.getElementById('sfc-btn-start-cook'),
            presetButtons: document.querySelectorAll('.sfc-btn-subtle'),
            btnDemo: document.getElementById('sfc-btn-demo'),
            btnReset: document.getElementById('sfc-btn-reset'),

            // Tab 2: Recipes
            recipesContainer: document.getElementById('sfc-recipes-container'),
            recipeFilters: document.querySelectorAll('.sfc-filter-btn'),

            // Tab 3: Meal Plan
            daysNav: document.getElementById('sfc-days-nav'),
            dayViewContainer: document.getElementById('sfc-day-view-container'),
            btnSavePlan: document.getElementById('sfc-btn-save-plan'),

            // Tab 4: Shopping
            groupedShoppingContainer: document.getElementById('sfc-grouped-shopping-container'),
            shoppingRemainingCount: document.getElementById('sfc-shopping-remaining-count'),
            btnCheckAll: document.getElementById('sfc-btn-check-all'),
            btnGoToStores: document.getElementById('sfc-btn-go-to-stores'),
            quickStoreTeaser: document.getElementById('sfc-quick-store-teaser'),
            btnOpenMapTeaser: document.getElementById('sfc-btn-open-map-teaser'),

            // Tab 5: Stores & Geolocation
            locationPrompt: document.getElementById('sfc-location-prompt'),
            btnPromptGeolocate: document.getElementById('sfc-btn-prompt-geolocate'),
            recHero: document.getElementById('sfc-recommendation-hero'),
            recTitle: document.getElementById('sfc-rec-title'),
            recReason: document.getElementById('sfc-rec-reason'),
            recDistance: document.getElementById('sfc-rec-distance'),
            recTime: document.getElementById('sfc-rec-time'),
            recHours: document.getElementById('sfc-rec-hours'),
            recTips: document.getElementById('sfc-rec-tips'),
            recEngineLabel: document.getElementById('sfc-rec-engine-label'),
            recRouteYandex: document.getElementById('sfc-rec-route-yandex'),
            recRouteGoogle: document.getElementById('sfc-rec-route-google'),
            mapContainer: document.getElementById('sfc-map'),
            storesList: document.getElementById('sfc-stores-list'),
            storesSubtitle: document.getElementById('sfc-stores-subtitle'),
            btnGeolocate: document.getElementById('sfc-btn-geolocate'),
            searchAddressInput: document.getElementById('sfc-search-address'),
            btnSearchCity: document.getElementById('sfc-btn-search-city'),

            // Modals
            recipeModal: document.getElementById('sfc-recipe-modal'),
            modalContent: document.getElementById('sfc-modal-content'),
            modalCloseBtn: document.getElementById('sfc-modal-close-btn'),
            addModal: document.getElementById('sfc-add-modal'),
            addModalClose: document.getElementById('sfc-add-modal-close'),
            manualAddForm: document.getElementById('sfc-manual-add-form'),
            addNameInput: document.getElementById('sfc-add-name'),
            addQtyInput: document.getElementById('sfc-add-qty'),
            swapModal: document.getElementById('sfc-swap-modal'),
            swapModalClose: document.getElementById('sfc-swap-modal-close'),
            swapList: document.getElementById('sfc-swap-list'),
            swapTitle: document.getElementById('sfc-swap-modal-title'),
            swapSubtitle: document.getElementById('sfc-swap-modal-subtitle'),
        };
    }

    // Initialize Application
    document.addEventListener('DOMContentLoaded', function () {
        initDOM();
        bindEvents();
        loadAIStatus();

        // Cleanse and load saved fridge products
        const saved = localStorage.getItem('sfc_products');
        if (saved) {
            try {
                const parsed = JSON.parse(saved);
                if (Array.isArray(parsed)) {
                    parsed.forEach(p => {
                        // Purge any stored emoji icons from older sessions
                        if (p.icon && /[\uD800-\uDFFF\u2600-\u27BF]/.test(p.icon)) {
                            p.icon = 'inventory_2';
                        }
                    });
                    state.products = parsed;
                    renderProducts();
                }
            } catch (e) {}
        }

        // On launch: Automatically ask browser for user's REAL location
        requestUserGeolocation(true);
    });

    // Load Live AI status from backend
    function loadAIStatus() {
        if (!dom.aiStatusText) return;
        dom.aiStatusText.textContent = 'ИИ: ' + (sfcConfig.aiLabel || 'Подключение...');

        fetch(sfcConfig.restUrl + 'ai-status')
            .then(r => r.json())
            .then(data => {
                if (data.success && data.label) {
                    state.aiStatus = data;
                    dom.aiStatusText.textContent = 'ИИ: ' + data.label;
                    if (dom.recEngineLabel) {
                        dom.recEngineLabel.textContent = data.label;
                    }
                }
            })
            .catch(() => {
                dom.aiStatusText.textContent = 'ИИ: ' + (sfcConfig.aiLabel || 'Активен');
            });
    }

    // Event Bindings
    function bindEvents() {
        // Tab switching
        dom.tabs.forEach(tab => {
            tab.addEventListener('click', () => switchTab(tab.dataset.tab));
        });

        // Mode switching (Photo vs Text)
        dom.btnModePhoto.addEventListener('click', () => {
            dom.btnModePhoto.classList.add('active');
            dom.btnModeText.classList.remove('active');
            dom.photoArea.style.display = 'block';
            dom.textArea.style.display = 'none';
        });

        dom.btnModeText.addEventListener('click', () => {
            dom.btnModeText.classList.add('active');
            dom.btnModePhoto.classList.remove('active');
            dom.photoArea.style.display = 'none';
            dom.textArea.style.display = 'block';
        });

        // File upload & Dropzone
        dom.btnChooseFile.addEventListener('click', () => dom.fileInput.click());
        dom.btnCamera.addEventListener('click', () => {
            dom.fileInput.setAttribute('capture', 'environment');
            dom.fileInput.click();
        });

        dom.fileInput.addEventListener('change', handleFileSelect);

        dom.dropzone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dom.dropzone.classList.add('dragover');
        });

        dom.dropzone.addEventListener('dragleave', () => dom.dropzone.classList.remove('dragover'));

        dom.dropzone.addEventListener('drop', (e) => {
            e.preventDefault();
            dom.dropzone.classList.remove('dragover');
            if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                processImageFile(e.dataTransfer.files[0]);
            }
        });

        dom.btnRemovePhoto.addEventListener('click', resetPhotoUpload);

        // Text input parse
        dom.btnParseText.addEventListener('click', () => {
            const text = dom.textProducts.value.trim();
            if (!text) {
                alert('Пожалуйста, введите список продуктов');
                return;
            }
            sendAnalyzeRequest({ type: 'text', text: text }, false);
        });

        // Presets
        dom.presetButtons.forEach(btn => {
            btn.addEventListener('click', () => loadPreset(btn.dataset.preset));
        });

        dom.btnDemo.addEventListener('click', () => loadPreset('family'));
        dom.btnReset.addEventListener('click', resetAll);

        // Manual add modal
        dom.btnAddManualProduct.addEventListener('click', () => {
            dom.addModal.style.display = 'flex';
            dom.addNameInput.value = '';
            dom.addNameInput.focus();
        });

        dom.addModalClose.addEventListener('click', () => dom.addModal.style.display = 'none');
        dom.manualAddForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const name = dom.addNameInput.value.trim();
            const qty = dom.addQtyInput.value.trim() || '1 шт';
            if (name) {
                addProductItem(name, qty);
                dom.addModal.style.display = 'none';
            }
        });

        // Cook button: transitions to recipes & meal plan
        dom.btnStartCook.addEventListener('click', generateRecipesAndPlan);

        // Recipe filter buttons
        dom.recipeFilters.forEach(btn => {
            btn.addEventListener('click', () => {
                dom.recipeFilters.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                filterRecipes(btn.dataset.filter);
            });
        });

        // Modals close
        dom.modalCloseBtn.addEventListener('click', () => {
            dom.recipeModal.style.display = 'none';
        });

        if (dom.swapModalClose) {
            dom.swapModalClose.addEventListener('click', () => {
                dom.swapModal.style.display = 'none';
            });
        }

        window.addEventListener('click', (e) => {
            if (e.target === dom.recipeModal) dom.recipeModal.style.display = 'none';
            if (e.target === dom.addModal) dom.addModal.style.display = 'none';
            if (e.target === dom.swapModal) dom.swapModal.style.display = 'none';
        });

        // Save plan to WordPress
        dom.btnSavePlan.addEventListener('click', savePlanToWordPress);

        // Shopping Actions
        dom.btnCheckAll.addEventListener('click', toggleAllShoppingCheckboxes);
        dom.btnGoToStores.addEventListener('click', () => switchTab('tab-stores'));
        dom.btnOpenMapTeaser.addEventListener('click', () => switchTab('tab-stores'));

        // Geolocation & City Search
        if (dom.btnPromptGeolocate) {
            dom.btnPromptGeolocate.addEventListener('click', () => requestUserGeolocation(false));
        }
        dom.btnGeolocate.addEventListener('click', () => requestUserGeolocation(false));
        dom.btnSearchCity.addEventListener('click', searchCityAddress);
        dom.searchAddressInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') searchCityAddress();
        });
    }

    // Switch Tabs
    function switchTab(tabId) {
        dom.tabs.forEach(t => t.classList.toggle('active', t.dataset.tab === tabId));
        dom.views.forEach(v => v.classList.toggle('active', v.id === tabId));

        if (tabId === 'tab-stores') {
            setTimeout(initOrRefreshMap, 200);
            if (!state.userLocation) {
                showLocationPrompt();
            } else {
                fetchStoresAndRecommendations();
            }
        }
    }

    // Process Image: Converts to WebP via HTML5 Canvas before uploading
    function handleFileSelect(e) {
        if (e.target.files && e.target.files[0]) {
            processImageFile(e.target.files[0]);
        }
    }

    function processImageFile(file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const rawDataUrl = e.target.result;

            const img = new Image();
            img.onload = function () {
                const maxDim = 1600;
                let w = img.width;
                let h = img.height;
                if (w > maxDim || h > maxDim) {
                    const ratio = Math.min(maxDim / w, maxDim / h);
                    w = Math.round(w * ratio);
                    h = Math.round(h * ratio);
                }

                const canvas = document.createElement('canvas');
                canvas.width = w;
                canvas.height = h;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, w, h);

                let webpDataUrl = rawDataUrl;
                try {
                    const converted = canvas.toDataURL('image/webp', 0.85);
                    if (converted && converted.indexOf('data:image/webp') === 0) {
                        webpDataUrl = converted;
                    }
                } catch (err) {
                    console.warn('Canvas WebP fallback:', err);
                }

                dom.previewImg.src = webpDataUrl;
                dom.dropzone.style.display = 'none';
                dom.previewWrapper.style.display = 'block';
                dom.scanStatusText.innerHTML = '<span class="material-symbols-outlined sfc-spin">sync</span> <span>ИИ анализирует снимок и подбирает рецепты...</span>';

                // Automatically calculate recipes immediately after photo upload!
                sendAnalyzeRequest({
                    type: 'photo',
                    image_base64: webpDataUrl,
                    mime_type: 'image/webp'
                }, true);
            };
            img.src = rawDataUrl;
        };
        reader.readAsDataURL(file);
    }

    function resetPhotoUpload() {
        dom.fileInput.value = '';
        dom.previewWrapper.style.display = 'none';
        dom.dropzone.style.display = 'block';
    }

    // REST: Analyze Input
    function sendAnalyzeRequest(payload, autoGenerateAfter) {
        fetch(sfcConfig.restUrl + 'analyze-input', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': sfcConfig.nonce
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.products) {
                state.products = data.products;
                saveProductsState();
                renderProducts();
                if (dom.scanStatusText) {
                    dom.scanStatusText.innerHTML = `<span class="material-symbols-outlined" style="color:#137333;">check_circle</span> <span>Распознано продуктов: ${data.products.length}. Подбор меню...</span>`;
                }

                // If photo was uploaded, immediately calculate recipes & meal plan
                if (autoGenerateAfter) {
                    generateRecipesAndPlan();
                }
            } else {
                alert('Не удалось распознать продукты. Проверьте настройки Ollama/Claude или введите список текстом.');
            }
        })
        .catch(err => {
            console.error('Error analyzing input:', err);
            alert('Ошибка при анализе продуктов.');
        });
    }

    // Render Products Table
    function renderProducts() {
        dom.badgeProducts.textContent = state.products.length;

        if (state.products.length === 0) {
            dom.productsEmpty.style.display = 'block';
            dom.productsListWrapper.style.display = 'none';
            return;
        }

        dom.productsEmpty.style.display = 'none';
        dom.productsListWrapper.style.display = 'block';
        dom.productsTbody.innerHTML = '';

        state.products.forEach((prod, index) => {
            const tr = document.createElement('tr');

            const freshnessText = prod.perishable ? 'Скоропортящийся' : 'Длительное хранение';
            const iconName = prod.icon || 'inventory_2';

            tr.innerHTML = `
                <td>
                    <div class="sfc-item-cell">
                        <span class="material-symbols-outlined sfc-item-icon">${iconName}</span>
                        <span>${escapeHtml(prod.name)}</span>
                    </div>
                </td>
                <td>
                    <span class="sfc-qty-tag">${escapeHtml(prod.quantity || '1 шт')}</span>
                </td>
                <td>
                    <span style="color:#5f6368;">${escapeHtml(prod.category || 'Другое')}</span>
                </td>
                <td>
                    <span class="sfc-fresh-tag">${freshnessText}</span>
                </td>
                <td style="text-align: right;">
                    <button type="button" class="sfc-btn-icon btn-delete-prod" data-index="${index}" title="Удалить">
                        <span class="material-symbols-outlined" style="font-size:18px; color:#d93025;">delete</span>
                    </button>
                </td>
            `;

            dom.productsTbody.appendChild(tr);
        });

        document.querySelectorAll('.btn-delete-prod').forEach(btn => {
            btn.addEventListener('click', () => {
                const idx = parseInt(btn.dataset.index, 10);
                state.products.splice(idx, 1);
                saveProductsState();
                renderProducts();
            });
        });
    }

    function addProductItem(name, quantity) {
        sendAnalyzeRequest({
            type: 'text',
            text: `${name} ${quantity}`
        }, false);
    }

    function saveProductsState() {
        localStorage.setItem('sfc_products', JSON.stringify(state.products));
    }

    function resetAll() {
        if (confirm('Очистить список продуктов и созданное меню?')) {
            state.products = [];
            state.recipes = { all: [], ready_now: [], need_more: [] };
            state.weeklyPlan = [];
            state.recipesByType = {};
            state.shoppingList = [];
            state.groupedShopping = {};
            localStorage.removeItem('sfc_products');
            renderProducts();
            resetPhotoUpload();
            if (dom.textProducts) dom.textProducts.value = '';
            dom.recipesContainer.innerHTML = '';
            dom.badgeRecipes.textContent = '0';
            dom.badgeShopping.textContent = '0';
            switchTab('tab-fridge');
        }
    }

    // Presets
    function loadPreset(presetKey) {
        let text = '';
        if (presetKey === 'bachelor') {
            text = '4 яйца, 500 мл молока, сыр 150г, сосиски 4 шт, макароны, помидоры 2 шт, сливочное масло';
        } else if (presetKey === 'family') {
            text = 'Яйца куриные 10 шт, молоко 1 л, творог 400г, сыр российский 250г, куриное филе 600г, помидоры 4 шт, огурцы 3 шт, сметана, картофель 1.5 кг, морковь 2 шт, лук 3 шт, макароны 500г, сливочное масло';
        } else if (presetKey === 'healthy') {
            text = 'Куриное филе 500г, творог 200г, овсянка 300г, яйца 6 шт, огурцы 4 шт, помидоры 3 шт, свежая зелень, яблоки 4 шт, гречка 400г';
        } else if (presetKey === 'minimal') {
            text = '3 яйца, молоко 200мл, сыр 80г, 1 помидор';
        }

        dom.textProducts.value = text;
        dom.btnModeText.click();
        sendAnalyzeRequest({ type: 'text', text: text }, true);
    }

    // Generate Recipes & Plan
    function generateRecipesAndPlan() {
        if (state.products.length === 0) {
            alert('Сначала добавьте продукты в холодильник!');
            return;
        }

        dom.btnStartCook.innerHTML = '<span class="material-symbols-outlined sfc-spin">sync</span> <span>ИИ рассчитывает меню...</span>';
        dom.btnStartCook.disabled = true;

        fetch(sfcConfig.restUrl + 'generate-recipes', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': sfcConfig.nonce },
            body: JSON.stringify({ products: state.products })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                state.recipes = data;
                dom.badgeRecipes.textContent = data.total;
                renderRecipesGrid(data.all);
            }

            return fetch(sfcConfig.restUrl + 'generate-weekly-plan', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': sfcConfig.nonce },
                body: JSON.stringify({ products: state.products })
            });
        })
        .then(r => r.json())
        .then(planData => {
            if (planData.success) {
                state.weeklyPlan = planData.plan;
                state.shoppingList = planData.shopping_list;
                state.groupedShopping = planData.grouped_shopping;
                state.recipesByType = planData.recipes_by_type || {};
                dom.badgeShopping.textContent = planData.total_to_buy;

                renderWeeklyPlan();
                renderShoppingList();
                if (state.userLocation) {
                    fetchStoresAndRecommendations();
                }
                switchTab('tab-recipes');
            }
        })
        .catch(err => {
            console.error('Error generating:', err);
            alert('Ошибка при генерации меню.');
        })
        .finally(() => {
            dom.btnStartCook.innerHTML = '<span>Рассчитать меню</span> <span class="material-symbols-outlined">arrow_forward</span>';
            dom.btnStartCook.disabled = false;
        });
    }

    // Render Recipes
    function renderRecipesGrid(recipes) {
        dom.recipesContainer.innerHTML = '';

        if (!recipes || recipes.length === 0) {
            dom.recipesContainer.innerHTML = '<div class="sfc-empty-placeholder"><p>Подходящих рецептов не найдено. Добавьте больше продуктов.</p></div>';
            return;
        }

        recipes.forEach(recipe => {
            const card = document.createElement('div');
            card.className = 'sfc-feed-card';

            const isReady = recipe.can_cook_now;
            const availabilityHtml = isReady
                ? `<div class="sfc-recipe-availability ready"><span class="material-symbols-outlined" style="font-size:16px;">check_circle</span> <span>100% из вашего холодильника</span></div>`
                : `<div class="sfc-recipe-availability shop"><span class="material-symbols-outlined" style="font-size:16px;">shopping_bag</span> <span>Докупить: <strong>${escapeHtml((recipe.missing_ingredients || []).join(', '))}</strong></span></div>`;

            let ingHtml = '';
            (recipe.ingredients_status || []).slice(0, 5).forEach(ing => {
                const mark = ing.available ? '•' : '+';
                ingHtml += `<span style="margin-right:6px;">${mark} ${escapeHtml(ing.name)}</span>`;
            });

            card.innerHTML = `
                <div class="sfc-feed-img-box">
                    <img src="${recipe.image}" alt="${escapeHtml(recipe.title)}" loading="lazy" />
                </div>
                <div class="sfc-feed-body">
                    <span class="sfc-recipe-category">${escapeHtml(recipe.meal_type || 'Блюдо')}</span>
                    <h3 class="sfc-feed-title">${escapeHtml(recipe.title)}</h3>
                    <div class="sfc-feed-meta">
                        <span><span class="material-symbols-outlined" style="font-size:15px;">schedule</span> ${recipe.time_mins} мин</span>
                        <span><span class="material-symbols-outlined" style="font-size:15px;">local_fire_department</span> ${recipe.calories} ккал</span>
                    </div>
                    ${availabilityHtml}
                    <div class="sfc-feed-ingredients">
                        ${ingHtml}
                    </div>
                    <div class="sfc-feed-footer">
                        <span class="sfc-feed-macros">Б: ${recipe.protein}г • Ж: ${recipe.fat}г • У: ${recipe.carbs}г</span>
                        <button type="button" class="sfc-btn-m3 sfc-btn-outlined sfc-btn-sm btn-view-recipe">
                            Рецепт
                        </button>
                    </div>
                </div>
            `;

            card.querySelector('.btn-view-recipe').addEventListener('click', () => {
                openRecipeModal(recipe);
            });

            dom.recipesContainer.appendChild(card);
        });
    }

    function filterRecipes(filter) {
        if (filter === 'ready') {
            renderRecipesGrid(state.recipes.ready_now);
        } else if (filter === 'need_more') {
            renderRecipesGrid(state.recipes.need_more);
        } else {
            renderRecipesGrid(state.recipes.all);
        }
    }

    function openRecipeModal(recipe) {
        let ingHtml = '';
        (recipe.ingredients_status || []).forEach(ing => {
            const mark = ing.available
                ? '<span class="material-symbols-outlined" style="color:#137333; font-size:18px;">check_circle</span>'
                : '<span class="material-symbols-outlined" style="color:#b06000; font-size:18px;">shopping_basket</span>';
            const statusLabel = ing.available
                ? '<span style="color:#137333; font-size:12px; margin-left:6px; font-weight:500;">в наличии</span>'
                : '<span style="color:#b06000; font-size:12px; margin-left:6px; font-weight:500;">нужно докупить</span>';

            ingHtml += `<li style="display:flex; justify-content:space-between; align-items:center; padding:8px 0; border-bottom:1px solid #e8eaed;">
                <div style="display:flex; align-items:center; gap:6px;">
                    ${mark}
                    <span>${escapeHtml(ing.name)}</span>
                    ${statusLabel}
                </div>
                <span style="color:#5f6368; font-size:13px;">${escapeHtml(ing.amount)}</span>
            </li>`;
        });

        let stepHtml = '';
        (recipe.instructions || []).forEach((step, idx) => {
            stepHtml += `<li style="margin-bottom:12px; line-height:1.55; color:#202124;">
                <strong style="color:#1a73e8;">Шаг ${idx + 1}:</strong> ${escapeHtml(step)}
            </li>`;
        });

        dom.modalContent.innerHTML = `
            <div style="margin-bottom: 20px;">
                <span class="sfc-recipe-category" style="margin-bottom: 6px; display:inline-block;">${escapeHtml(recipe.meal_type || 'Блюдо')}</span>
                <h2 class="sfc-recipe-sheet-title" style="margin: 4px 0 8px; font-size: 22px; font-weight:700;">${escapeHtml(recipe.title)}</h2>
                <p style="color: #5f6368; font-size: 14px; margin:0 0 16px;">${escapeHtml(recipe.description || '')}</p>
                <div style="display:flex; gap:16px; background:#f8f9fa; border:1px solid #dadce0; border-radius:6px; padding:10px 14px; font-size:13px; font-weight:500;">
                    <span>Время: <strong>${recipe.time_mins} мин</strong></span>
                    <span>Калории: <strong>${recipe.calories} ккал</strong></span>
                    <span>Б: <strong>${recipe.protein}г</strong></span>
                    <span>Ж: <strong>${recipe.fat}г</strong></span>
                    <span>У: <strong>${recipe.carbs}г</strong></span>
                </div>
            </div>

            <div style="margin-bottom: 22px;">
                <h3 style="font-size: 16px; margin: 0 0 8px; color:#202124; font-family:'Lora',serif;">Необходимые ингредиенты</h3>
                <ul style="list-style:none; padding:0; margin:0;">
                    ${ingHtml}
                </ul>
            </div>

            <div style="margin-bottom: 24px;">
                <h3 style="font-size: 16px; margin: 0 0 10px; color:#202124; font-family:'Lora',serif;">Инструкция приготовления</h3>
                <ol style="padding-left: 20px; margin:0;">
                    ${stepHtml}
                </ol>
            </div>

            <div style="display:flex; justify-content:flex-end;">
                <button type="button" class="sfc-btn-m3 sfc-btn-primary" onclick="document.getElementById('sfc-recipe-modal').style.display='none';">
                    Закрыть
                </button>
            </div>
        `;

        dom.recipeModal.style.display = 'flex';
    }

    // Render Weekly Plan
    function renderWeeklyPlan() {
        if (!state.weeklyPlan || state.weeklyPlan.length === 0) return;

        dom.daysNav.innerHTML = '';
        state.weeklyPlan.forEach((day, index) => {
            const btn = document.createElement('button');
            btn.className = `sfc-cal-day-btn ${index === state.activeDayIndex ? 'active' : ''}`;
            btn.innerHTML = `
                <span class="day-title">${day.day_name}</span>
                <span class="day-sub">${day.nutrition.calories} ккал</span>
            `;
            btn.addEventListener('click', () => {
                state.activeDayIndex = index;
                renderWeeklyPlan();
            });
            dom.daysNav.appendChild(btn);
        });

        const activeDay = state.weeklyPlan[state.activeDayIndex];
        if (!activeDay) return;

        const meals = activeDay.meals;

        dom.dayViewContainer.innerHTML = `
            <div class="sfc-day-strategy-notice">
                <span class="material-symbols-outlined" style="color:#1a73e8;">tips_and_updates</span>
                <span><strong>Фокус дня:</strong> ${escapeHtml(activeDay.focus)}</span>
            </div>

            <div class="sfc-day-slots-grid">
                ${renderMealSlot(state.activeDayIndex, 'Завтрак', 'breakfast', meals.breakfast)}
                ${renderMealSlot(state.activeDayIndex, 'Обед', 'lunch', meals.lunch)}
                ${renderMealSlot(state.activeDayIndex, 'Ужин', 'dinner', meals.dinner)}
                ${renderMealSlot(state.activeDayIndex, 'Перекус', 'snack', meals.snack)}
            </div>

            <div style="margin-top:16px; background:#ffffff; border:1px solid #dadce0; border-radius:6px; padding:12px 18px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; font-size:13px;">
                <div>
                    <strong>Итого за день:</strong>
                    <span style="margin-left:8px; color:#5f6368;">${activeDay.nutrition.calories} ккал</span>
                </div>
                <div style="display:flex; gap:16px; color:#5f6368;">
                    <span>Белки: <strong style="color:#202124;">${activeDay.nutrition.protein}г</strong></span>
                    <span>Жиры: <strong style="color:#202124;">${activeDay.nutrition.fat}г</strong></span>
                    <span>Углеводы: <strong style="color:#202124;">${activeDay.nutrition.carbs}г</strong></span>
                </div>
            </div>
        `;

        dom.dayViewContainer.querySelectorAll('.btn-open-slot-recipe').forEach(btn => {
            btn.addEventListener('click', () => {
                const recipeId = btn.dataset.recipeId;
                const found = state.recipes.all.find(r => r.id === recipeId);
                if (found) openRecipeModal(found);
            });
        });

        dom.dayViewContainer.querySelectorAll('.btn-swap-slot').forEach(btn => {
            btn.addEventListener('click', () => {
                const dayIdx = parseInt(btn.dataset.day, 10);
                const slotKey = btn.dataset.slot;
                const mealType = btn.dataset.type;
                openSwapModal(dayIdx, slotKey, mealType);
            });
        });
    }

    function renderMealSlot(dayIndex, label, slotKey, meal) {
        if (!meal) return '';
        const isFromFridge = meal.can_cook_from_fridge !== undefined ? meal.can_cook_from_fridge : meal.can_cook_now;
        const statusHtml = isFromFridge
            ? `<span class="sfc-slot-state ready"><span class="material-symbols-outlined" style="font-size:13px;">check</span> Из холодильника</span>`
            : `<span class="sfc-slot-state shop"><span class="material-symbols-outlined" style="font-size:13px;">shopping_bag</span> Докупить: ${(meal.missing_items || []).slice(0, 1).join(', ')}</span>`;

        return `
            <div class="sfc-slot-card">
                <div class="sfc-slot-head">
                    <span class="sfc-slot-tag">${label}</span>
                    ${statusHtml}
                </div>
                <div class="sfc-slot-img">
                    <img src="${meal.image}" alt="${escapeHtml(meal.title)}" />
                </div>
                <h4>${escapeHtml(meal.title)}</h4>
                <div class="sfc-slot-meta">
                    <span>${meal.time_mins} мин</span> • <span>${meal.calories} ккал</span>
                </div>
                <div class="sfc-slot-actions">
                    <button type="button" class="sfc-btn-m3 sfc-btn-outlined sfc-btn-sm btn-open-slot-recipe" data-recipe-id="${meal.id}">
                        Рецепт
                    </button>
                    <button type="button" class="sfc-btn-m3 sfc-btn-tonal sfc-btn-sm btn-swap-slot" data-day="${dayIndex}" data-slot="${slotKey}" data-type="${label}">
                        Заменить
                    </button>
                </div>
            </div>
        `;
    }

    // Modal: Swap Meal Alternative
    function openSwapModal(dayIndex, slotKey, mealType) {
        state.currentSwapSlot = { dayIndex, slotKey, mealType };
        dom.swapTitle.textContent = `Замена блюда: ${mealType} (${state.weeklyPlan[dayIndex].day_name})`;
        dom.swapSubtitle.textContent = 'Выберите блюдо только из имеющихся продуктов или с докупкой';
        dom.swapList.innerHTML = '';

        let candidates = (state.recipesByType && state.recipesByType[mealType]) ? state.recipesByType[mealType] : [];
        if (!candidates || candidates.length === 0) {
            candidates = state.recipes.all.filter(r => (r.meal_type || '').toLowerCase() === mealType.toLowerCase());
        }
        if (candidates.length === 0) {
            candidates = state.recipes.all;
        }

        const seen = new Set();
        const uniqueCandidates = [];
        candidates.forEach(c => {
            if (!seen.has(c.id)) {
                seen.add(c.id);
                uniqueCandidates.push(c);
            }
        });

        uniqueCandidates.forEach(cand => {
            const isReady = cand.can_cook_now;
            const tag = isReady
                ? '<span style="color:#137333; font-weight:600;"><span class="material-symbols-outlined" style="font-size:13px;">check</span> Только из холодильника</span>'
                : `<span style="color:#b06000; font-weight:600;"><span class="material-symbols-outlined" style="font-size:13px;">shopping_bag</span> Докупить: ${(cand.missing_ingredients || []).slice(0, 2).join(', ')}</span>`;

            const item = document.createElement('div');
            item.className = 'sfc-swap-item';
            item.innerHTML = `
                <div class="sfc-swap-item-info">
                    <div class="sfc-swap-item-title">${escapeHtml(cand.title)}</div>
                    <div class="sfc-swap-item-meta">
                        ${tag}
                        <span>${cand.time_mins} мин</span>
                        <span>${cand.calories} ккал</span>
                    </div>
                </div>
                <button type="button" class="sfc-btn-m3 sfc-btn-primary sfc-btn-sm btn-choose-swap">
                    Выбрать
                </button>
            `;

            item.querySelector('.btn-choose-swap').addEventListener('click', () => {
                applySwapMeal(cand);
            });

            dom.swapList.appendChild(item);
        });

        dom.swapModal.style.display = 'flex';
    }

    function applySwapMeal(newRecipe) {
        if (!state.currentSwapSlot) return;
        const { dayIndex, slotKey } = state.currentSwapSlot;

        const availableRoots = {};
        state.products.forEach(p => { availableRoots[p.clean_key] = true; });

        const missing = [];
        (newRecipe.ingredients || []).forEach(ing => {
            if (!availableRoots[ing.root]) {
                missing.push(ing.name);
            }
        });

        const updatedMeal = Object.assign({}, newRecipe, {
            missing_items: missing,
            can_cook_from_fridge: missing.length === 0
        });

        state.weeklyPlan[dayIndex].meals[slotKey] = updatedMeal;

        // Recalculate daily nutrition
        const meals = state.weeklyPlan[dayIndex].meals;
        let cals = 0, p = 0, f = 0, c = 0;
        ['breakfast', 'lunch', 'dinner', 'snack'].forEach(k => {
            if (meals[k]) {
                cals += meals[k].calories || 0;
                p += meals[k].protein || 0;
                f += meals[k].fat || 0;
                c += meals[k].carbs || 0;
            }
        });
        state.weeklyPlan[dayIndex].nutrition = { calories: cals, protein: p, fat: f, carbs: c };

        recalculateShoppingList();

        dom.swapModal.style.display = 'none';
        renderWeeklyPlan();
        renderShoppingList();
    }

    function recalculateShoppingList() {
        const availableRoots = {};
        state.products.forEach(p => { availableRoots[p.clean_key] = true; });

        const needed = {};
        state.weeklyPlan.forEach(day => {
            ['breakfast', 'lunch', 'dinner', 'snack'].forEach(slotKey => {
                const meal = day.meals[slotKey];
                if (meal && meal.ingredients) {
                    meal.ingredients.forEach(ing => {
                        const root = ing.root;
                        if (!availableRoots[root]) {
                            if (!needed[root]) {
                                needed[root] = {
                                    name: ing.name,
                                    root: root,
                                    category: ing.category || 'Бакалея и продукты',
                                    amount: ing.amount || '1 шт',
                                    count: 1
                                };
                            } else {
                                needed[root].count++;
                            }
                        }
                    });
                }
            });
        });

        state.shoppingList = Object.values(needed);
        const grouped = {};
        state.shoppingList.forEach(item => {
            const cat = item.category || 'Бакалея и продукты';
            if (!grouped[cat]) grouped[cat] = [];
            grouped[cat].push(item);
        });
        state.groupedShopping = grouped;
        dom.badgeShopping.textContent = state.shoppingList.length;
    }

    // Save Plan to WP
    function savePlanToWordPress() {
        if (!state.weeklyPlan || state.weeklyPlan.length === 0) return;

        dom.btnSavePlan.innerHTML = '<span class="material-symbols-outlined sfc-spin">sync</span> <span>Сохранение...</span>';
        dom.btnSavePlan.disabled = true;

        fetch(sfcConfig.restUrl + 'save-meal-plan', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': sfcConfig.nonce },
            body: JSON.stringify({
                title: 'План питания от ' + new Date().toLocaleDateString('ru-RU'),
                plan_data: {
                    weeklyPlan: state.weeklyPlan,
                    shoppingList: state.shoppingList
                }
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                alert('План успешно сохранен в WordPress!');
            } else {
                alert('Не удалось сохранить план.');
            }
        })
        .catch(e => {
            console.error('Error saving plan:', e);
            alert('Ошибка сервера при сохранении.');
        })
        .finally(() => {
            dom.btnSavePlan.innerHTML = '<span class="material-symbols-outlined">bookmark_add</span> <span>Сохранить план</span>';
            dom.btnSavePlan.disabled = false;
        });
    }

    // Render Shopping List
    function renderShoppingList() {
        if (!state.shoppingList || state.shoppingList.length === 0) {
            dom.groupedShoppingContainer.innerHTML = '<p class="sfc-subtext">Все необходимые продукты уже есть в наличии в холодильнике.</p>';
            dom.shoppingRemainingCount.textContent = '0';
            return;
        }

        dom.shoppingRemainingCount.textContent = state.shoppingList.length;
        dom.groupedShoppingContainer.innerHTML = '';

        for (const [category, items] of Object.entries(state.groupedShopping)) {
            const block = document.createElement('div');
            block.className = 'sfc-keep-category';

            let itemsHtml = '';
            items.forEach(item => {
                itemsHtml += `
                    <div class="sfc-keep-item" data-root="${item.root}">
                        <input type="checkbox" class="sfc-checkbox" id="chk_${item.root}" />
                        <label for="chk_${item.root}" class="sfc-keep-label">
                            <span>${escapeHtml(item.name)}</span>
                            <span class="sfc-qty-tag">${escapeHtml(item.amount)}</span>
                        </label>
                    </div>
                `;
            });

            block.innerHTML = `
                <h4>${escapeHtml(category)} (${items.length})</h4>
                <div>${itemsHtml}</div>
            `;

            dom.groupedShoppingContainer.appendChild(block);
        }

        dom.groupedShoppingContainer.querySelectorAll('.sfc-checkbox').forEach(chk => {
            chk.addEventListener('change', () => {
                const parent = chk.closest('.sfc-keep-item');
                parent.classList.toggle('checked', chk.checked);
                updateRemainingShoppingCount();
            });
        });
    }

    function toggleAllShoppingCheckboxes() {
        const checkboxes = dom.groupedShoppingContainer.querySelectorAll('.sfc-checkbox');
        const anyUnchecked = Array.from(checkboxes).some(c => !c.checked);
        checkboxes.forEach(c => {
            c.checked = anyUnchecked;
            c.closest('.sfc-keep-item').classList.toggle('checked', anyUnchecked);
        });
        updateRemainingShoppingCount();
    }

    function updateRemainingShoppingCount() {
        const unchecked = dom.groupedShoppingContainer.querySelectorAll('.sfc-checkbox:not(:checked)');
        dom.shoppingRemainingCount.textContent = unchecked.length;
    }

    // Show Location Prompt (never show Moscow if unknown)
    function showLocationPrompt() {
        if (dom.locationPrompt) dom.locationPrompt.style.display = 'flex';
        if (dom.recHero) dom.recHero.style.display = 'none';
        if (dom.storesList) {
            dom.storesList.innerHTML = '<div class="sfc-empty-placeholder" style="padding:24px 12px;"><span class="material-symbols-outlined sfc-empty-icon">near_me</span><h3>Ожидание местоположения</h3><p>Разрешите доступ к геопозиции или введите город выше, чтобы найти ближайшие магазины.</p></div>';
        }
    }

    // Fetch Stores & Recommendations
    function fetchStoresAndRecommendations() {
        if (!state.userLocation || !state.userLocation.lat || !state.userLocation.lon) {
            showLocationPrompt();
            return;
        }

        if (dom.locationPrompt) dom.locationPrompt.style.display = 'none';

        fetch(sfcConfig.restUrl + 'find-stores', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': sfcConfig.nonce },
            body: JSON.stringify({
                lat: state.userLocation.lat,
                lon: state.userLocation.lon,
                shopping_list: state.shoppingList
            })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.stores && data.stores.length > 0) {
                state.stores = data.stores;
                state.recommendation = data.recommendation;
                renderStoreRecommendation(data.recommendation);
                renderStoresList(data.stores);
                updateMapMarkers();
            } else if (data.needs_location) {
                showLocationPrompt();
            }
        })
        .catch(e => console.error('Error fetching stores:', e));
    }

    // Render Store Recommendation Card
    function renderStoreRecommendation(rec) {
        if (!rec || !rec.store) {
            if (dom.recHero) dom.recHero.style.display = 'none';
            return;
        }
        if (dom.recHero) dom.recHero.style.display = 'block';
        const store = rec.store;

        dom.recTitle.textContent = store.name;
        dom.recReason.textContent = rec.reason;
        dom.recDistance.textContent = store.distance_m + ' м';
        dom.recTime.innerHTML = `<span class="material-symbols-outlined">directions_walk</span> ~${store.walking_time_mins} мин пешком`;
        dom.recHours.innerHTML = `<span class="material-symbols-outlined">schedule</span> ${store.opening_hours || '08:00 - 23:00'}`;

        if (rec.ai_model && dom.recEngineLabel) {
            dom.recEngineLabel.textContent = 'ИИ: ' + rec.ai_model;
        }

        dom.recTips.innerHTML = '';
        if (rec.tips && rec.tips.length > 0) {
            rec.tips.forEach(t => {
                const div = document.createElement('div');
                div.style.display = 'flex';
                div.style.alignItems = 'center';
                div.style.gap = '6px';
                div.innerHTML = `<span class="material-symbols-outlined" style="color:#137333; font-size:16px;">check</span> <span>${escapeHtml(t)}</span>`;
                dom.recTips.appendChild(div);
            });
        }

        if (rec.navigation_urls) {
            dom.recRouteYandex.href = rec.navigation_urls.yandex;
            dom.recRouteGoogle.href = rec.navigation_urls.google;
        }

        if (dom.quickStoreTeaser) {
            dom.quickStoreTeaser.innerHTML = `<strong>${store.name}</strong> (${store.distance_m} м) — ${rec.reason}`;
        }
    }

    // Render Stores List
    function renderStoresList(stores) {
        dom.storesList.innerHTML = '';
        if (!stores || stores.length === 0) {
            dom.storesList.innerHTML = '<p class="sfc-subtext">Супермаркеты поблизости не найдены. Попробуйте указать другой адрес.</p>';
            return;
        }

        if (dom.storesSubtitle && state.userCityName) {
            dom.storesSubtitle.textContent = `Найдено ${stores.length} магазинов рядом с вами (${state.userCityName})`;
        }

        stores.forEach(store => {
            const item = document.createElement('div');
            item.className = 'sfc-place-card';
            item.dataset.storeId = store.id;

            let tagsHtml = '';
            (store.features || []).forEach(f => {
                tagsHtml += `<span style="font-size:11px; color:#5f6368; margin-right:6px;">• ${escapeHtml(f)}</span>`;
            });

            item.innerHTML = `
                <div class="sfc-place-head">
                    <span>${escapeHtml(store.name)}</span>
                    <span style="color:#137333; font-weight:600;">${store.distance_m} м</span>
                </div>
                <div class="sfc-place-addr">${escapeHtml(store.address || 'Адрес уточняется')} • ${escapeHtml(store.opening_hours || '08:00-23:00')}</div>
                <div style="margin-top:4px;">${tagsHtml}</div>
            `;

            item.addEventListener('click', () => {
                document.querySelectorAll('.sfc-place-card').forEach(s => s.classList.remove('selected'));
                item.classList.add('selected');
                if (state.map) {
                    state.map.setView([store.lat, store.lon], 16);
                    const marker = state.mapMarkers.find(m => m.options.storeId === store.id);
                    if (marker) marker.openPopup();
                }
            });

            dom.storesList.appendChild(item);
        });
    }

    // Leaflet Map
    function initOrRefreshMap() {
        if (!dom.mapContainer) return;

        const defaultLat = state.userLocation ? state.userLocation.lat : 55.0;
        const defaultLon = state.userLocation ? state.userLocation.lon : 37.0;
        const zoom = state.userLocation ? 15 : 4;

        if (!state.map) {
            state.map = L.map(dom.mapContainer).setView([defaultLat, defaultLon], zoom);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(state.map);
        } else {
            state.map.invalidateSize();
            if (state.userLocation) {
                state.map.setView([state.userLocation.lat, state.userLocation.lon], 15);
            }
        }

        updateMapMarkers();
    }

    function updateMapMarkers() {
        if (!state.map) return;

        state.mapMarkers.forEach(m => state.map.removeLayer(m));
        state.mapMarkers = [];
        if (state.userMarker) state.map.removeLayer(state.userMarker);

        if (!state.userLocation) return;

        // User Marker
        const userIcon = L.divIcon({
            className: 'sfc-user-pin',
            html: '<div style="background:#1a73e8; color:#fff; width:26px; height:26px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:2px solid #fff; box-shadow:0 2px 6px rgba(0,0,0,0.3); font-size:12px;"><span class="material-symbols-outlined" style="font-size:16px;">person</span></div>',
            iconSize: [26, 26],
            iconAnchor: [13, 13]
        });

        state.userMarker = L.marker([state.userLocation.lat, state.userLocation.lon], { icon: userIcon })
            .addTo(state.map)
            .bindPopup('<strong>Вы находитесь здесь</strong>');

        // Store Markers
        (state.stores || []).forEach(store => {
            const isRec = state.recommendation?.store?.id === store.id;
            const markerBg = isRec ? '#1a73e8' : '#5f6368';

            const storeIcon = L.divIcon({
                className: 'sfc-store-pin',
                html: `<div style="background:${markerBg}; color:#fff; width:30px; height:30px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:2px solid #fff; box-shadow:0 2px 5px rgba(0,0,0,0.25);"><span class="material-symbols-outlined" style="font-size:16px;">storefront</span></div>`,
                iconSize: [30, 30],
                iconAnchor: [15, 15]
            });

            const popupContent = `
                <div style="font-family:'Roboto',sans-serif; min-width:180px; padding:4px;">
                    <div style="font-size:14px; font-weight:700; margin-bottom:2px;">${escapeHtml(store.name)}</div>
                    <div style="font-size:12px; color:#5f6368; margin-bottom:4px;">${escapeHtml(store.address || '')}</div>
                    <div style="font-size:12px; font-weight:600; color:#137333; margin-bottom:8px;">${store.distance_m} м (~${store.walking_time_mins} мин)</div>
                    <a href="https://yandex.ru/maps/?rtext=~${store.lat},${store.lon}&rtt=pd" target="_blank" style="display:inline-block; background:#1a73e8; color:#fff; padding:4px 10px; border-radius:4px; font-size:11px; text-decoration:none;">Маршрут</a>
                </div>
            `;

            const marker = L.marker([store.lat, store.lon], { icon: storeIcon, storeId: store.id })
                .addTo(state.map)
                .bindPopup(popupContent);

            if (isRec) marker.openPopup();
            state.mapMarkers.push(marker);
        });
    }

    // Geolocation: Asks browser for GPS, reverse geocodes to get REAL city
    function requestUserGeolocation(isSilentAuto) {
        if (!navigator.geolocation) {
            if (!isSilentAuto) alert('Геолокация не поддерживается вашим браузером.');
            return;
        }

        if (dom.btnGeolocate) {
            dom.btnGeolocate.innerHTML = '<span class="material-symbols-outlined sfc-spin">sync</span> <span>Определение...</span>';
        }

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                const lat = pos.coords.latitude;
                const lon = pos.coords.longitude;
                state.userLocation = { lat: lat, lon: lon };

                // Reverse geocode via Nominatim to detect real city / town
                fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}&zoom=14&addressdetails=1`)
                    .then(r => r.json())
                    .then(geoData => {
                        let city = '';
                        if (geoData && geoData.address) {
                            city = geoData.address.city ||
                                   geoData.address.town ||
                                   geoData.address.village ||
                                   geoData.address.municipality ||
                                   geoData.address.county ||
                                   geoData.address.suburb ||
                                   '';
                        }
                        if (!city && geoData && geoData.display_name) {
                            city = geoData.display_name.split(',')[0].trim();
                        }
                        if (city) {
                            state.userCityName = city;
                            if (dom.searchAddressInput) {
                                dom.searchAddressInput.value = city;
                            }
                        }
                    })
                    .catch(e => console.warn('Reverse geocode error:', e))
                    .finally(() => {
                        if (dom.btnGeolocate) {
                            dom.btnGeolocate.innerHTML = '<span class="material-symbols-outlined">my_location</span> <span>Мое местоположение</span>';
                        }
                        if (dom.locationPrompt) {
                            dom.locationPrompt.style.display = 'none';
                        }
                        if (state.map) {
                            state.map.setView([lat, lon], 15);
                        }
                        fetchStoresAndRecommendations();
                    });
            },
            (err) => {
                console.warn('Geolocation denied or unavailable:', err);
                if (dom.btnGeolocate) {
                    dom.btnGeolocate.innerHTML = '<span class="material-symbols-outlined">my_location</span> <span>Мое местоположение</span>';
                }
                if (!isSilentAuto) {
                    alert('Пожалуйста, разрешите доступ к геопозиции в браузере или введите ваш город в строке поиска.');
                }
                if (!state.userLocation) {
                    showLocationPrompt();
                }
            },
            { timeout: 10000, enableHighAccuracy: true }
        );
    }

    // Geocoding city or street address input
    function searchCityAddress() {
        const query = dom.searchAddressInput.value.trim();
        if (!query) {
            alert('Пожалуйста, введите название вашего города или улицы.');
            return;
        }

        dom.btnSearchCity.innerHTML = '<span class="material-symbols-outlined sfc-spin">sync</span>';

        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1&addressdetails=1`)
            .then(r => r.json())
            .then(data => {
                if (data && data.length > 0) {
                    const lat = parseFloat(data[0].lat);
                    const lon = parseFloat(data[0].lon);
                    state.userLocation = { lat: lat, lon: lon };

                    const detected = data[0].address?.city ||
                                     data[0].address?.town ||
                                     data[0].address?.village ||
                                     data[0].display_name.split(',')[0].trim();
                    if (detected) {
                        state.userCityName = detected;
                        dom.searchAddressInput.value = detected;
                    }

                    if (dom.locationPrompt) {
                        dom.locationPrompt.style.display = 'none';
                    }

                    if (state.map) {
                        state.map.setView([lat, lon], 15);
                    }
                    fetchStoresAndRecommendations();
                } else {
                    alert('Город не найден. Проверьте правильность написания.');
                }
            })
            .catch(e => {
                console.error('Geocoding error:', e);
                alert('Ошибка при поиске адреса.');
            })
            .finally(() => {
                dom.btnSearchCity.innerHTML = '<span class="material-symbols-outlined">arrow_forward</span>';
            });
    }

    // Helper: Escape HTML
    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

})();

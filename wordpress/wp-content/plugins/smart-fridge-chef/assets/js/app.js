
(function () {
    'use strict';

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
        currentSwapSlot: null,
    };

    let dom = {};

    function initDOM() {
        dom = {
            tabs: document.querySelectorAll('.sfc-nav-item'),
            views: document.querySelectorAll('.sfc-view'),
            badgeProducts: document.getElementById('sfc-badge-products-count'),
            badgeRecipes: document.getElementById('sfc-badge-recipes-count'),
            badgeShopping: document.getElementById('sfc-badge-shopping-count'),
            aiStatusText: document.getElementById('sfc-ai-status-text'),

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
            productsBoard: document.getElementById('sfc-products-board'),
            btnAddManualProduct: document.getElementById('sfc-btn-add-manual-product'),
            btnStartCook: document.getElementById('sfc-btn-start-cook'),
            presetButtons: document.querySelectorAll('.sfc-btn-subtle, .sfc-preset-chip'),
            btnDemo: document.getElementById('sfc-btn-demo'),
            btnReset: document.getElementById('sfc-btn-reset'),

            recipesContainer: document.getElementById('sfc-recipes-container'),
            recipeFilters: document.querySelectorAll('.sfc-filter-btn, .sfc-filter-pill'),

            daysNav: document.getElementById('sfc-days-nav'),
            dayViewContainer: document.getElementById('sfc-day-view-container'),
            btnSavePlan: document.getElementById('sfc-btn-save-plan'),

            groupedShoppingContainer: document.getElementById('sfc-grouped-shopping-container'),
            shoppingRemainingCount: document.getElementById('sfc-shopping-remaining-count'),
            btnCheckAll: document.getElementById('sfc-btn-check-all'),
            btnGoToStores: document.getElementById('sfc-btn-go-to-stores'),
            quickStoreTeaser: document.getElementById('sfc-quick-store-teaser'),
            btnOpenMapTeaser: document.getElementById('sfc-btn-open-map-teaser'),

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

    document.addEventListener('DOMContentLoaded', function () {
        initDOM();
        bindEvents();
        loadAIStatus();

        const saved = localStorage.getItem('sfc_products');
        if (saved) {
            try {
                const parsed = JSON.parse(saved);
                if (Array.isArray(parsed)) {
                    parsed.forEach(p => {

                        if (p.icon && /[\uD800-\uDFFF\u2600-\u27BF]/.test(p.icon)) {
                            p.icon = 'inventory_2';
                        }
                    });
                    state.products = parsed;
                    renderProducts();
                }
            } catch (e) {}
        }

        requestUserGeolocation(true);
    });

    function loadAIStatus() {
        fetch(sfcConfig.restUrl + 'ai-status')
            .then(r => r.json())
            .then(data => {
                if (data.success && data.label) {
                    state.aiStatus = data;
                    if (dom.aiStatusText) dom.aiStatusText.textContent = data.label;
                    const statusBadge = document.getElementById('sfc-ai-status-badge');
                    if (statusBadge) statusBadge.title = 'Движок рецептов: ' + data.label;
                    if (dom.recEngineLabel) {
                        dom.recEngineLabel.textContent = 'Оптимальный выбор';
                    }
                }
            })
            .catch(() => {});
    }

    function bindEvents() {

        dom.tabs.forEach(tab => {
            tab.addEventListener('click', () => switchTab(tab.dataset.tab));
        });

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

        dom.btnParseText.addEventListener('click', () => {
            const text = dom.textProducts.value.trim();
            if (!text) {
                alert('Пожалуйста, введите список продуктов');
                return;
            }
            sendAnalyzeRequest({ type: 'text', text: text }, false);
        });

        dom.presetButtons.forEach(btn => {
            btn.addEventListener('click', () => loadPreset(btn.dataset.preset));
        });

        dom.btnDemo.addEventListener('click', () => loadPreset('family'));
        dom.btnReset.addEventListener('click', resetAll);

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

        dom.btnStartCook.addEventListener('click', generateRecipesAndPlan);

        dom.recipeFilters.forEach(btn => {
            btn.addEventListener('click', () => {
                dom.recipeFilters.forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                filterRecipes(btn.dataset.filter);
            });
        });

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

        dom.btnSavePlan.addEventListener('click', savePlanToWordPress);

        dom.btnCheckAll.addEventListener('click', toggleAllShoppingCheckboxes);
        dom.btnGoToStores.addEventListener('click', () => switchTab('tab-stores'));
        dom.btnOpenMapTeaser.addEventListener('click', () => switchTab('tab-stores'));

        if (dom.btnPromptGeolocate) {
            dom.btnPromptGeolocate.addEventListener('click', () => requestUserGeolocation(false));
        }
        dom.btnGeolocate.addEventListener('click', () => requestUserGeolocation(false));
        dom.btnSearchCity.addEventListener('click', searchCityAddress);
        dom.searchAddressInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') searchCityAddress();
        });
    }

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
                dom.scanStatusText.innerHTML = '<span class="material-symbols-outlined sfc-spin">sync</span> <span>Определение продуктов и подбор рецептов...</span>';

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
                    dom.scanStatusText.innerHTML = `<span class="material-symbols-outlined sfc-scan-success">check_circle</span> <span>Определено продуктов: ${data.products.length}. Подбор меню...</span>`;
                }

                if (autoGenerateAfter) {
                    generateRecipesAndPlan();
                }
            } else {
                alert('Не удалось распознать продукты. Проверьте подключение или введите список текстом.');
            }
        })
        .catch(err => {
            console.error('Error analyzing input:', err);
            alert('Ошибка при анализе продуктов.');
        });
    }

    function renderProducts() {
        dom.badgeProducts.textContent = state.products.length;

        if (state.products.length === 0) {
            dom.productsEmpty.style.display = 'block';
            dom.productsListWrapper.style.display = 'none';
            return;
        }

        dom.productsEmpty.style.display = 'none';
        dom.productsListWrapper.style.display = 'block';

        if (dom.productsBoard) {
            dom.productsBoard.innerHTML = '';

            const groups = {};
            state.products.forEach((prod, index) => {
                const cat = prod.category || 'Другое';
                if (!groups[cat]) groups[cat] = [];
                groups[cat].push({ prod, index });
            });

            const catIcons = {
                'Овощи и зелень': 'eco',
                'Фрукты и ягоды': 'nutrition',
                'Молочные продукты': 'egg',
                'Сыры': 'restaurant',
                'Мясо и птица': 'restaurant_menu',
                'Рыба и морепродукты': 'set_meal',
                'Бакалея и крупы': 'grain',
                'Бакалея и продукты': 'grain',
                'Хлеб и выпечка': 'bakery_dining',
                'Соусы и приправы': 'soup_kitchen',
                'Другое': 'kitchen'
            };

            for (const [category, items] of Object.entries(groups)) {
                const shelf = document.createElement('div');
                shelf.className = 'sfc-pantry-shelf';

                const shelfIcon = catIcons[category] || 'kitchen';

                let itemsHtml = '';
                items.forEach(({ prod, index }) => {
                    const iconName = prod.icon || shelfIcon;
                    const freshClass = prod.perishable ? 'is-fresh' : 'is-stable';
                    const freshLabel = prod.perishable ? 'Свежее' : 'Хранение';

                    itemsHtml += `
                        <div class="sfc-pantry-card">
                            <div class="sfc-pantry-card-icon">
                                <span class="material-symbols-outlined">${iconName}</span>
                            </div>
                            <div class="sfc-pantry-card-info">
                                <span class="sfc-pantry-card-name">${escapeHtml(prod.name)}</span>
                                <div class="sfc-pantry-card-meta">
                                    <span class="sfc-pantry-card-qty">${escapeHtml(prod.quantity || '1 шт')}</span>
                                    <span class="sfc-pantry-card-freshness ${freshClass}">• ${freshLabel}</span>
                                </div>
                            </div>
                            <button type="button" class="sfc-pantry-card-remove btn-delete-prod" data-index="${index}" title="Убрать из холодильника" aria-label="Удалить">
                                <span class="material-symbols-outlined">close</span>
                            </button>
                        </div>
                    `;
                });

                shelf.innerHTML = `
                    <div class="sfc-pantry-shelf-header">
                        <span class="material-symbols-outlined sfc-shelf-header-icon">${shelfIcon}</span>
                        <h3 class="sfc-pantry-shelf-title">${escapeHtml(category)}</h3>
                        <span class="sfc-pantry-shelf-count">${items.length}</span>
                    </div>
                    <div class="sfc-pantry-shelf-grid">
                        ${itemsHtml}
                    </div>
                `;

                dom.productsBoard.appendChild(shelf);
            }
        }

        if (dom.productsTbody) {
            dom.productsTbody.innerHTML = '';
            state.products.forEach((prod, index) => {
                const tr = document.createElement('tr');
                const freshnessText = prod.perishable ? 'Свежее' : 'Хранение';
                const iconName = prod.icon || 'kitchen';

                tr.innerHTML = `
                    <td>
                        <div class="sfc-item-cell">
                            <span class="material-symbols-outlined sfc-item-icon">${iconName}</span>
                            <span>${escapeHtml(prod.name)}</span>
                        </div>
                    </td>
                    <td><span class="sfc-qty-tag">${escapeHtml(prod.quantity || '1 шт')}</span></td>
                    <td><span>${escapeHtml(prod.category || 'Другое')}</span></td>
                    <td><span class="sfc-fresh-tag">${freshnessText}</span></td>
                    <td style="text-align: right;">
                        <button type="button" class="sfc-btn-icon-subtle btn-delete-prod" data-index="${index}" title="Удалить">
                            <span class="material-symbols-outlined">delete_outline</span>
                        </button>
                    </td>
                `;
                dom.productsTbody.appendChild(tr);
            });
        }

        document.querySelectorAll('.btn-delete-prod').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
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

    function generateRecipesAndPlan() {
        if (state.products.length === 0) {
            alert('Сначала добавьте продукты в холодильник!');
            return;
        }

        dom.btnStartCook.innerHTML = '<span class="material-symbols-outlined sfc-spin">sync</span> <span>Составление меню и рецептов...</span>';
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
            alert('Ошибка при составлении меню.');
        })
        .finally(() => {
            dom.btnStartCook.innerHTML = '<span>Рассчитать меню</span> <span class="material-symbols-outlined">arrow_forward</span>';
            dom.btnStartCook.disabled = false;
        });
    }

    function renderRecipesGrid(recipes) {
        dom.recipesContainer.innerHTML = '';

        if (!recipes || recipes.length === 0) {
            dom.recipesContainer.innerHTML = `
                <div class="sfc-empty-placeholder" style="grid-column: 1 / -1; padding: 48px 16px;">
                    <div class="sfc-empty-icon-circle">
                        <span class="material-symbols-outlined sfc-empty-icon">menu_book</span>
                    </div>
                    <h3>Подходящих рецептов не найдено</h3>
                    <p class="sfc-subtext">Добавьте базовые продукты (яйца, овощи, сыр, молоко, крупы), чтобы шеф составил меню.</p>
                </div>
            `;
            return;
        }

        recipes.forEach(recipe => {
            const card = document.createElement('article');
            card.className = 'sfc-recipe-card';

            const isReady = recipe.can_cook_now;
            const missingCount = (recipe.missing_ingredients || []).length;
            const badgeHtml = isReady
                ? `<div class="sfc-recipe-floating-badge is-ready"><span class="material-symbols-outlined">check_circle</span><span>Все продукты есть</span></div>`
                : `<div class="sfc-recipe-floating-badge is-shop"><span class="material-symbols-outlined">shopping_basket</span><span>Докупить: ${missingCount} прод.</span></div>`;

            let ingHtml = '';
            (recipe.ingredients_status || []).slice(0, 4).forEach(ing => {
                const mark = ing.available
                    ? '<span class="material-symbols-outlined sfc-ing-mark-avail">check</span>'
                    : '<span class="material-symbols-outlined sfc-ing-mark-miss">add</span>';
                const cls = ing.available ? 'is-available' : 'is-missing';
                ingHtml += `<span class="sfc-recipe-ing-chip ${cls}">${mark} ${escapeHtml(ing.name)}</span>`;
            });
            if ((recipe.ingredients_status || []).length > 4) {
                const rem = recipe.ingredients_status.length - 4;
                ingHtml += `<span class="sfc-recipe-ing-chip is-more">+${rem}</span>`;
            }

            card.innerHTML = `
                <div class="sfc-recipe-cover">
                    <img src="${recipe.image}" alt="${escapeHtml(recipe.title)}" loading="lazy" />
                    <div class="sfc-recipe-cover-gradient"></div>
                    ${badgeHtml}
                    <div class="sfc-recipe-floating-time">
                        <span class="material-symbols-outlined">schedule</span>
                        <span>${recipe.time_mins} мин</span>
                    </div>
                </div>
                <div class="sfc-recipe-content">
                    <div class="sfc-recipe-top-meta">
                        <span class="sfc-recipe-type-tag">${escapeHtml(recipe.meal_type || 'Блюдо')}</span>
                        <span class="sfc-recipe-calories">${recipe.calories} ккал</span>
                    </div>
                    <h3 class="sfc-recipe-title">${escapeHtml(recipe.title)}</h3>
                    <p class="sfc-recipe-desc">${escapeHtml(recipe.description || '')}</p>
                    
                    <div class="sfc-recipe-ingredients-preview">
                        ${ingHtml}
                    </div>

                    <div class="sfc-recipe-footer">
                        <div class="sfc-recipe-macros">
                            <span>Б: <strong>${recipe.protein}г</strong></span>
                            <span>Ж: <strong>${recipe.fat}г</strong></span>
                            <span>У: <strong>${recipe.carbs}г</strong></span>
                        </div>
                        <button type="button" class="sfc-btn-secondary sfc-btn-sm btn-view-recipe">
                            <span>Рецепт</span>
                            <span class="material-symbols-outlined">arrow_forward</span>
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
                ? '<span class="material-symbols-outlined sfc-modal-ing-check">check_circle</span>'
                : '<span class="material-symbols-outlined sfc-modal-ing-shop">shopping_basket</span>';
            const statusLabel = ing.available
                ? '<span class="sfc-modal-ing-tag ready">в наличии</span>'
                : '<span class="sfc-modal-ing-tag shop">нужно докупить</span>';

            ingHtml += `
                <li class="sfc-modal-ing-item">
                    <div class="sfc-modal-ing-left">
                        ${mark}
                        <span class="sfc-modal-ing-name">${escapeHtml(ing.name)}</span>
                        ${statusLabel}
                    </div>
                    <span class="sfc-modal-ing-qty">${escapeHtml(ing.amount)}</span>
                </li>
            `;
        });

        let stepHtml = '';
        (recipe.instructions || []).forEach((step, idx) => {
            const num = (idx + 1).toString().padStart(2, '0');
            stepHtml += `
                <li class="sfc-modal-step-item">
                    <span class="sfc-modal-step-num">${num}</span>
                    <p class="sfc-modal-step-text">${escapeHtml(step)}</p>
                </li>
            `;
        });

        dom.modalContent.innerHTML = `
            <div class="sfc-modal-recipe-header">
                <span class="sfc-recipe-type-tag">${escapeHtml(recipe.meal_type || 'Блюдо')}</span>
                <h2 class="sfc-modal-recipe-title">${escapeHtml(recipe.title)}</h2>
                <p class="sfc-modal-recipe-desc">${escapeHtml(recipe.description || '')}</p>
                <div class="sfc-modal-nutrition-strip">
                    <div class="sfc-modal-nut-item">
                        <span class="nut-label">Время</span>
                        <span class="nut-val">${recipe.time_mins} мин</span>
                    </div>
                    <div class="sfc-modal-nut-item">
                        <span class="nut-label">Калории</span>
                        <span class="nut-val">${recipe.calories} ккал</span>
                    </div>
                    <div class="sfc-modal-nut-item">
                        <span class="nut-label">Белки</span>
                        <span class="nut-val">${recipe.protein}г</span>
                    </div>
                    <div class="sfc-modal-nut-item">
                        <span class="nut-label">Жиры</span>
                        <span class="nut-val">${recipe.fat}г</span>
                    </div>
                    <div class="sfc-modal-nut-item">
                        <span class="nut-label">Углеводы</span>
                        <span class="nut-val">${recipe.carbs}г</span>
                    </div>
                </div>
            </div>

            <div class="sfc-modal-section">
                <h3 class="sfc-modal-section-title">
                    <span class="material-symbols-outlined">receipt_long</span>
                    <span>Необходимые ингредиенты</span>
                </h3>
                <ul class="sfc-modal-ingredients-list">
                    ${ingHtml}
                </ul>
            </div>

            <div class="sfc-modal-section">
                <h3 class="sfc-modal-section-title">
                    <span class="material-symbols-outlined">soup_kitchen</span>
                    <span>Пошаговое приготовление</span>
                </h3>
                <ol class="sfc-modal-steps-list">
                    ${stepHtml}
                </ol>
            </div>

            <div class="sfc-modal-bottom-actions">
                <button type="button" class="sfc-btn-primary" onclick="document.getElementById('sfc-recipe-modal').style.display='none';">
                    Понятно, готовить!
                </button>
            </div>
        `;

        dom.recipeModal.style.display = 'flex';
    }

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
                <span class="material-symbols-outlined sfc-strategy-icon">tips_and_updates</span>
                <div class="sfc-strategy-text">
                    <strong>Фокус дня:</strong> ${escapeHtml(activeDay.focus)}
                </div>
            </div>

            <div class="sfc-day-slots-grid">
                ${renderMealSlot(state.activeDayIndex, 'Завтрак', 'breakfast', meals.breakfast)}
                ${renderMealSlot(state.activeDayIndex, 'Обед', 'lunch', meals.lunch)}
                ${renderMealSlot(state.activeDayIndex, 'Ужин', 'dinner', meals.dinner)}
                ${renderMealSlot(state.activeDayIndex, 'Перекус', 'snack', meals.snack)}
            </div>

            <div class="sfc-day-nutrition-summary">
                <div class="sfc-day-cals-total">
                    <span class="sfc-day-nut-label">Итого за день:</span>
                    <strong class="sfc-day-nut-val">${activeDay.nutrition.calories} ккал</strong>
                </div>
                <div class="sfc-day-macros-total">
                    <span>Белки: <strong>${activeDay.nutrition.protein}г</strong></span>
                    <span>Жиры: <strong>${activeDay.nutrition.fat}г</strong></span>
                    <span>Углеводы: <strong>${activeDay.nutrition.carbs}г</strong></span>
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
            ? `<span class="sfc-slot-state ready"><span class="material-symbols-outlined">check_circle</span> Из холодильника</span>`
            : `<span class="sfc-slot-state shop"><span class="material-symbols-outlined">shopping_basket</span> Докупить: ${(meal.missing_items || []).slice(0, 1).join(', ')}</span>`;

        return `
            <div class="sfc-slot-card">
                <div class="sfc-slot-head">
                    <span class="sfc-slot-tag">${label}</span>
                    ${statusHtml}
                </div>
                <div class="sfc-slot-img">
                    <img src="${meal.image}" alt="${escapeHtml(meal.title)}" loading="lazy" />
                </div>
                <h4 class="sfc-slot-title">${escapeHtml(meal.title)}</h4>
                <div class="sfc-slot-meta">
                    <span><span class="material-symbols-outlined">schedule</span> ${meal.time_mins} мин</span>
                    <span><span class="material-symbols-outlined">local_fire_department</span> ${meal.calories} ккал</span>
                </div>
                <div class="sfc-slot-actions">
                    <button type="button" class="sfc-btn-secondary sfc-btn-sm btn-open-slot-recipe" data-recipe-id="${meal.id}">
                        Рецепт
                    </button>
                    <button type="button" class="sfc-btn-subtle sfc-btn-sm btn-swap-slot" data-day="${dayIndex}" data-slot="${slotKey}" data-type="${label}">
                        Заменить
                    </button>
                </div>
            </div>
        `;
    }

    function openSwapModal(dayIndex, slotKey, mealType) {
        state.currentSwapSlot = { dayIndex, slotKey, mealType };
        dom.swapTitle.textContent = `Замена блюда: ${mealType} (${state.weeklyPlan[dayIndex].day_name})`;
        dom.swapSubtitle.textContent = 'Выберите блюдо только из имеющихся продуктов или с быстрой докупкой';
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
                ? '<span class="sfc-swap-badge is-ready"><span class="material-symbols-outlined">check_circle</span> Из холодильника</span>'
                : `<span class="sfc-swap-badge is-shop"><span class="material-symbols-outlined">shopping_basket</span> Докупить: ${(cand.missing_ingredients || []).slice(0, 2).join(', ')}</span>`;

            const item = document.createElement('div');
            item.className = 'sfc-swap-item';
            item.innerHTML = `
                <div class="sfc-swap-item-info">
                    <div class="sfc-swap-item-title">${escapeHtml(cand.title)}</div>
                    <div class="sfc-swap-item-meta">
                        ${tag}
                        <span><span class="material-symbols-outlined">schedule</span> ${cand.time_mins} мин</span>
                        <span><span class="material-symbols-outlined">local_fire_department</span> ${cand.calories} ккал</span>
                    </div>
                </div>
                <button type="button" class="sfc-btn-primary sfc-btn-sm btn-choose-swap">
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

    function renderShoppingList() {
        if (!state.shoppingList || state.shoppingList.length === 0) {
            dom.groupedShoppingContainer.innerHTML = `
                <div class="sfc-empty-placeholder" style="padding: 24px 16px;">
                    <div class="sfc-empty-icon-circle">
                        <span class="material-symbols-outlined sfc-empty-icon">task_alt</span>
                    </div>
                    <h3>Все продукты есть в наличии</h3>
                    <p class="sfc-subtext">Для составленного меню на неделю все ингредиенты уже найдены в вашем холодильнике.</p>
                </div>
            `;
            dom.shoppingRemainingCount.textContent = '0';
            return;
        }

        dom.shoppingRemainingCount.textContent = state.shoppingList.length;
        dom.groupedShoppingContainer.innerHTML = '';

        const catAisleIcons = {
            'Овощи и зелень': 'eco',
            'Фрукты и ягоды': 'nutrition',
            'Молочные продукты': 'egg',
            'Сыры': 'restaurant',
            'Мясо и птица': 'restaurant_menu',
            'Рыба и морепродукты': 'set_meal',
            'Бакалея и крупы': 'grain',
            'Бакалея и продукты': 'grain',
            'Хлеб и выпечка': 'bakery_dining',
            'Соусы и приправы': 'soup_kitchen',
            'Другое': 'shopping_basket'
        };

        for (const [category, items] of Object.entries(state.groupedShopping)) {
            const block = document.createElement('div');
            block.className = 'sfc-aisle-block';

            const aisleIcon = catAisleIcons[category] || 'shopping_basket';

            let itemsHtml = '';
            items.forEach(item => {
                itemsHtml += `
                    <div class="sfc-checklist-item" data-root="${item.root}">
                        <label class="sfc-checkbox-label">
                            <input type="checkbox" class="sfc-checkbox" id="chk_${item.root}" />
                            <span class="sfc-custom-checkbox"></span>
                            <span class="sfc-checklist-name">${escapeHtml(item.name)}</span>
                        </label>
                        <span class="sfc-checklist-qty">${escapeHtml(item.amount)}</span>
                    </div>
                `;
            });

            block.innerHTML = `
                <div class="sfc-aisle-header">
                    <span class="material-symbols-outlined sfc-aisle-icon">${aisleIcon}</span>
                    <h4>${escapeHtml(category)}</h4>
                    <span class="sfc-aisle-count">${items.length}</span>
                </div>
                <div class="sfc-aisle-items">${itemsHtml}</div>
            `;

            dom.groupedShoppingContainer.appendChild(block);
        }

        dom.groupedShoppingContainer.querySelectorAll('.sfc-checkbox').forEach(chk => {
            chk.addEventListener('change', () => {
                const parent = chk.closest('.sfc-checklist-item');
                if (parent) parent.classList.toggle('checked', chk.checked);
                updateRemainingShoppingCount();
            });
        });
    }

    function toggleAllShoppingCheckboxes() {
        const checkboxes = dom.groupedShoppingContainer.querySelectorAll('.sfc-checkbox');
        const anyUnchecked = Array.from(checkboxes).some(c => !c.checked);
        checkboxes.forEach(c => {
            c.checked = anyUnchecked;
            const item = c.closest('.sfc-checklist-item');
            if (item) item.classList.toggle('checked', anyUnchecked);
        });
        updateRemainingShoppingCount();
    }

    function updateRemainingShoppingCount() {
        const unchecked = dom.groupedShoppingContainer.querySelectorAll('.sfc-checkbox:not(:checked)');
        dom.shoppingRemainingCount.textContent = unchecked.length;
    }

    function showLocationPrompt() {
        if (dom.locationPrompt) dom.locationPrompt.style.display = 'flex';
        if (dom.recHero) dom.recHero.style.display = 'none';
        if (dom.storesList) {
            dom.storesList.innerHTML = `
                <div class="sfc-empty-placeholder" style="padding: 24px 12px;">
                    <div class="sfc-empty-icon-circle">
                        <span class="material-symbols-outlined sfc-empty-icon">near_me</span>
                    </div>
                    <h3>Ожидание местоположения</h3>
                    <p class="sfc-subtext">Разрешите доступ к геопозиции или укажите город выше, чтобы найти ближайшие магазины.</p>
                </div>
            `;
        }
    }

    function fetchStoresAndRecommendations(queryText) {
        if (!state.userLocation && !queryText) {
            showLocationPrompt();
            return;
        }

        if (dom.locationPrompt) dom.locationPrompt.style.display = 'none';

        const payload = {
            shopping_list: state.shoppingList
        };

        if (state.userLocation && state.userLocation.lat && state.userLocation.lon) {
            payload.lat = state.userLocation.lat;
            payload.lon = state.userLocation.lon;
        }

        if (queryText) {
            payload.query = queryText;
        }

        fetch(sfcConfig.restUrl + 'find-stores', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': sfcConfig.nonce },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(data => {
            if (data.user_location && data.user_location.lat && data.user_location.lon) {
                state.userLocation = data.user_location;
                if (state.map) {
                    state.map.setView([data.user_location.lat, data.user_location.lon], 15);
                }
            }

            if (data.city_name && !state.userCityName) {
                state.userCityName = data.city_name;
                if (dom.searchAddressInput) {
                    dom.searchAddressInput.value = data.city_name;
                }
            }

            if (data.success && data.stores && data.stores.length > 0) {
                state.stores = data.stores;
                state.recommendation = data.recommendation;
                renderStoreRecommendation(data.recommendation);
                renderStoresList(data.stores);
                updateMapMarkers();
            } else if (data.needs_location) {
                showLocationPrompt();
            } else {
                state.stores = [];
                state.recommendation = null;
                renderStoreRecommendation(null);
                renderStoresList([], data.message);
                updateMapMarkers();
            }
        })
        .catch(e => {
            console.error('Error fetching stores:', e);
            renderStoresList([], 'Не удалось загрузить данные с карты. Проверьте интернет-соединение.');
        });
    }

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

        if (dom.recEngineLabel) {
            dom.recEngineLabel.textContent = 'Оптимальный выбор';
        }

        dom.recTips.innerHTML = '';
        if (rec.tips && rec.tips.length > 0) {
            rec.tips.forEach(t => {
                const div = document.createElement('div');
                div.className = 'sfc-rec-tip-item';
                div.innerHTML = `<span class="material-symbols-outlined">check_circle</span> <span>${escapeHtml(t)}</span>`;
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

    function renderStoresList(stores, emptyMsg) {
        dom.storesList.innerHTML = '';
        if (!stores || stores.length === 0) {
            const msg = emptyMsg || 'В радиусе 3-5 км на карте не найдено продуктовых магазинов. Попробуйте указать другой адрес или район.';
            dom.storesList.innerHTML = `
                <div class="sfc-empty-placeholder" style="padding: 24px 12px;">
                    <div class="sfc-empty-icon-circle">
                        <span class="material-symbols-outlined sfc-empty-icon">search_off</span>
                    </div>
                    <h3>Магазины не найдены</h3>
                    <p class="sfc-subtext">${escapeHtml(msg)}</p>
                </div>
            `;
            return;
        }

        if (dom.storesSubtitle) {
            const locText = state.userCityName ? ` (${state.userCityName})` : '';
            dom.storesSubtitle.textContent = `Найдено на карте: ${stores.length}${locText}`;
        }

        stores.forEach(store => {
            const item = document.createElement('div');
            item.className = 'sfc-place-card';
            item.dataset.storeId = store.id;

            let tagsHtml = '';
            (store.features || []).forEach(f => {
                tagsHtml += `<span class="sfc-place-feature">• ${escapeHtml(f)}</span>`;
            });

            item.innerHTML = `
                <div class="sfc-place-head">
                    <span class="sfc-place-name">${escapeHtml(store.name)}</span>
                    <span class="sfc-place-dist">${store.distance_m} м</span>
                </div>
                <div class="sfc-place-addr">${escapeHtml(store.address || 'Адрес уточняется')} • ${escapeHtml(store.opening_hours || '08:00-23:00')}</div>
                <div class="sfc-place-features">${tagsHtml}</div>
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

        const userIcon = L.divIcon({
            className: 'sfc-user-pin',
            html: '<div style="background:#2D5E3B; color:#fff; width:28px; height:28px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:2px solid #fff; box-shadow:0 3px 8px rgba(0,0,0,0.25);"><span class="material-symbols-outlined" style="font-size:16px;">my_location</span></div>',
            iconSize: [28, 28],
            iconAnchor: [14, 14]
        });

        state.userMarker = L.marker([state.userLocation.lat, state.userLocation.lon], { icon: userIcon })
            .addTo(state.map)
            .bindPopup('<strong style="font-family:\'Plus Jakarta Sans\',sans-serif; font-size:13px;">Вы находитесь здесь</strong>');

        (state.stores || []).forEach(store => {
            const isRec = state.recommendation?.store?.id === store.id;
            const markerBg = isRec ? '#C85A32' : '#2D5E3B';

            const storeIcon = L.divIcon({
                className: 'sfc-store-pin',
                html: `<div style="background:${markerBg}; color:#fff; width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:2px solid #fff; box-shadow:0 3px 8px rgba(0,0,0,0.25);"><span class="material-symbols-outlined" style="font-size:18px;">storefront</span></div>`,
                iconSize: [32, 32],
                iconAnchor: [16, 16]
            });

            const popupContent = `
                <div style="font-family:\'Plus Jakarta Sans\',sans-serif; min-width:180px; padding:4px;">
                    <div style="font-family:\'Lora\',serif; font-size:15px; font-weight:600; margin-bottom:3px; color:#1F1A16;">${escapeHtml(store.name)}</div>
                    <div style="font-size:12px; color:#6B6156; margin-bottom:4px;">${escapeHtml(store.address || '')}</div>
                    <div style="font-size:12px; font-weight:600; color:#2D5E3B; margin-bottom:8px;">${store.distance_m} м (~${store.walking_time_mins} мин)</div>
                    <a href="https://yandex.ru/maps/?rtext=~${store.lat},${store.lon}&rtt=pd" target="_blank" style="display:inline-block; background:#2D5E3B; color:#fff; padding:5px 12px; border-radius:6px; font-size:12px; font-weight:500; text-decoration:none;">Маршрут</a>
                </div>
            `;

            const marker = L.marker([store.lat, store.lon], { icon: storeIcon, storeId: store.id })
                .addTo(state.map)
                .bindPopup(popupContent);

            if (isRec) marker.openPopup();
            state.mapMarkers.push(marker);
        });
    }

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

                    fetchStoresAndRecommendations(query);
                }
            })
            .catch(e => {
                console.warn('Direct geocoding error, falling back to server:', e);
                fetchStoresAndRecommendations(query);
            })
            .finally(() => {
                dom.btnSearchCity.innerHTML = '<span class="material-symbols-outlined">arrow_forward</span>';
            });
    }

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

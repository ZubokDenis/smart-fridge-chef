/**
 * Smart Fridge Chef - Application Logic
 * Clean Standard Architecture with Multi-Provider AI (Ollama / Claude / Gemini)
 */

(function () {
    'use strict';

    // Application State
    const state = {
        products: [],
        recipes: { all: [], ready_now: [], need_more: [] },
        weeklyPlan: [],
        shoppingList: [],
        groupedShopping: {},
        userLocation: { lat: sfcConfig.defaultLat || 55.7558, lon: sfcConfig.defaultLon || 37.6173 },
        stores: [],
        recommendation: null,
        activeDayIndex: 0,
        map: null,
        mapMarkers: [],
        userMarker: null,
        aiStatus: null,
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
            presetChips: document.querySelectorAll('.sfc-filter-chip'),
            btnDemo: document.getElementById('sfc-btn-demo'),
            btnReset: document.getElementById('sfc-btn-reset'),

            // Tab 2: Recipes
            recipesContainer: document.getElementById('sfc-recipes-container'),
            recipeFilters: document.querySelectorAll('.sfc-chip-item'),

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

            // Tab 5: Stores
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
        };
    }

    // Initialize Application
    document.addEventListener('DOMContentLoaded', function () {
        initDOM();
        bindEvents();
        loadAIStatus();

        // Load saved fridge products
        const saved = localStorage.getItem('sfc_products');
        if (saved) {
            try {
                state.products = JSON.parse(saved);
                renderProducts();
            } catch (e) {}
        }
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
                dom.aiStatusText.textContent = 'ИИ: ' + (sfcConfig.aiLabel || 'Локальный');
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
            sendAnalyzeRequest({ type: 'text', text: text });
        });

        // Presets
        dom.presetChips.forEach(chip => {
            chip.addEventListener('click', () => loadPreset(chip.dataset.preset));
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

        // Recipe filter pills
        dom.recipeFilters.forEach(pill => {
            pill.addEventListener('click', () => {
                dom.recipeFilters.forEach(p => p.classList.remove('active'));
                pill.classList.add('active');
                filterRecipes(pill.dataset.filter);
            });
        });

        // Modal close
        dom.modalCloseBtn.addEventListener('click', () => {
            dom.recipeModal.style.display = 'none';
        });

        window.addEventListener('click', (e) => {
            if (e.target === dom.recipeModal) dom.recipeModal.style.display = 'none';
            if (e.target === dom.addModal) dom.addModal.style.display = 'none';
        });

        // Save plan to WordPress
        dom.btnSavePlan.addEventListener('click', savePlanToWordPress);

        // Shopping Actions
        dom.btnCheckAll.addEventListener('click', toggleAllShoppingCheckboxes);
        dom.btnGoToStores.addEventListener('click', () => switchTab('tab-stores'));
        dom.btnOpenMapTeaser.addEventListener('click', () => switchTab('tab-stores'));

        // Store Geolocation & Search
        dom.btnGeolocate.addEventListener('click', requestUserGeolocation);
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
        }
    }

    // Process Image File
    function handleFileSelect(e) {
        if (e.target.files && e.target.files[0]) {
            processImageFile(e.target.files[0]);
        }
    }

    function processImageFile(file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            const base64Data = e.target.result;
            dom.previewImg.src = base64Data;
            dom.dropzone.style.display = 'none';
            dom.previewWrapper.style.display = 'block';
            dom.scanStatusText.innerHTML = '<span class="material-symbols-outlined sfc-spin">sync</span> <span>ИИ анализирует снимок...</span>';

            sendAnalyzeRequest({
                type: 'photo',
                image_base64: base64Data,
                mime_type: file.type || 'image/jpeg'
            });
        };
        reader.readAsDataURL(file);
    }

    function resetPhotoUpload() {
        dom.fileInput.value = '';
        dom.previewWrapper.style.display = 'none';
        dom.dropzone.style.display = 'block';
    }

    // REST: Analyze Input
    function sendAnalyzeRequest(payload) {
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
                    dom.scanStatusText.innerHTML = `<span class="material-symbols-outlined" style="color:#137333;">check_circle</span> <span>Распознано продуктов: ${data.products.length}</span>`;
                }
            } else {
                alert('Не удалось распознать продукты. Проверьте подключение к Ollama/Claude или введите текстом.');
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

            const freshnessTag = prod.perishable
                ? '<span class="sfc-fresh-tag sfc-tag-warning">Скоропортящееся</span>'
                : '<span class="sfc-fresh-tag sfc-tag-fresh">Длительное хранение</span>';

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
                    ${freshnessTag}
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
        });
    }

    function saveProductsState() {
        localStorage.setItem('sfc_products', JSON.stringify(state.products));
    }

    function resetAll() {
        if (confirm('Очистить холодильник и созданное меню?')) {
            state.products = [];
            state.recipes = { all: [], ready_now: [], need_more: [] };
            state.weeklyPlan = [];
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
            text = '4 яйца, 500 мл молока, кусок сыра 150г, сосиски 4 шт, пачка макарон, кетчуп, помидоры 2 шт, сливочное масло';
        } else if (presetKey === 'family') {
            text = 'Яйца куриные 10 шт, молоко 1 л, творог 400г, сыр российский 250г, куриное филе 600г, помидоры 4 шт, огурцы 3 шт, сметана 1 банка, картофель 1.5 кг, морковь 2 шт, лук 3 шт, макароны 500г, сливочное масло';
        } else if (presetKey === 'healthy') {
            text = 'Куриное филе 500г, творог 200г, овсянка 300г, яйца 6 шт, огурцы 4 шт, помидоры 3 шт, свежая зелень, яблоки 4 шт, гречка 400г';
        } else if (presetKey === 'minimal') {
            text = '3 яйца, молоко 200мл, сыр 80г, 1 помидор';
        }

        dom.textProducts.value = text;
        dom.btnModeText.click();
        sendAnalyzeRequest({ type: 'text', text: text });
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
                dom.badgeShopping.textContent = planData.total_to_buy;

                renderWeeklyPlan();
                renderShoppingList();
                fetchStoresAndRecommendations();
                switchTab('tab-recipes');
            }
        })
        .catch(err => {
            console.error('Error generating:', err);
            alert('Ошибка при генерации меню.');
        })
        .finally(() => {
            dom.btnStartCook.innerHTML = '<span>Рассчитать рецепты и меню</span> <span class="material-symbols-outlined">arrow_forward</span>';
            dom.btnStartCook.disabled = false;
        });
    }

    // Render Recipes (Instagram Feed style cards)
    function renderRecipesGrid(recipes) {
        dom.recipesContainer.innerHTML = '';

        if (!recipes || recipes.length === 0) {
            dom.recipesContainer.innerHTML = '<div class="sfc-empty-placeholder"><p>Подходящих рецептов не найдено. Добавьте больше базовых продуктов.</p></div>';
            return;
        }

        recipes.forEach(recipe => {
            const card = document.createElement('div');
            card.className = 'sfc-feed-card';

            const matchTag = recipe.can_cook_now
                ? '<span class="sfc-match-chip sfc-chip-green">100% В наличии</span>'
                : `<span class="sfc-match-chip sfc-chip-yellow">Совпадение ${recipe.match_score}%</span>`;

            let ingHtml = '';
            recipe.ingredients_status.slice(0, 3).forEach(ing => {
                const color = ing.available ? '#137333' : '#d93025';
                const mark = ing.available ? '✓' : '✕';
                ingHtml += `<span class="sfc-ing-pill" style="color:${color};">${mark} ${escapeHtml(ing.name)}</span>`;
            });

            card.innerHTML = `
                <div class="sfc-feed-img-box">
                    <img src="${recipe.image}" alt="${escapeHtml(recipe.title)}" loading="lazy" />
                    ${matchTag}
                </div>
                <div class="sfc-feed-body">
                    <span class="sfc-feed-category">${escapeHtml(recipe.meal_type || 'Блюдо')}</span>
                    <h3 class="sfc-feed-title">${escapeHtml(recipe.title)}</h3>
                    <div class="sfc-feed-meta">
                        <span><span class="material-symbols-outlined">schedule</span> ${recipe.time_mins} мин</span>
                        <span><span class="material-symbols-outlined">local_fire_department</span> ${recipe.calories} ккал</span>
                    </div>
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
        recipe.ingredients_status.forEach(ing => {
            const mark = ing.available
                ? '<span class="material-symbols-outlined" style="color:#137333; font-size:18px;">check_circle</span>'
                : '<span class="material-symbols-outlined" style="color:#d93025; font-size:18px;">cancel</span>';
            const statusLabel = ing.available
                ? '<span style="color:#137333; font-size:12px; margin-left:6px;">в наличии</span>'
                : '<span style="color:#d93025; font-size:12px; margin-left:6px;">докупить</span>';

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
        recipe.instructions.forEach((step, idx) => {
            stepHtml += `<li style="margin-bottom:12px; line-height:1.5; color:#202124;">
                <strong style="color:#1a73e8;">Шаг ${idx + 1}:</strong> ${escapeHtml(step)}
            </li>`;
        });

        dom.modalContent.innerHTML = `
            <div style="margin-bottom: 20px;">
                <span class="sfc-qty-tag" style="margin-bottom: 8px; display:inline-block;">${escapeHtml(recipe.meal_type)}</span>
                <h2 style="margin: 4px 0 8px; font-size: 20px; font-weight:700;">${escapeHtml(recipe.title)}</h2>
                <p style="color: #5f6368; font-size: 14px; margin:0 0 16px;">${escapeHtml(recipe.description || '')}</p>
                <div style="display:flex; gap:16px; background:#f8f9fa; border:1px solid #dadce0; border-radius:8px; padding:12px 16px; font-size:13px; font-weight:500;">
                    <span>Время: <strong>${recipe.time_mins} мин</strong></span>
                    <span>Калории: <strong>${recipe.calories} ккал</strong></span>
                    <span>Б: <strong>${recipe.protein}г</strong></span>
                    <span>Ж: <strong>${recipe.fat}г</strong></span>
                    <span>У: <strong>${recipe.carbs}г</strong></span>
                </div>
            </div>

            <div style="margin-bottom: 22px;">
                <h3 style="font-size: 15px; margin: 0 0 8px; color:#202124;">Необходимые ингредиенты</h3>
                <ul style="list-style:none; padding:0; margin:0;">
                    ${ingHtml}
                </ul>
            </div>

            <div style="margin-bottom: 24px;">
                <h3 style="font-size: 15px; margin: 0 0 10px; color:#202124;">Инструкция приготовления</h3>
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

    // Render Weekly Plan (Google Calendar Style)
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
                <span><strong>План на день:</strong> ${escapeHtml(activeDay.focus)}</span>
            </div>

            <div class="sfc-day-slots-grid">
                ${renderMealSlot('Завтрак', meals.breakfast)}
                ${renderMealSlot('Обед', meals.lunch)}
                ${renderMealSlot('Ужин', meals.dinner)}
                ${renderMealSlot('Перекус', meals.snack)}
            </div>

            <div style="margin-top:16px; background:#ffffff; border:1px solid #dadce0; border-radius:8px; padding:12px 18px; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; font-size:13px;">
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
    }

    function renderMealSlot(label, meal) {
        if (!meal) return '';
        return `
            <div class="sfc-slot-card">
                <span class="sfc-slot-tag">${label}</span>
                <div class="sfc-slot-img">
                    <img src="${meal.image}" alt="${escapeHtml(meal.title)}" />
                </div>
                <h4>${escapeHtml(meal.title)}</h4>
                <div class="sfc-slot-meta">
                    <span>${meal.time_mins} мин</span> • <span>${meal.calories} ккал</span>
                </div>
                <button type="button" class="sfc-btn-m3 sfc-btn-outlined sfc-btn-sm btn-open-slot-recipe" data-recipe-id="${meal.id}" style="margin-top:auto;">
                    Посмотреть
                </button>
            </div>
        `;
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
                alert('✓ ' + data.message);
            } else {
                alert('Не удалось сохранить план.');
            }
        })
        .catch(e => {
            console.error('Error saving plan:', e);
            alert('Ошибка сервера при сохранении.');
        })
        .finally(() => {
            dom.btnSavePlan.innerHTML = '<span class="material-symbols-outlined">bookmark</span> <span>Сохранить в WordPress</span>';
            dom.btnSavePlan.disabled = false;
        });
    }

    // Render Shopping List (Google Keep Style)
    function renderShoppingList() {
        if (!state.shoppingList || state.shoppingList.length === 0) {
            dom.groupedShoppingContainer.innerHTML = '<p class="sfc-subtext">Все необходимые продукты уже есть в холодильнике.</p>';
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

    // Fetch Stores & AI Recommendations
    function fetchStoresAndRecommendations() {
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
            if (data.success) {
                state.stores = data.stores;
                state.recommendation = data.recommendation;
                renderStoreRecommendation(data.recommendation);
                renderStoresList(data.stores);
                updateMapMarkers();
            }
        })
        .catch(e => console.error('Error fetching stores:', e));
    }

    // Render Store Recommendation Card
    function renderStoreRecommendation(rec) {
        if (!rec || !rec.store) return;
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

    // Render Stores List (Google Places Cards)
    function renderStoresList(stores) {
        dom.storesList.innerHTML = '';
        if (!stores || stores.length === 0) {
            dom.storesList.innerHTML = '<p class="sfc-subtext">Супермаркеты не найдены.</p>';
            return;
        }

        stores.forEach(store => {
            const item = document.createElement('div');
            item.className = 'sfc-place-card';
            item.dataset.storeId = store.id;

            let tagsHtml = '';
            (store.features || []).forEach(f => {
                tagsHtml += `<span class="sfc-tag-sm">${escapeHtml(f)}</span>`;
            });

            item.innerHTML = `
                <div class="sfc-place-head">
                    <span>${escapeHtml(store.name)}</span>
                    <span style="color:#137333; font-weight:600;">${store.distance_m} м</span>
                </div>
                <div class="sfc-place-addr">${escapeHtml(store.address || 'Адрес уточняется')} • ${escapeHtml(store.opening_hours || '08:00-23:00')}</div>
                <div class="sfc-place-tags">${tagsHtml}</div>
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

    // Leaflet Map (Google Maps Style)
    function initOrRefreshMap() {
        if (!dom.mapContainer) return;

        if (!state.map) {
            state.map = L.map(dom.mapContainer).setView([state.userLocation.lat, state.userLocation.lon], 15);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; OpenStreetMap'
            }).addTo(state.map);
        } else {
            state.map.invalidateSize();
            state.map.setView([state.userLocation.lat, state.userLocation.lon]);
        }

        updateMapMarkers();
    }

    function updateMapMarkers() {
        if (!state.map) return;

        state.mapMarkers.forEach(m => state.map.removeLayer(m));
        state.mapMarkers = [];
        if (state.userMarker) state.map.removeLayer(state.userMarker);

        // User Marker (Google Blue Pin)
        const userIcon = L.divIcon({
            className: 'sfc-user-pin',
            html: '<div style="background:#1a73e8; color:#fff; width:26px; height:26px; border-radius:50%; display:flex; align-items:center; justify-content:center; border:2px solid #fff; box-shadow:0 2px 6px rgba(0,0,0,0.3); font-size:12px;"><span class="material-symbols-outlined" style="font-size:16px;">person</span></div>',
            iconSize: [26, 26],
            iconAnchor: [13, 13]
        });

        state.userMarker = L.marker([state.userLocation.lat, state.userLocation.lon], { icon: userIcon })
            .addTo(state.map)
            .bindPopup('<strong>Вы здесь</strong>');

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

    function requestUserGeolocation() {
        if (!navigator.geolocation) {
            alert('Геолокация не поддерживается вашим браузером.');
            return;
        }

        dom.btnGeolocate.innerHTML = '<span class="material-symbols-outlined sfc-spin">sync</span> <span>Поиск...</span>';

        navigator.geolocation.getCurrentPosition(
            (pos) => {
                state.userLocation.lat = pos.coords.latitude;
                state.userLocation.lon = pos.coords.longitude;
                dom.btnGeolocate.innerHTML = '<span class="material-symbols-outlined">my_location</span> <span>Мое местоположение</span>';

                if (state.map) {
                    state.map.setView([state.userLocation.lat, state.userLocation.lon], 15);
                }
                fetchStoresAndRecommendations();
            },
            (err) => {
                console.warn('Geolocation denied or failed:', err);
                dom.btnGeolocate.innerHTML = '<span class="material-symbols-outlined">my_location</span> <span>Мое местоположение</span>';
                alert('Не удалось определить GPS координаты. Введите город в строке поиска.');
            },
            { timeout: 10000, enableHighAccuracy: true }
        );
    }

    function searchCityAddress() {
        const query = dom.searchAddressInput.value.trim();
        if (!query) return;

        dom.btnSearchCity.innerHTML = '<span class="material-symbols-outlined sfc-spin">sync</span>';

        fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1`)
            .then(r => r.json())
            .then(data => {
                if (data && data.length > 0) {
                    state.userLocation.lat = parseFloat(data[0].lat);
                    state.userLocation.lon = parseFloat(data[0].lon);

                    if (state.map) {
                        state.map.setView([state.userLocation.lat, state.userLocation.lon], 15);
                    }
                    fetchStoresAndRecommendations();
                } else {
                    alert('Адрес не найден.');
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

<?php
/**
 * Smart Fridge Chef - Clean Standard UI (Google Material 3 & Instagram Web Design)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div id="smart-fridge-app" class="sfc-app-root">

    <!-- Top Navigation Bar (Google / Instagram Web standard) -->
    <header class="sfc-topbar">
        <div class="sfc-topbar-inner">
            <div class="sfc-topbar-left">
                <span class="material-symbols-outlined sfc-logo-glyph">kitchen</span>
                <div class="sfc-title-group">
                    <h1 class="sfc-app-title">Холодильник & Меню</h1>
                    <span class="sfc-app-tagline">Умный ассистент питания и покупок</span>
                </div>
            </div>

            <div class="sfc-topbar-right">
                <!-- Live AI Engine Indicator -->
                <div class="sfc-ai-status-pill" id="sfc-ai-status-badge" title="Активный движок ИИ">
                    <span class="sfc-ai-dot"></span>
                    <span id="sfc-ai-status-text">ИИ: Загрузка...</span>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-fridge-chef' ) ); ?>" class="sfc-ai-config-link" title="Настроить Ollama / Claude">
                        <span class="material-symbols-outlined">settings</span>
                    </a>
                </div>

                <button type="button" id="sfc-btn-demo" class="sfc-btn-text" title="Заполнить готовым примером">
                    <span class="material-symbols-outlined">magic_button</span>
                    <span>Пример</span>
                </button>
                <button type="button" id="sfc-btn-reset" class="sfc-btn-icon" title="Очистить все данные">
                    <span class="material-symbols-outlined">restart_alt</span>
                </button>
            </div>
        </div>

        <!-- Instagram / Material Segmented Navigation Bar -->
        <nav class="sfc-nav-tabs" id="sfc-main-tabs" role="tablist">
            <button class="sfc-nav-item active" data-tab="tab-fridge" role="tab">
                <span class="material-symbols-outlined">inventory_2</span>
                <span>1. Холодильник</span>
                <span class="sfc-counter" id="sfc-badge-products-count">0</span>
            </button>
            <button class="sfc-nav-item" data-tab="tab-recipes" role="tab">
                <span class="material-symbols-outlined">restaurant</span>
                <span>2. Рецепты</span>
                <span class="sfc-counter" id="sfc-badge-recipes-count">0</span>
            </button>
            <button class="sfc-nav-item" data-tab="tab-plan" role="tab">
                <span class="material-symbols-outlined">calendar_month</span>
                <span>3. План на неделю</span>
            </button>
            <button class="sfc-nav-item" data-tab="tab-shopping" role="tab">
                <span class="material-symbols-outlined">shopping_cart</span>
                <span>4. Список покупок</span>
                <span class="sfc-counter sfc-counter-alert" id="sfc-badge-shopping-count">0</span>
            </button>
            <button class="sfc-nav-item" data-tab="tab-stores" role="tab">
                <span class="material-symbols-outlined">storefront</span>
                <span>5. Магазины</span>
            </button>
        </nav>
    </header>

    <!-- Main Container -->
    <main class="sfc-main-content">

        <!-- ================= TAB 1: FRIDGE INPUT & INVENTORY ================= -->
        <section id="tab-fridge" class="sfc-view active">
            <div class="sfc-split-layout">

                <!-- Left Column: Input Form (Google Drive / Instagram Upload style) -->
                <div class="sfc-panel">
                    <div class="sfc-panel-header">
                        <h2>Ввод продуктов</h2>
                        <p class="sfc-subtext">Загрузите фотографию или введите продукты списком через запятую</p>
                    </div>

                    <!-- Material Segmented Button -->
                    <div class="sfc-segmented-control">
                        <button type="button" class="sfc-segment active" id="btn-mode-photo">
                            <span class="material-symbols-outlined">add_a_photo</span>
                            <span>Фото холодильника</span>
                        </button>
                        <button type="button" class="sfc-segment" id="btn-mode-text">
                            <span class="material-symbols-outlined">edit_note</span>
                            <span>Текстовый список</span>
                        </button>
                    </div>

                    <!-- Photo Upload Dropzone -->
                    <div id="sfc-photo-area" class="sfc-upload-container">
                        <input type="file" id="sfc-file-input" accept="image/*" class="sfc-hidden-input" />
                        <div class="sfc-dropzone" id="sfc-dropzone">
                            <div class="sfc-dropzone-icon">
                                <span class="material-symbols-outlined">cloud_upload</span>
                            </div>
                            <div class="sfc-dropzone-label">
                                <strong>Перетащите фотографию сюда</strong>
                                <span>или нажмите кнопку для выбора файла</span>
                            </div>
                            <div class="sfc-dropzone-actions">
                                <button type="button" class="sfc-btn-m3 sfc-btn-primary" id="sfc-btn-choose-file">
                                    <span class="material-symbols-outlined">image</span>
                                    <span>Выбрать снимок</span>
                                </button>
                                <button type="button" class="sfc-btn-m3 sfc-btn-tonal" id="sfc-btn-camera">
                                    <span class="material-symbols-outlined">photo_camera</span>
                                    <span>Камера</span>
                                </button>
                            </div>
                        </div>

                        <!-- Clean Image Preview with Progress Bar -->
                        <div id="sfc-image-preview-wrapper" class="sfc-preview-box" style="display: none;">
                            <div class="sfc-preview-img-container">
                                <img id="sfc-image-preview" src="" alt="Фотография холодильника" />
                                <div class="sfc-linear-progress" id="sfc-progress-bar">
                                    <div class="sfc-linear-progress-bar"></div>
                                </div>
                            </div>
                            <div class="sfc-preview-footer">
                                <div class="sfc-preview-status" id="sfc-scan-status-text">
                                    <span class="material-symbols-outlined sfc-spin">sync</span>
                                    <span>ИИ анализирует снимок и подбирает рецепты...</span>
                                </div>
                                <button type="button" class="sfc-btn-m3 sfc-btn-text sfc-btn-danger" id="sfc-btn-remove-photo">
                                    <span class="material-symbols-outlined">delete</span>
                                    <span>Сменить снимок</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Text Input Box -->
                    <div id="sfc-text-area" class="sfc-text-container" style="display: none;">
                        <div class="sfc-textarea-wrapper">
                            <textarea id="sfc-text-products" rows="4" class="sfc-textarea-m3" placeholder="Например: 4 яйца, 500 мл молока, сыр 200г, куриное филе 400г, сметана, 2 помидора, пачка макарон, картофель..."></textarea>
                        </div>
                        <div class="sfc-text-footer">
                            <button type="button" class="sfc-btn-m3 sfc-btn-primary" id="sfc-btn-parse-text">
                                <span class="material-symbols-outlined">auto_awesome</span>
                                <span>Распознать через ИИ</span>
                            </button>
                        </div>
                    </div>

                    <!-- Quick Preset Filter Chips -->
                    <div class="sfc-chip-group">
                        <span class="sfc-chip-label">Быстрые шаблоны:</span>
                        <div class="sfc-chips-list">
                            <button type="button" class="sfc-filter-chip" data-preset="bachelor">Холостяцкий</button>
                            <button type="button" class="sfc-filter-chip" data-preset="family">Семейный</button>
                            <button type="button" class="sfc-filter-chip" data-preset="healthy">ЗОЖ / Спорт</button>
                            <button type="button" class="sfc-filter-chip" data-preset="minimal">Минимум</button>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Products Inventory (Google Sheets / Material Table) -->
                <div class="sfc-panel">
                    <div class="sfc-panel-header sfc-flex-between">
                        <div>
                            <h2>Распознанные продукты</h2>
                            <p class="sfc-subtext">Ингредиенты в наличии, готовые к расчету рациона</p>
                        </div>
                        <button type="button" class="sfc-btn-m3 sfc-btn-outlined sfc-btn-sm" id="sfc-btn-add-manual-product">
                            <span class="material-symbols-outlined">add</span>
                            <span>Добавить</span>
                        </button>
                    </div>

                    <!-- Empty State -->
                    <div id="sfc-products-empty" class="sfc-empty-placeholder">
                        <span class="material-symbols-outlined sfc-empty-icon">kitchen</span>
                        <h3>Холодильник пуст</h3>
                        <p>Загрузите фотографию или введите продукты списком, чтобы сформировать инвентарь.</p>
                    </div>

                    <!-- Product Table -->
                    <div id="sfc-products-list-wrapper" style="display: none;">
                        <div class="sfc-table-container">
                            <table class="sfc-data-table" id="sfc-products-table">
                                <thead>
                                    <tr>
                                        <th>Продукт</th>
                                        <th>Объем</th>
                                        <th>Категория</th>
                                        <th>Срок хранения</th>
                                        <th style="width: 40px;"></th>
                                    </tr>
                                </thead>
                                <tbody id="sfc-products-tbody">
                                    <!-- Populated by JS -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Bottom Calculation Bar -->
                        <div class="sfc-bottom-cta">
                            <div class="sfc-cta-info">
                                <strong>Готово к расчету меню</strong>
                                <span>Шеф рассчитает блюда и оптимизирует закупки на неделю</span>
                            </div>
                            <button type="button" class="sfc-btn-m3 sfc-btn-primary sfc-btn-lg" id="sfc-btn-start-cook">
                                <span>Рассчитать рецепты и меню</span>
                                <span class="material-symbols-outlined">arrow_forward</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================= TAB 2: RECIPES (Instagram Feed Cards) ================= -->
        <section id="tab-recipes" class="sfc-view">
            <div class="sfc-view-header">
                <div>
                    <h2>Что можно приготовить</h2>
                    <p class="sfc-subtext">Блюда, сформированные на основе продуктов вашего холодильника</p>
                </div>
                <div class="sfc-filter-chips" id="sfc-recipe-filters">
                    <button type="button" class="sfc-chip-item active" data-filter="all">Все варианты</button>
                    <button type="button" class="sfc-chip-item" data-filter="ready">Только из холодильника</button>
                    <button type="button" class="sfc-chip-item" data-filter="need_more">С быстрой докупкой</button>
                </div>
            </div>

            <!-- Recipes Grid -->
            <div class="sfc-recipe-grid" id="sfc-recipes-container">
                <!-- Rendered dynamically -->
            </div>
        </section>

        <!-- ================= TAB 3: 7-DAY MEAL PLAN (Google Calendar style) ================= -->
        <section id="tab-plan" class="sfc-view">
            <div class="sfc-view-header sfc-flex-between">
                <div>
                    <h2>План питания на 7 дней</h2>
                    <p class="sfc-subtext">Сбалансированный рацион с возможностью замены блюд и вариативностью докупки</p>
                </div>
                <div class="sfc-header-button-group">
                    <button type="button" class="sfc-btn-m3 sfc-btn-outlined" id="sfc-btn-save-plan">
                        <span class="material-symbols-outlined">bookmark</span>
                        <span>Сохранить в WordPress</span>
                    </button>
                    <button type="button" class="sfc-btn-m3 sfc-btn-outlined" onclick="window.print()">
                        <span class="material-symbols-outlined">print</span>
                        <span>Печать</span>
                    </button>
                </div>
            </div>

            <!-- Day Selector Tabs (Calendar style) -->
            <div class="sfc-calendar-strip" id="sfc-days-nav">
                <!-- Day buttons populated by JS -->
            </div>

            <!-- Day View Container -->
            <div id="sfc-day-view-container">
                <!-- Rendered by JS -->
            </div>
        </section>

        <!-- ================= TAB 4: SHOPPING LIST (Google Keep style) ================= -->
        <section id="tab-shopping" class="sfc-view">
            <div class="sfc-view-header sfc-flex-between">
                <div>
                    <h2>Список покупок</h2>
                    <p class="sfc-subtext">Автоматически рассчитанная разница: необходимые продукты для меню за вычетом содержимого холодильника</p>
                </div>
                <button type="button" class="sfc-btn-m3 sfc-btn-primary" id="sfc-btn-go-to-stores">
                    <span class="material-symbols-outlined">near_me</span>
                    <span>Найти магазины рядом</span>
                </button>
            </div>

            <div class="sfc-shopping-layout">
                <!-- Left: Shopping Checklist -->
                <div class="sfc-panel sfc-checklist-panel">
                    <div class="sfc-checklist-toolbar">
                        <span>Осталось купить: <strong id="sfc-shopping-remaining-count">0</strong></span>
                        <button type="button" class="sfc-btn-m3 sfc-btn-text sfc-btn-sm" id="sfc-btn-check-all">
                            Выбрать всё
                        </button>
                    </div>
                    <div id="sfc-grouped-shopping-container">
                        <!-- Populated by JS -->
                    </div>
                </div>

                <!-- Right: Smart Assistant Sidebar -->
                <div class="sfc-panel sfc-sidebar-panel">
                    <div class="sfc-sidebar-card">
                        <div class="sfc-sidebar-card-title">
                            <span class="material-symbols-outlined">lightbulb</span>
                            <span>Рекомендация по закупкам</span>
                        </div>
                        <p class="sfc-sidebar-desc" id="sfc-quick-store-teaser">
                            Шеф анализирует список покупок и определяет оптимальный супермаркет...
                        </p>
                        <button type="button" class="sfc-btn-m3 sfc-btn-tonal sfc-btn-block" id="sfc-btn-open-map-teaser">
                            Посмотреть на карте
                        </button>
                    </div>

                    <div class="sfc-sidebar-hints">
                        <h4>Советы по покупкам:</h4>
                        <ul>
                            <li><strong>Свежие овощи и зелень:</strong> лучше брать в начале недели в специализированных магазинах.</li>
                            <li><strong>Базовые крупы и мука:</strong> проверяйте нижние полки в супермаркетах для экономии до 30%.</li>
                            <li><strong>Охлажденное мясо и рыба:</strong> всегда проверяйте герметичность упаковки и дату фасовки.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================= TAB 5: STORES & MAP (Google Maps style) ================= -->
        <section id="tab-stores" class="sfc-view">
            <div class="sfc-view-header">
                <div>
                    <h2>Поиск магазинов и ИИ-рекомендации</h2>
                    <p class="sfc-subtext">Анализ супермаркетов на основе искусственного интеллекта под ваш текущий список продуктов</p>
                </div>
            </div>

            <!-- Recommendation Place Card (Google Maps style) -->
            <div id="sfc-recommendation-hero" class="sfc-rec-card">
                <div class="sfc-rec-card-header">
                    <div class="sfc-rec-badge">
                        <span class="material-symbols-outlined sfc-rec-sparkle">verified</span>
                        <span>Выбор ИИ под ваш список</span>
                    </div>
                    <span class="sfc-rec-engine-tag" id="sfc-rec-engine-label">Ollama / Claude</span>
                </div>

                <div class="sfc-rec-content">
                    <div class="sfc-rec-primary">
                        <div class="sfc-rec-title-row">
                            <h3 id="sfc-rec-title">Супермаркет</h3>
                            <span class="sfc-rec-dist-chip" id="sfc-rec-distance">-- м</span>
                        </div>
                        <p class="sfc-rec-reason" id="sfc-rec-reason">Загрузка кулинарного анализа...</p>
                        
                        <div class="sfc-rec-metadata">
                            <span id="sfc-rec-time"><span class="material-symbols-outlined">directions_walk</span> ~-- мин</span>
                            <span id="sfc-rec-hours"><span class="material-symbols-outlined">schedule</span> --</span>
                        </div>

                        <div class="sfc-rec-tips-box" id="sfc-rec-tips">
                            <!-- Populated by JS -->
                        </div>
                    </div>

                    <div class="sfc-rec-actions">
                        <a id="sfc-rec-route-yandex" href="#" target="_blank" class="sfc-btn-m3 sfc-btn-primary">
                            <span class="material-symbols-outlined">navigation</span>
                            <span>Маршрут в Яндекс Карты</span>
                        </a>
                        <a id="sfc-rec-route-google" href="#" target="_blank" class="sfc-btn-m3 sfc-btn-outlined">
                            <span class="material-symbols-outlined">map</span>
                            <span>Google Карты</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Google Maps Split Layout -->
            <div class="sfc-maps-layout">
                <!-- Map Container -->
                <div class="sfc-panel sfc-map-wrapper">
                    <div class="sfc-map-searchbar">
                        <div class="sfc-search-input-box">
                            <span class="material-symbols-outlined sfc-search-icon">search</span>
                            <input type="text" id="sfc-search-address" placeholder="Город или точный адрес..." value="Москва" />
                            <button type="button" id="sfc-btn-search-city" class="sfc-search-btn" title="Найти">
                                <span class="material-symbols-outlined">arrow_forward</span>
                            </button>
                        </div>
                        <button type="button" class="sfc-btn-m3 sfc-btn-outlined sfc-btn-sm" id="sfc-btn-geolocate">
                            <span class="material-symbols-outlined">my_location</span>
                            <span>Мое местоположение</span>
                        </button>
                    </div>
                    <div id="sfc-map" class="sfc-leaflet-map"></div>
                </div>

                <!-- Stores List (Google Places Cards) -->
                <div class="sfc-panel sfc-places-wrapper">
                    <div class="sfc-panel-header">
                        <h3>Магазины рядом</h3>
                        <p class="sfc-subtext">Нажмите для отображения на карте</p>
                    </div>
                    <div class="sfc-places-list" id="sfc-stores-list">
                        <!-- Populated by JS -->
                    </div>
                </div>
            </div>
        </section>

    </main>

    <!-- Modal: Recipe Details (Instagram dialog style) -->
    <div id="sfc-recipe-modal" class="sfc-modal-scrim" style="display: none;">
        <div class="sfc-modal-sheet">
            <button type="button" class="sfc-modal-close" id="sfc-modal-close-btn">
                <span class="material-symbols-outlined">close</span>
            </button>
            <div id="sfc-modal-content">
                <!-- Populated dynamically -->
            </div>
        </div>
    </div>

    <!-- Modal: Swap Meal Alternative -->
    <div id="sfc-swap-modal" class="sfc-modal-scrim" style="display: none;">
        <div class="sfc-modal-sheet">
            <button type="button" class="sfc-modal-close" id="sfc-swap-modal-close">
                <span class="material-symbols-outlined">close</span>
            </button>
            <div class="sfc-modal-title">
                <h3 id="sfc-swap-modal-title">Выбор альтернативного блюда</h3>
                <p class="sfc-subtext" id="sfc-swap-modal-subtitle">Выберите блюдо из холодильника или с минимальной докупкой</p>
            </div>
            <div id="sfc-swap-list" class="sfc-swap-options-list">
                <!-- Populated dynamically by JS -->
            </div>
        </div>
    </div>

    <!-- Modal: Manual Add Product -->
    <div id="sfc-add-modal" class="sfc-modal-scrim" style="display: none;">
        <div class="sfc-modal-sheet sfc-modal-compact">
            <button type="button" class="sfc-modal-close" id="sfc-add-modal-close">
                <span class="material-symbols-outlined">close</span>
            </button>
            <div class="sfc-modal-title">
                <h3>Добавить продукт</h3>
            </div>
            <form id="sfc-manual-add-form" class="sfc-form-m3">
                <div class="sfc-form-field">
                    <label for="sfc-add-name">Название продукта</label>
                    <input type="text" id="sfc-add-name" required placeholder="Например: Творог 5%" />
                </div>
                <div class="sfc-form-field">
                    <label for="sfc-add-qty">Количество</label>
                    <input type="text" id="sfc-add-qty" placeholder="Например: 200 г, 1 пачка" value="1 шт" />
                </div>
                <div class="sfc-modal-actions">
                    <button type="button" class="sfc-btn-m3 sfc-btn-text" onclick="document.getElementById('sfc-add-modal').style.display='none';">Отмена</button>
                    <button type="submit" class="sfc-btn-m3 sfc-btn-primary">Добавить в инвентарь</button>
                </div>
            </form>
        </div>
    </div>

</div>

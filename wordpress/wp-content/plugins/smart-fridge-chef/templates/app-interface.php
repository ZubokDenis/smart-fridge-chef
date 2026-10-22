<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<div id="smart-fridge-app" class="sfc-app-root">

    <header class="sfc-topbar">
        <div class="sfc-topbar-inner">
            <div class="sfc-topbar-left">
                <h1 class="sfc-app-title">Умный Шеф</h1>
                <span class="sfc-app-tagline">Планирование рациона, каталог рецептов и закупки</span>
            </div>

            <div class="sfc-topbar-right">
                <button type="button" id="sfc-btn-demo" class="sfc-btn-secondary" title="Заполнить готовым набором продуктов">
                    <svg class="sfc-svg-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v6m3-3h-6M3 6h18M3 12h12M3 18h9"/></svg>
                    <span>Пример продуктов</span>
                </button>
                <button type="button" id="sfc-btn-reset" class="sfc-btn-secondary" title="Очистить все данные">
                    <svg class="sfc-svg-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2M10 11v6M14 11v6"/></svg>
                    <span>Очистить</span>
                </button>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-fridge-chef' ) ); ?>" class="sfc-btn-secondary" title="Настройки подключения" id="sfc-ai-status-badge">
                    <svg class="sfc-svg-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><line x1="1" y1="14" x2="7" y2="14"/><line x1="9" y1="8" x2="15" y2="8"/><line x1="17" y1="16" x2="23" y2="16"/></svg>
                    <span>Настройки</span>
                </a>
                <span id="sfc-ai-status-text" style="display:none;"></span>
            </div>
        </div>

        <nav class="sfc-nav-tabs" id="sfc-main-tabs" role="tablist">
            <button class="sfc-nav-item active" data-tab="tab-fridge" role="tab">
                <span>Холодильник</span>
                <span class="sfc-counter" id="sfc-badge-products-count">0</span>
            </button>
            <button class="sfc-nav-item" data-tab="tab-recipes" role="tab">
                <span>Рецепты</span>
                <span class="sfc-counter" id="sfc-badge-recipes-count">0</span>
            </button>
            <button class="sfc-nav-item" data-tab="tab-plan" role="tab">
                <span>План на неделю</span>
            </button>
            <button class="sfc-nav-item" data-tab="tab-shopping" role="tab">
                <span>Список покупок</span>
                <span class="sfc-counter" id="sfc-badge-shopping-count">0</span>
            </button>
            <button class="sfc-nav-item" data-tab="tab-stores" role="tab">
                <span>Магазины рядом</span>
            </button>
        </nav>
    </header>

    <main class="sfc-main-content">

        <section id="tab-fridge" class="sfc-view active">
            <div class="sfc-split-layout">

                <div class="sfc-panel sfc-panel-input">
                    <div class="sfc-panel-header">
                        <h2>Что у вас в холодильнике?</h2>
                        <p class="sfc-subtext">Сфотографируйте полки с продуктами или добавьте список текстом</p>
                    </div>

                    <div class="sfc-segmented-control">
                        <button type="button" class="sfc-segment active" id="btn-mode-photo">
                            <span>Фото полок</span>
                        </button>
                        <button type="button" class="sfc-segment" id="btn-mode-text">
                            <span>Текстовый список</span>
                        </button>
                    </div>

                    <div id="sfc-photo-area" class="sfc-upload-container">
                        <input type="file" id="sfc-file-input" accept="image/*" class="sfc-hidden-input" />
                        <div class="sfc-dropzone" id="sfc-dropzone">
                            <div class="sfc-dropzone-icon">
                                <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="4" y1="10" x2="20" y2="10"/><line x1="8" y1="6" x2="8" y2="8"/><line x1="8" y1="14" x2="8" y2="17"/></svg>
                            </div>
                            <div class="sfc-dropzone-label">
                                <strong>Сфотографируйте холодильник, остальное мы возьмём на себя</strong>
                                <span>Снимок полок или продуктов на столе (JPG, PNG, WebP)</span>
                            </div>
                            <div class="sfc-dropzone-actions">
                                <button type="button" class="sfc-btn-primary" id="sfc-btn-choose-file">
                                    <svg class="sfc-svg-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
                                    <span>Выбрать фото</span>
                                </button>
                                <button type="button" class="sfc-btn-secondary" id="sfc-btn-camera">
                                    <svg class="sfc-svg-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                                    <span>С камеры</span>
                                </button>
                            </div>
                        </div>

                        <div id="sfc-image-preview-wrapper" class="sfc-preview-box" style="display: none;">
                            <div class="sfc-preview-img-container">
                                <img id="sfc-image-preview" src="" alt="Фотография продуктов" />
                                <div class="sfc-linear-progress" id="sfc-progress-bar">
                                    <div class="sfc-linear-progress-bar"></div>
                                </div>
                            </div>
                            <div class="sfc-preview-footer">
                                <div class="sfc-preview-status" id="sfc-scan-status-text">
                                    <svg class="sfc-spin" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21.5 2v6h-6M21.34 15.57a10 10 0 1 1-.57-8.38l5.67-5.67"/></svg>
                                    <span>Распознавание продуктов...</span>
                                </div>
                                <button type="button" class="sfc-btn-danger-outline" id="sfc-btn-remove-photo">
                                    <span>Удалить снимок</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="sfc-text-area" class="sfc-text-container" style="display: none;">
                        <div class="sfc-textarea-wrapper">
                            <textarea id="sfc-text-products" rows="4" class="sfc-textarea" placeholder="Например: 4 яйца, молоко 1 л, сыр 200г, куриное филе 500г, 2 помидора, макароны, картофель..."></textarea>
                        </div>
                        <div class="sfc-text-footer">
                            <button type="button" class="sfc-btn-primary" id="sfc-btn-parse-text">
                                <span>Добавить продукты</span>
                            </button>
                        </div>
                    </div>

                    <div class="sfc-preset-section">
                        <span class="sfc-preset-title">Готовые наборы для быстрого старта:</span>
                        <div class="sfc-preset-buttons">
                            <button type="button" class="sfc-preset-chip" data-preset="family">Семейный набор</button>
                            <button type="button" class="sfc-preset-chip" data-preset="bachelor">Холостяцкий</button>
                            <button type="button" class="sfc-preset-chip" data-preset="healthy">ЗОЖ / Спорт</button>
                            <button type="button" class="sfc-preset-chip" data-preset="minimal">Минимум</button>
                        </div>
                    </div>
                </div>

                <div class="sfc-panel sfc-panel-pantry">
                    <div class="sfc-panel-header sfc-flex-between">
                        <div>
                            <h2>Ваш кулинарный запас</h2>
                            <p class="sfc-subtext">Продукты, из которых шеф составит меню на каждый день</p>
                        </div>
                        <button type="button" class="sfc-btn-secondary" id="sfc-btn-add-manual-product">
                            <svg class="sfc-svg-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            <span>Добавить вручную</span>
                        </button>
                    </div>

                    <div id="sfc-products-empty" class="sfc-empty-placeholder">
                        <div class="sfc-empty-icon-wrap">
                            <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="2" width="16" height="20" rx="2"/><line x1="4" y1="10" x2="20" y2="10"/><line x1="8" y1="6" x2="8" y2="8"/><line x1="8" y1="14" x2="8" y2="17"/></svg>
                        </div>
                        <h3>В холодильнике пока пусто</h3>
                        <p>Сфотографируйте полки, введите список или выберите готовый набор слева, чтобы наполнить запасы.</p>
                    </div>

                    <div id="sfc-products-list-wrapper" style="display: none;">
                        <div class="sfc-pantry-board" id="sfc-products-board">
                            <!-- Categorized pantry cards rendered by JS -->
                        </div>
                        <table id="sfc-products-table" style="display:none;"><tbody id="sfc-products-tbody"></tbody></table>

                        <div class="sfc-bottom-cta">
                            <div class="sfc-cta-info">
                                <strong>Список продуктов готов</strong>
                                <span>Шеф рассчитает сбалансированное меню и рецепты на неделю</span>
                            </div>
                            <button type="button" class="sfc-btn-primary sfc-btn-lg" id="sfc-btn-start-cook">
                                <span>Составить меню</span>
                                <svg class="sfc-svg-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="tab-recipes" class="sfc-view">
            <div class="sfc-view-header">
                <div>
                    <h2>Блюда из ваших продуктов</h2>
                    <p class="sfc-subtext">Рецепты, подобранные специально под ваш запас продуктов</p>
                </div>
                <div class="sfc-filter-pills" id="sfc-recipe-filters">
                    <button type="button" class="sfc-filter-pill active" data-filter="all">Все блюда</button>
                    <button type="button" class="sfc-filter-pill" data-filter="ready">Можно готовить сразу</button>
                    <button type="button" class="sfc-filter-pill" data-filter="need_more">С докупкой продуктов</button>
                </div>
            </div>

            <div class="sfc-recipe-grid" id="sfc-recipes-container">
                <!-- Recipe cards rendered by JS -->
            </div>
        </section>

        <section id="tab-plan" class="sfc-view">
            <div class="sfc-view-header sfc-flex-between">
                <div>
                    <h2>План питания на неделю</h2>
                    <p class="sfc-subtext">Сбалансированное меню на каждый день с возможностью замены любого блюда</p>
                </div>
                <div class="sfc-header-actions">
                    <button type="button" class="sfc-btn-secondary" id="sfc-btn-save-plan">
                        <svg class="sfc-svg-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                        <span>Сохранить</span>
                    </button>
                    <button type="button" class="sfc-btn-secondary" onclick="window.print()">
                        <svg class="sfc-svg-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                        <span>Печать</span>
                    </button>
                </div>
            </div>

            <div class="sfc-calendar-strip" id="sfc-days-nav">
                <!-- Day buttons rendered by JS -->
            </div>

            <div id="sfc-day-view-container">
                <!-- Day meals slots rendered by JS -->
            </div>
        </section>

        <section id="tab-shopping" class="sfc-view">
            <div class="sfc-view-header sfc-flex-between">
                <div>
                    <h2>Список покупок</h2>
                    <p class="sfc-subtext">Только то, чего не хватает в холодильнике для меню на неделю</p>
                </div>
                <button type="button" class="sfc-btn-primary" id="sfc-btn-go-to-stores">
                    <svg class="sfc-svg-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l1 10a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2l1-10"/><path d="M3 9a3 3 0 0 1 6 0 3 3 0 0 1 6 0 3 3 0 0 1 6 0"/><line x1="3" y1="9" x2="21" y2="9"/><path d="M4 9V4a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v5"/></svg>
                    <span>Найти магазины рядом</span>
                </button>
            </div>

            <div class="sfc-shopping-layout">
                <div class="sfc-panel sfc-checklist-panel">
                    <div class="sfc-checklist-toolbar">
                        <div class="sfc-remaining-badge">
                            Осталось купить: <strong id="sfc-shopping-remaining-count">0</strong>
                        </div>
                        <button type="button" class="sfc-btn-secondary sfc-btn-sm" id="sfc-btn-check-all">
                            Отметить все
                        </button>
                    </div>
                    <div id="sfc-grouped-shopping-container">
                        <!-- Grouped shopping list rendered by JS -->
                    </div>
                </div>

                <div class="sfc-panel sfc-sidebar-panel">
                    <div class="sfc-sidebar-card">
                        <div class="sfc-sidebar-card-title">
                            <span>Оптимизация закупок</span>
                        </div>
                        <p class="sfc-sidebar-desc" id="sfc-quick-store-teaser">
                            Сравнение ассортимента ближайших супермаркетов под ваш список...
                        </p>
                        <button type="button" class="sfc-btn-secondary sfc-btn-block" id="sfc-btn-open-map-teaser">
                            Открыть карту магазинов
                        </button>
                    </div>

                    <div class="sfc-sidebar-hints">
                        <h4>Рекомендации по закупкам:</h4>
                        <ul>
                            <li><strong>Свежие овощи и зелень:</strong> покупайте порционно на 2-3 дня.</li>
                            <li><strong>Крупы и бакалея:</strong> стандартные упаковки выгоднее брать в супермаркетах.</li>
                            <li><strong>Мясо и птица:</strong> проверяйте дату фасовки и храните в зоне свежести.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <section id="tab-stores" class="sfc-view">
            <div class="sfc-view-header">
                <div>
                    <h2>Магазины рядом</h2>
                    <p class="sfc-subtext">Поиск реальных продуктовых магазинов на карте поблизости от вас</p>
                </div>
            </div>

            <div id="sfc-location-prompt" class="sfc-location-banner" style="display: none;">
                <div class="sfc-location-banner-text">
                    <h3>Определите местоположение</h3>
                    <p>Разрешите доступ к геопозиции или укажите ваш город в строке поиска ниже.</p>
                </div>
                <button type="button" class="sfc-btn-primary" id="sfc-btn-prompt-geolocate">
                    <svg class="sfc-svg-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/><line x1="12" y1="2" x2="12" y2="5"/><line x1="12" y1="19" x2="12" y2="22"/><line x1="2" y1="12" x2="5" y2="12"/><line x1="19" y1="12" x2="22" y2="12"/></svg>
                    <span>Поделиться местоположением</span>
                </button>
            </div>

            <div id="sfc-recommendation-hero" class="sfc-rec-card" style="display: none;">
                <div class="sfc-rec-card-header">
                    <div class="sfc-rec-badge">
                        <span>Рекомендуемый супермаркет</span>
                    </div>
                    <span class="sfc-rec-engine-tag" id="sfc-rec-engine-label">Оптимальный выбор</span>
                </div>

                <div class="sfc-rec-content">
                    <div class="sfc-rec-primary">
                        <div class="sfc-rec-title-row">
                            <h3 id="sfc-rec-title">Супермаркет</h3>
                            <span class="sfc-rec-dist-chip" id="sfc-rec-distance">-- м</span>
                        </div>
                        <p class="sfc-rec-reason" id="sfc-rec-reason">Анализ ассортимента...</p>
                        
                        <div class="sfc-rec-metadata">
                            <span id="sfc-rec-time"><svg class="sfc-svg-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="5" r="2"/><path d="M10 22l2-7 3-3-2-4-3 1-2-1M14 15l2 7"/></svg> ~-- мин пешком</span>
                            <span id="sfc-rec-hours"><svg class="sfc-svg-icon" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> --</span>
                        </div>

                        <div class="sfc-rec-tips-box" id="sfc-rec-tips">
                            <!-- Tips -->
                        </div>
                    </div>

                    <div class="sfc-rec-actions">
                        <a id="sfc-rec-route-yandex" href="#" target="_blank" class="sfc-btn-primary">
                            <span>Яндекс Карты</span>
                        </a>
                        <a id="sfc-rec-route-google" href="#" target="_blank" class="sfc-btn-secondary">
                            <span>Google Карты</span>
                        </a>
                    </div>
                </div>
            </div>

            <div class="sfc-maps-layout">
                <div class="sfc-panel sfc-map-wrapper">
                    <div class="sfc-map-searchbar">
                        <div class="sfc-search-input-box">
                            <input type="text" id="sfc-search-address" placeholder="Укажите город или адрес..." value="" />
                            <button type="button" id="sfc-btn-search-city" class="sfc-search-btn" title="Найти">
                                <svg class="sfc-svg-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                            </button>
                        </div>
                        <button type="button" class="sfc-btn-secondary" id="sfc-btn-geolocate">
                            <svg class="sfc-svg-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/><line x1="12" y1="2" x2="12" y2="5"/><line x1="12" y1="19" x2="12" y2="22"/><line x1="2" y1="12" x2="5" y2="12"/><line x1="19" y1="12" x2="22" y2="12"/></svg>
                            <span>Мое местоположение</span>
                        </button>
                    </div>
                    <div id="sfc-map" class="sfc-leaflet-map"></div>
                </div>

                <div class="sfc-panel sfc-places-wrapper">
                    <div class="sfc-panel-header">
                        <h3>Список супермаркетов</h3>
                        <p class="sfc-subtext" id="sfc-stores-subtitle">Нажмите на магазин для просмотра на карте</p>
                    </div>
                    <div class="sfc-places-list" id="sfc-stores-list">
                        <!-- Store cards rendered by JS -->
                    </div>
                </div>
            </div>
        </section>

    </main>

    <div id="sfc-recipe-modal" class="sfc-modal-scrim" style="display: none;">
        <div class="sfc-modal-sheet">
            <button type="button" class="sfc-modal-close" id="sfc-modal-close-btn" aria-label="Закрыть">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
            <div id="sfc-modal-content">
                <!-- Modal content -->
            </div>
        </div>
    </div>

    <div id="sfc-swap-modal" class="sfc-modal-scrim" style="display: none;">
        <div class="sfc-modal-sheet">
            <button type="button" class="sfc-modal-close" id="sfc-swap-modal-close" aria-label="Закрыть">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
            <div class="sfc-modal-title">
                <h3 id="sfc-swap-modal-title">Замена блюда</h3>
                <p class="sfc-subtext" id="sfc-swap-modal-subtitle">Выберите подходящее блюдо из продуктов в наличии или с докупкой</p>
            </div>
            <div id="sfc-swap-list" class="sfc-swap-options-list">
                <!-- Options list -->
            </div>
        </div>
    </div>

    <div id="sfc-add-modal" class="sfc-modal-scrim" style="display: none;">
        <div class="sfc-modal-sheet sfc-modal-compact">
            <button type="button" class="sfc-modal-close" id="sfc-add-modal-close" aria-label="Закрыть">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
            </button>
            <div class="sfc-modal-title">
                <h3>Добавить продукт</h3>
                <p class="sfc-subtext">Укажите наименование и количество</p>
            </div>
            <form id="sfc-manual-add-form" class="sfc-form-styled">
                <div class="sfc-form-field">
                    <label for="sfc-add-name">Название продукта</label>
                    <input type="text" id="sfc-add-name" required placeholder="Например: Творог 5% или Сыр пармезан" />
                </div>
                <div class="sfc-form-field">
                    <label for="sfc-add-qty">Количество / вес</label>
                    <input type="text" id="sfc-add-qty" placeholder="Например: 200 г, 1 упаковка" value="1 шт" />
                </div>
                <div class="sfc-modal-actions">
                    <button type="button" class="sfc-btn-secondary" onclick="document.getElementById('sfc-add-modal').style.display='none';">Отмена</button>
                    <button type="submit" class="sfc-btn-primary">Сохранить</button>
                </div>
            </form>
        </div>
    </div>

</div>
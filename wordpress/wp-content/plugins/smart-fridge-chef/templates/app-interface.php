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
                    <span class="material-symbols-outlined">playlist_add</span>
                    <span>Пример продуктов</span>
                </button>
                <button type="button" id="sfc-btn-reset" class="sfc-btn-secondary" title="Очистить все данные">
                    <span class="material-symbols-outlined">delete_sweep</span>
                </button>
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=smart-fridge-chef' ) ); ?>" class="sfc-btn-secondary" title="Настройки подключения" id="sfc-ai-status-badge">
                    <span class="material-symbols-outlined">tune</span>
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
                        <h2>Продукты в наличии</h2>
                        <p class="sfc-subtext">Загрузите фотографию полок холодильника или добавьте список текстом</p>
                    </div>

                    <div class="sfc-segmented-control">
                        <button type="button" class="sfc-segment active" id="btn-mode-photo">
                            <span>Фото холодильника</span>
                        </button>
                        <button type="button" class="sfc-segment" id="btn-mode-text">
                            <span>Текстовый список</span>
                        </button>
                    </div>

                    <div id="sfc-photo-area" class="sfc-upload-container">
                        <input type="file" id="sfc-file-input" accept="image/*" class="sfc-hidden-input" />
                        <div class="sfc-dropzone" id="sfc-dropzone">
                            <div class="sfc-dropzone-label">
                                <strong>Перетащите фотографию продуктов сюда</strong>
                                <span>JPG, PNG или WebP со снимком содержимого холодильника</span>
                            </div>
                            <div class="sfc-dropzone-actions">
                                <button type="button" class="sfc-btn-primary" id="sfc-btn-choose-file">
                                    <span class="material-symbols-outlined">upload_file</span>
                                    <span>Выбрать файл</span>
                                </button>
                                <button type="button" class="sfc-btn-secondary" id="sfc-btn-camera">
                                    <span class="material-symbols-outlined">photo_camera</span>
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
                                    <span class="material-symbols-outlined sfc-spin">sync</span>
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
                        <span class="sfc-preset-title">Готовые наборы:</span>
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
                            <h2>Список продуктов</h2>
                            <p class="sfc-subtext">Ингредиенты для расчета рецептов и меню на неделю</p>
                        </div>
                        <button type="button" class="sfc-btn-secondary" id="sfc-btn-add-manual-product">
                            <span class="material-symbols-outlined">add</span>
                            <span>Добавить вручную</span>
                        </button>
                    </div>

                    <div id="sfc-products-empty" class="sfc-empty-placeholder">
                        <h3>Холодильник пуст</h3>
                        <p>Загрузите фотографию продуктов или введите список текстом.</p>
                    </div>

                    <div id="sfc-products-list-wrapper" style="display: none;">
                        <div class="sfc-pantry-board" id="sfc-products-board">
                            <!-- Categorized pantry cards rendered by JS -->
                        </div>
                        <table id="sfc-products-table" style="display:none;"><tbody id="sfc-products-tbody"></tbody></table>

                        <div class="sfc-bottom-cta">
                            <div class="sfc-cta-info">
                                <strong>Список сформирован</strong>
                                <span>Перейдите к расчету блюд и формированию рациона</span>
                            </div>
                            <button type="button" class="sfc-btn-primary sfc-btn-lg" id="sfc-btn-start-cook">
                                <span>Рассчитать меню</span>
                                <span class="material-symbols-outlined">arrow_forward</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="tab-recipes" class="sfc-view">
            <div class="sfc-view-header">
                <div>
                    <h2>Рецепты блюд</h2>
                    <p class="sfc-subtext">Блюда, сформированные на основе продуктов в вашем холодильнике</p>
                </div>
                <div class="sfc-filter-pills" id="sfc-recipe-filters">
                    <button type="button" class="sfc-filter-pill active" data-filter="all">Все блюда</button>
                    <button type="button" class="sfc-filter-pill" data-filter="ready">Из того, что есть</button>
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
                        <span class="material-symbols-outlined">save</span>
                        <span>Сохранить</span>
                    </button>
                    <button type="button" class="sfc-btn-secondary" onclick="window.print()">
                        <span class="material-symbols-outlined">print</span>
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
                    <p class="sfc-subtext">Недостающие продукты для недельного рациона за вычетом содержимого холодильника</p>
                </div>
                <button type="button" class="sfc-btn-primary" id="sfc-btn-go-to-stores">
                    <span class="material-symbols-outlined">storefront</span>
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
                    <span class="material-symbols-outlined">my_location</span>
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
                            <span id="sfc-rec-time"><span class="material-symbols-outlined">directions_walk</span> ~-- мин пешком</span>
                            <span id="sfc-rec-hours"><span class="material-symbols-outlined">schedule</span> --</span>
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
                                <span class="material-symbols-outlined">search</span>
                            </button>
                        </div>
                        <button type="button" class="sfc-btn-secondary" id="sfc-btn-geolocate">
                            <span class="material-symbols-outlined">my_location</span>
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
                <span class="material-symbols-outlined">close</span>
            </button>
            <div id="sfc-modal-content">
                <!-- Modal content -->
            </div>
        </div>
    </div>

    <div id="sfc-swap-modal" class="sfc-modal-scrim" style="display: none;">
        <div class="sfc-modal-sheet">
            <button type="button" class="sfc-modal-close" id="sfc-swap-modal-close" aria-label="Закрыть">
                <span class="material-symbols-outlined">close</span>
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
                <span class="material-symbols-outlined">close</span>
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
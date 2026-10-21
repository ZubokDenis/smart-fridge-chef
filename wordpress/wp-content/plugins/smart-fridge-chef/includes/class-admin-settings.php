<?php
/**
 * Admin Settings for Smart Fridge Chef
 * Supports Ollama Cloud (Remote Host with Token/Key), Anthropic Claude, Google Gemini, and Geolocation defaults.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SFC_Admin_Settings {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_menu_page' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
    }

    public static function add_menu_page() {
        add_menu_page(
            'Настройки ИИ и Шефа',
            'Умный Шеф',
            'manage_options',
            'smart-fridge-chef',
            [ __CLASS__, 'render_admin_page' ],
            'dashicons-carrot',
            26
        );

        add_submenu_page(
            'smart-fridge-chef',
            'Все рецепты',
            'Рецепты',
            'manage_options',
            'edit.php?post_type=sfc_recipe'
        );

        add_submenu_page(
            'smart-fridge-chef',
            'Сохраненные планы питания',
            'Планы питания',
            'manage_options',
            'edit.php?post_type=sfc_meal_plan'
        );
    }

    public static function register_settings() {
        register_setting( 'sfc_settings_group', 'sfc_ai_provider' );
        register_setting( 'sfc_settings_group', 'sfc_ollama_url' );
        register_setting( 'sfc_settings_group', 'sfc_ollama_api_key' );
        register_setting( 'sfc_settings_group', 'sfc_ollama_model' );
        register_setting( 'sfc_settings_group', 'sfc_claude_api_key' );
        register_setting( 'sfc_settings_group', 'sfc_claude_base_url' );
        register_setting( 'sfc_settings_group', 'sfc_claude_model' );
        register_setting( 'sfc_settings_group', 'sfc_gemini_api_key' );
        register_setting( 'sfc_settings_group', 'sfc_gemini_model' );
        register_setting( 'sfc_settings_group', 'sfc_default_city' );
        register_setting( 'sfc_settings_group', 'sfc_default_lat' );
        register_setting( 'sfc_settings_group', 'sfc_default_lon' );
    }

    public static function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $provider        = get_option( 'sfc_ai_provider', 'ollama_cloud' );
        $ollama_url      = get_option( 'sfc_ollama_url', '' );
        $ollama_api_key  = get_option( 'sfc_ollama_api_key', '' );
        $ollama_model    = get_option( 'sfc_ollama_model', 'llama3.2-vision' );
        $claude_key      = get_option( 'sfc_claude_api_key', '' );
        $claude_base_url = get_option( 'sfc_claude_base_url', 'https://api.anthropic.com' );
        $claude_model    = get_option( 'sfc_claude_model', 'claude-3-5-sonnet-20241022' );
        $gemini_key     = get_option( 'sfc_gemini_api_key', '' );
        $gemini_model   = get_option( 'sfc_gemini_model', 'gemini-1.5-flash' );
        $default_city   = get_option( 'sfc_default_city', 'Москва' );
        $default_lat    = get_option( 'sfc_default_lat', '55.7558' );
        $default_lon    = get_option( 'sfc_default_lon', '37.6173' );
        ?>
        <div class="wrap" style="max-width: 880px; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
            <div style="background: #ffffff; border: 1px solid #dadce0; border-radius: 8px; padding: 20px 24px; margin-bottom: 20px; display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h1 style="margin: 0 0 4px; font-size: 20px; font-weight: 700; color: #202124;">Конфигурация искусственного интеллекта</h1>
                    <p style="margin: 0; color: #5f6368; font-size: 14px;">Подключение <strong>Ollama Cloud</strong>, <strong>Anthropic Claude</strong>, <strong>Google Gemini</strong> для анализа фото холодильника и поиска магазинов</p>
                </div>
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" class="button button-primary" style="background:#1a73e8; border-color:#1a73e8; padding: 4px 16px;">
                    Открыть сайт &rarr;
                </a>
            </div>

            <div style="background: #ffffff; border: 1px solid #dadce0; border-radius: 8px; padding: 24px; margin-bottom: 24px;">
                <form method="post" action="options.php" id="sfc-admin-settings-form">
                    <?php settings_fields( 'sfc_settings_group' ); ?>
                    <?php do_settings_sections( 'sfc_settings_group' ); ?>

                    <table class="form-table" style="margin-top: 0;">
                        <!-- AI Provider Select -->
                        <tr valign="top">
                            <th scope="row" style="width: 240px; font-weight: 600; color: #202124;">
                                Основной провайдер ИИ
                            </th>
                            <td>
                                <select name="sfc_ai_provider" id="sfc_ai_provider_select" style="min-width: 320px; padding: 4px 8px; font-size: 14px;">
                                    <option value="ollama_cloud" <?php selected( $provider, 'ollama_cloud' ); ?>>☁️ Ollama Cloud / Удаленный сервер</option>
                                    <option value="claude" <?php selected( $provider, 'claude' ); ?>>🧠 Anthropic Claude (claude-3-5-sonnet)</option>
                                    <option value="gemini" <?php selected( $provider, 'gemini' ); ?>>✨ Google Gemini (1.5 / 2.0 Flash)</option>
                                    <option value="builtin" <?php selected( $provider, 'builtin' ); ?>>⚙️ Встроенный экспертный движок (автономный)</option>
                                </select>
                                <p class="description">Выберите, какой ИИ-сервер будет отвечать за распознавание продуктов с фото и анализ супермаркетов.</p>
                            </td>
                        </tr>

                        <!-- Section: Ollama Cloud Settings -->
                        <tr class="sfc-provider-row sfc-row-ollama">
                            <th scope="row" style="font-weight: 600; color: #202124;">Ollama Cloud Endpoint URL</th>
                            <td>
                                <input type="text" name="sfc_ollama_url" value="<?php echo esc_attr( $ollama_url ); ?>" style="width: 100%; max-width: 480px;" placeholder="https://your-ollama-host.com или https://...runpod.net" />
                                <p class="description">Адрес вашего облачного или удаленного Ollama сервера (например, <code>https://api.yourdomain.com</code> или <code>https://...</code>).</p>
                            </td>
                        </tr>
                        <tr class="sfc-provider-row sfc-row-ollama">
                            <th scope="row" style="font-weight: 600; color: #202124;">Ollama Cloud API Key / Token</th>
                            <td>
                                <input type="password" name="sfc_ollama_api_key" value="<?php echo esc_attr( $ollama_api_key ); ?>" style="width: 100%; max-width: 480px;" placeholder="Bearer токен или пароль (если требуется)" />
                                <p class="description">Передается в заголовке <code>Authorization: Bearer &lt;token&gt;</code>. Оставьте пустым, если ваш сервер без авторизации.</p>
                            </td>
                        </tr>
                        <tr class="sfc-provider-row sfc-row-ollama">
                            <th scope="row" style="font-weight: 600; color: #202124;">Ollama Модель</th>
                            <td>
                                <input type="text" name="sfc_ollama_model" value="<?php echo esc_attr( $ollama_model ); ?>" style="width: 100%; max-width: 480px;" placeholder="llama3.2-vision" />
                                <p class="description">Название модели на вашем Ollama Cloud, например: <code>llama3.2-vision</code>, <code>llava</code>, <code>qwen2-vl</code>, <code>claude</code> (proxy), <code>llama3</code>.</p>
                            </td>
                        </tr>

                        <!-- Section: Claude Settings -->
                        <tr class="sfc-provider-row sfc-row-claude">
                            <th scope="row" style="font-weight: 600; color: #202124;">Anthropic Claude API Key</th>
                            <td>
                                <input type="password" name="sfc_claude_api_key" value="<?php echo esc_attr( $claude_key ); ?>" style="width: 100%; max-width: 480px;" placeholder="sk-ant-api03-..." />
                                <p class="description">API-ключ с платформы console.anthropic.com или прокси.</p>
                            </td>
                        </tr>
                        <tr class="sfc-provider-row sfc-row-claude">
                            <th scope="row" style="font-weight: 600; color: #202124;">Claude Base URL (Endpoint)</th>
                            <td>
                                <input type="text" name="sfc_claude_base_url" value="<?php echo esc_attr( $claude_base_url ); ?>" style="width: 100%; max-width: 480px;" placeholder="https://api.anthropic.com" />
                                <p class="description">Базовый адрес API. По умолчанию: <code>https://api.anthropic.com</code> (или укажите адрес вашего облачного прокси-шлюза).</p>
                            </td>
                        </tr>
                        <tr class="sfc-provider-row sfc-row-claude">
                            <th scope="row" style="font-weight: 600; color: #202124;">Claude Модель</th>
                            <td>
                                <input type="text" name="sfc_claude_model" value="<?php echo esc_attr( $claude_model ); ?>" style="width: 100%; max-width: 480px;" placeholder="claude-3-5-sonnet-20241022" />
                            </td>
                        </tr>

                        <!-- Section: Gemini Settings -->
                        <tr class="sfc-provider-row sfc-row-gemini">
                            <th scope="row" style="font-weight: 600; color: #202124;">Google Gemini API Key</th>
                            <td>
                                <input type="password" name="sfc_gemini_api_key" value="<?php echo esc_attr( $gemini_key ); ?>" style="width: 100%; max-width: 480px;" placeholder="AIzaSy..." />
                            </td>
                        </tr>

                        <!-- Location Settings -->
                        <tr><td colspan="2"><hr style="border:none; border-top: 1px solid #dadce0; margin: 12px 0;" /></td></tr>
                        <tr valign="top">
                            <th scope="row" style="font-weight: 600; color: #202124;">Город по умолчанию</th>
                            <td>
                                <input type="text" name="sfc_default_city" value="<?php echo esc_attr( $default_city ); ?>" style="width: 240px;" />
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row" style="font-weight: 600; color: #202124;">Координаты (Широта / Долгота)</th>
                            <td>
                                <input type="text" name="sfc_default_lat" value="<?php echo esc_attr( $default_lat ); ?>" style="width: 120px;" />
                                <input type="text" name="sfc_default_lon" value="<?php echo esc_attr( $default_lon ); ?>" style="width: 120px; margin-left: 8px;" />
                            </td>
                        </tr>
                    </table>

                    <div style="margin-top: 24px; padding-top: 16px; border-top: 1px solid #dadce0; display:flex; gap: 12px; align-items:center;">
                        <?php submit_button( 'Сохранить настройки', 'primary', 'submit', false, [ 'style' => 'background: #1a73e8; border-color: #1a73e8;' ] ); ?>
                        <button type="button" class="button" id="sfc-btn-test-connection" style="display:inline-flex; align-items:center; gap: 6px;">
                            <span>Проверить подключение к ИИ</span>
                        </button>
                        <span id="sfc-test-result" style="margin-left: 8px; font-size: 13px;"></span>
                    </div>
                </form>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            var select = document.getElementById('sfc_ai_provider_select');
            function updateVisibility() {
                var val = select.value;
                document.querySelectorAll('.sfc-row-ollama').forEach(function(el) {
                    el.style.display = (val === 'ollama_cloud' || val === 'ollama') ? '' : 'none';
                });
                document.querySelectorAll('.sfc-row-claude').forEach(function(el) {
                    el.style.display = (val === 'claude') ? '' : 'none';
                });
                document.querySelectorAll('.sfc-row-gemini').forEach(function(el) {
                    el.style.display = (val === 'gemini') ? '' : 'none';
                });
            }
            select.addEventListener('change', updateVisibility);
            updateVisibility();

            var testBtn = document.getElementById('sfc-btn-test-connection');
            var resultEl = document.getElementById('sfc-test-result');

            testBtn.addEventListener('click', function() {
                resultEl.innerHTML = '<span style="color:#5f6368;">Проверка связи с облачным сервером...</span>';
                testBtn.disabled = true;

                fetch('<?php echo esc_url( rest_url( 'fridge-chef/v1/test-ai' ) ); ?>', {
                    method: 'POST',
                    headers: { 'X-WP-Nonce': '<?php echo wp_create_nonce( 'wp_rest' ); ?>' }
                })
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    if (data.success) {
                        resultEl.innerHTML = '<span style="color:#137333; font-weight:600;">✓ ' + data.message + (data.latency ? ' (' + data.latency + ')' : '') + '</span>';
                    } else {
                        resultEl.innerHTML = '<span style="color:#c5221f; font-weight:600;">✕ ' + data.message + '</span>';
                    }
                })
                .catch(function(e) {
                    resultEl.innerHTML = '<span style="color:#c5221f;">Ошибка сети при запросе к серверу.</span>';
                })
                .finally(function() {
                    testBtn.disabled = false;
                });
            });
        });
        </script>
        <?php
    }
}

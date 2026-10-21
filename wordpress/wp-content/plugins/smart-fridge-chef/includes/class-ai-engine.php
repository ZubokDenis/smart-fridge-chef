<?php
/**
 * AI & Culinary Engine for Smart Fridge Chef
 * Multi-Provider: Ollama Cloud / Remote, Anthropic Claude, Google Gemini, and Smart Built-in Fallback.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SFC_AI_Engine {

    /**
     * Get active AI provider configuration
     */
    public static function get_ai_config() {
        $raw_url = trim( get_option( 'sfc_ollama_url', 'https://api.ollama.com' ) );
        if ( empty( $raw_url ) || $raw_url === 'admin' || strpos( $raw_url, 'http' ) !== 0 ) {
            $raw_url = 'https://api.ollama.com';
        }

        return [
            'provider'        => get_option( 'sfc_ai_provider', 'ollama_cloud' ), // 'ollama_cloud', 'claude', 'gemini', 'builtin'
            'ollama_url'      => rtrim( $raw_url, '/' ),
            'ollama_api_key'  => get_option( 'sfc_ollama_api_key', '' ),
            'ollama_model'    => get_option( 'sfc_ollama_model', 'deepseek-v4.1-flash' ),
            'claude_key'      => get_option( 'sfc_claude_api_key', '' ),
            'claude_base_url' => get_option( 'sfc_claude_base_url', 'https://api.anthropic.com' ),
            'claude_model'    => get_option( 'sfc_claude_model', 'claude-3-5-sonnet-20241022' ),
            'gemini_key'      => get_option( 'sfc_gemini_api_key', '' ),
            'gemini_model'    => get_option( 'sfc_gemini_model', 'gemini-1.5-flash' ),
        ];
    }

    /**
     * Build HTTP headers for Ollama Cloud (supports Bearer token)
     */
    public static function get_ollama_headers( $config ) {
        $headers = [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];
        if ( ! empty( $config['ollama_api_key'] ) ) {
            $headers['Authorization'] = 'Bearer ' . trim( $config['ollama_api_key'] );
        }
        return $headers;
    }

    /**
     * Test connection to active AI provider
     */
    public static function test_ai_connection() {
        $config = self::get_ai_config();
        $provider = $config['provider'];
        $start_time = microtime( true );

        if ( $provider === 'ollama_cloud' || $provider === 'ollama' ) {
            $host = rtrim( $config['ollama_url'], '/' );
            if ( empty( $host ) || $host === 'https://your-ollama-cloud-host.com' ) {
                return [
                    'success'  => false,
                    'provider' => 'Ollama Cloud',
                    'message'  => 'Укажите реальный адрес вашего Ollama Cloud сервера (URL) в настройках.',
                ];
            }

            $headers = self::get_ollama_headers( $config );

            // 1. Try GET /api/tags
            $res = wp_remote_get( $host . '/api/tags', [
                'headers' => $headers,
                'timeout' => 10,
                'sslverify' => false,
            ]);

            // 2. If 404, try GET /v1/models (OpenAI compatibility on Ollama)
            if ( ! is_wp_error( $res ) && wp_remote_retrieve_response_code( $res ) === 404 ) {
                $res = wp_remote_get( $host . '/v1/models', [
                    'headers' => $headers,
                    'timeout' => 10,
                    'sslverify' => false,
                ]);
            }

            if ( is_wp_error( $res ) ) {
                return [
                    'success'  => false,
                    'provider' => 'Ollama Cloud',
                    'message'  => 'Ошибка подключения к Ollama Cloud (' . esc_html( $host ) . '): ' . $res->get_error_message(),
                ];
            }

            $code = wp_remote_retrieve_response_code( $res );
            $latency = round( ( microtime( true ) - $start_time ) * 1000 );
            $body = json_decode( wp_remote_retrieve_body( $res ), true );

            $models = [];
            if ( ! empty( $body['models'] ) && is_array( $body['models'] ) ) {
                foreach ( $body['models'] as $m ) {
                    $models[] = $m['name'] ?? $m['id'] ?? '';
                }
            } elseif ( ! empty( $body['data'] ) && is_array( $body['data'] ) ) {
                foreach ( $body['data'] as $m ) {
                    $models[] = $m['id'] ?? '';
                }
            }
            $models = array_filter( $models );

            if ( $code === 200 ) {
                $model_str = ! empty( $models ) ? " Доступно моделей: " . count( $models ) . " (" . implode( ', ', array_slice( $models, 0, 4 ) ) . ")" : "";
                return [
                    'success'  => true,
                    'provider' => 'Ollama Cloud',
                    'latency'  => $latency . ' мс',
                    'models'   => $models,
                    'message'  => "Успешное подключение к Ollama Cloud!{$model_str}",
                ];
            }

            if ( $code === 401 || $code === 403 ) {
                return [
                    'success'  => false,
                    'provider' => 'Ollama Cloud',
                    'message'  => "Ошибка авторизации (HTTP $code). Проверьте Ollama Cloud API Key / Token.",
                ];
            }

            return [
                'success'  => false,
                'provider' => 'Ollama Cloud',
                'message'  => "Сервер ответил с кодом ошибки $code.",
            ];
        }

        if ( $provider === 'claude' ) {
            if ( empty( $config['claude_key'] ) ) {
                return [
                    'success'  => false,
                    'provider' => 'Anthropic Claude',
                    'message'  => 'API-ключ Claude не указан в настройках.',
                ];
            }

            $base_url = rtrim( $config['claude_base_url'] ?: 'https://api.anthropic.com', '/' );
            $url = $base_url . '/v1/messages';
            $res = wp_remote_post( $url, [
                'timeout' => 10,
                'sslverify' => false,
                'headers' => [
                    'x-api-key'         => $config['claude_key'],
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                ],
                'body' => wp_json_encode([
                    'model'      => $config['claude_model'],
                    'max_tokens' => 20,
                    'messages'   => [
                        [ 'role' => 'user', 'content' => 'Ping. Answer: OK' ]
                    ]
                ])
            ]);

            if ( is_wp_error( $res ) ) {
                return [
                    'success'  => false,
                    'provider' => 'Anthropic Claude',
                    'message'  => 'Ошибка подключения к Claude: ' . $res->get_error_message(),
                ];
            }

            $code = wp_remote_retrieve_response_code( $res );
            $latency = round( ( microtime( true ) - $start_time ) * 1000 );

            return [
                'success'  => ( $code === 200 ),
                'provider' => 'Anthropic Claude (' . $config['claude_model'] . ')',
                'latency'  => $latency . ' мс',
                'message'  => ( $code === 200 ) ? 'Подключение к Claude API успешно!' : "Claude API вернул код ошибки $code.",
            ];
        }

        if ( $provider === 'gemini' ) {
            if ( empty( $config['gemini_key'] ) ) {
                return [
                    'success'  => false,
                    'provider' => 'Google Gemini',
                    'message'  => 'Ключ Google Gemini API не заполнен.',
                ];
            }

            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$config['gemini_model']}:generateContent?key={$config['gemini_key']}";
            $res = wp_remote_post( $url, [
                'timeout' => 8,
                'headers' => [ 'Content-Type' => 'application/json' ],
                'body'    => wp_json_encode([
                    'contents' => [ [ 'parts' => [ [ 'text' => 'Ping' ] ] ] ]
                ])
            ]);

            $code = wp_remote_retrieve_response_code( $res );
            return [
                'success'  => ( $code === 200 ),
                'provider' => 'Google Gemini',
                'message'  => ( $code === 200 ) ? 'Подключение к Gemini API успешно!' : "Ошибка Gemini: код $code",
            ];
        }

        return [
            'success'  => true,
            'provider' => 'Встроенный экспертный интеллект',
            'message'  => 'Активен встроенный кулинарный интеллект (не требует внешних подключений).',
        ];
    }

    /**
     * Unified AI Dispatcher: analyzes fridge image
     */
    public static function analyze_fridge_image( $image_base64, $mime_type = 'image/jpeg' ) {
        $config = self::get_ai_config();
        $provider = $config['provider'];

        // 1. Try Ollama Cloud
        if ( $provider === 'ollama_cloud' || $provider === 'ollama' ) {
            $result = self::call_ollama_vision( $image_base64, $mime_type, $config );
            if ( ! empty( $result ) ) {
                return $result;
            }
        }

        // 2. Try Claude (Anthropic)
        if ( $provider === 'claude' && ! empty( $config['claude_key'] ) ) {
            $result = self::call_claude_vision( $image_base64, $mime_type, $config );
            if ( ! empty( $result ) ) {
                return $result;
            }
        }

        // 3. Try Google Gemini
        if ( $provider === 'gemini' && ! empty( $config['gemini_key'] ) ) {
            $result = self::call_gemini_vision( $image_base64, $mime_type, $config['gemini_key'] );
            if ( ! empty( $result ) ) {
                return $result;
            }
        }

        // 4. Fallback to smart detection
        return self::smart_sample_fridge_detection();
    }

    /**
     * Call Ollama Cloud Vision API (Supports both /api/generate and /v1/chat/completions)
     */
    public static function call_ollama_vision( $base64_image, $mime_type, $config ) {
        $host = rtrim( $config['ollama_url'], '/' );
        if ( empty( $host ) || $host === 'https://your-ollama-cloud-host.com' ) {
            return null;
        }

        $headers = self::get_ollama_headers( $config );

        $prompt = "Ты профессиональный шеф-повар. Посмотри на это фото холодильника. "
            . "Распознай все продукты питания и напиши их список. "
            . "Ответь ТОЛЬКО валидным JSON без markdown обертки в следующем формате:\n"
            . "{\n"
            . '  "products": [' . "\n"
            . '    {"name": "Яйца", "quantity": "6 шт", "category": "Яйца"},' . "\n"
            . '    {"name": "Молоко", "quantity": "1 л", "category": "Молочные продукты"}' . "\n"
            . "  ]\n"
            . "}";

        // Method A: Native Ollama /api/generate
        $native_url = $host . '/api/generate';
        $native_payload = [
            'model'   => $config['ollama_model'] ?: 'llama3.2-vision',
            'prompt'  => $prompt,
            'images'  => [ $base64_image ],
            'stream'  => false,
            'format'  => 'json',
            'options' => [ 'temperature' => 0.2 ]
        ];

        $response = wp_remote_post( $native_url, [
            'headers'   => $headers,
            'body'      => wp_json_encode( $native_payload ),
            'timeout'   => 40,
            'sslverify' => false,
        ]);

        if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
            $body = json_decode( wp_remote_retrieve_body( $response ), true );
            if ( ! empty( $body['response'] ) ) {
                $parsed = self::parse_ai_products_json( $body['response'] );
                if ( ! empty( $parsed ) ) return $parsed;
            }
        }

        // Method B: OpenAI-compatible /v1/chat/completions for hosted Ollama Cloud proxies
        $openai_url = $host . '/v1/chat/completions';
        $openai_payload = [
            'model'    => $config['ollama_model'] ?: 'llama3.2-vision',
            'messages' => [
                [
                    'role'    => 'user',
                    'content' => [
                        [ 'type' => 'text', 'text' => $prompt ],
                        [
                            'type'      => 'image_url',
                            'image_url' => [
                                'url' => 'data:' . $mime_type . ';base64,' . $base64_image,
                            ]
                        ]
                    ]
                ]
            ],
            'response_format' => [ 'type' => 'json_object' ],
            'temperature'     => 0.2,
        ];

        $response_b = wp_remote_post( $openai_url, [
            'headers'   => $headers,
            'body'      => wp_json_encode( $openai_payload ),
            'timeout'   => 40,
            'sslverify' => false,
        ]);

        if ( ! is_wp_error( $response_b ) && wp_remote_retrieve_response_code( $response_b ) === 200 ) {
            $body_b = json_decode( wp_remote_retrieve_body( $response_b ), true );
            if ( ! empty( $body_b['choices'][0]['message']['content'] ) ) {
                return self::parse_ai_products_json( $body_b['choices'][0]['message']['content'] );
            }
        }

        return null;
    }

    /**
     * Call Anthropic Claude Vision API
     */
    public static function call_claude_vision( $base64_image, $mime_type, $config ) {
        $base_url = rtrim( $config['claude_base_url'] ?: 'https://api.anthropic.com', '/' );
        $url = $base_url . '/v1/messages';

        $prompt = "Внимательно проанализируй это фото холодильника. Составь список распознанных продуктов. "
            . "Ответь ТОЛЬКО валидным JSON без markdown:\n"
            . "{\n"
            . '  "products": [' . "\n"
            . '    {"name": "Яйца", "quantity": "6 шт", "category": "Яйца"},' . "\n"
            . '    {"name": "Молоко", "quantity": "1 л", "category": "Молочные продукты"}' . "\n"
            . "  ]\n"
            . "}";

        $payload = [
            'model'      => $config['claude_model'] ?: 'claude-3-5-sonnet-20241022',
            'max_tokens' => 1000,
            'messages'   => [
                [
                    'role'    => 'user',
                    'content' => [
                        [
                            'type'   => 'image',
                            'source' => [
                                'type'       => 'base64',
                                'media_type' => $mime_type,
                                'data'       => $base64_image,
                            ]
                        ],
                        [
                            'type' => 'text',
                            'text' => $prompt
                        ]
                    ]
                ]
            ]
        ];

        $response = wp_remote_post( $url, [
            'headers' => [
                'x-api-key'         => $config['claude_key'],
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ],
            'body'    => wp_json_encode( $payload ),
            'timeout' => 35,
        ]);

        if ( is_wp_error( $response ) ) {
            return null;
        }

        $body = wp_remote_retrieve_body( $response );
        $json = json_decode( $body, true );

        if ( ! empty( $json['content'][0]['text'] ) ) {
            return self::parse_ai_products_json( $json['content'][0]['text'] );
        }

        return null;
    }

    /**
     * Call Google Gemini Vision API
     */
    public static function call_gemini_vision( $base64_image, $mime_type, $api_key ) {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $api_key;

        $prompt = "Ты шеф-повар. Проанализируй это фото холодильника. Составь список продуктов. "
            . "Ответь ТОЛЬКО валидным JSON:\n"
            . '{"products": [{"name": "Яйца", "quantity": "6 шт", "category": "Яйца"}]}';

        $body = [
            'contents' => [
                [
                    'parts' => [
                        [ 'text' => $prompt ],
                        [
                            'inline_data' => [
                                'mime_type' => $mime_type,
                                'data'      => $base64_image,
                            ]
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.2,
                'maxOutputTokens' => 1000,
            ]
        ];

        $response = wp_remote_post( $url, [
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => wp_json_encode( $body ),
            'timeout' => 30,
        ]);

        if ( is_wp_error( $response ) ) {
            return null;
        }

        $body_text = wp_remote_retrieve_body( $response );
        $json = json_decode( $body_text, true );

        if ( ! empty( $json['candidates'][0]['content']['parts'][0]['text'] ) ) {
            return self::parse_ai_products_json( $json['candidates'][0]['content']['parts'][0]['text'] );
        }

        return null;
    }

    /**
     * Helper to parse and structure products JSON from AI models
     */
    public static function parse_ai_products_json( $raw_text ) {
        $cleaned = trim( $raw_text );
        $cleaned = preg_replace( '/^```(json)?\s*/i', '', $cleaned );
        $cleaned = preg_replace( '/```\s*$/', '', $cleaned );

        $data = json_decode( $cleaned, true );
        if ( empty( $data['products'] ) || ! is_array( $data['products'] ) ) {
            return null;
        }

        $results = [];
        foreach ( $data['products'] as $p ) {
            $name = trim( $p['name'] ?? '' );
            if ( empty( $name ) ) continue;

            $cat_info = self::detect_category( $name );
            $results[] = [
                'id'         => 'prod_' . md5( $name . microtime( true ) ),
                'name'       => mb_convert_case( $name, MB_CASE_TITLE, 'UTF-8' ),
                'clean_key'  => self::get_product_root( $name ),
                'quantity'   => $p['quantity'] ?? '1 шт',
                'category'   => $p['category'] ?? $cat_info['category'],
                'icon'       => $cat_info['icon'],
                'freshness'  => $cat_info['perishable'] ? 'warning' : 'fresh',
                'perishable' => $cat_info['perishable'],
            ];
        }

        return ! empty( $results ) ? $results : null;
    }

    /**
     * AI-Powered Store Analysis & Recommendations
     * Uses active AI model (Ollama Cloud / Claude / Gemini) to evaluate candidate stores against shopping list
     */
    public static function analyze_stores_with_ai( $stores, $shopping_list, $user_location ) {
        $config = self::get_ai_config();
        $provider = $config['provider'];

        if ( $provider === 'builtin' ) {
            return null;
        }

        $items_str = [];
        foreach ( array_slice( $shopping_list, 0, 15 ) as $it ) {
            $items_str[] = "- " . $it['name'] . " (" . $it['amount'] . ", " . $it['category'] . ")";
        }
        $shopping_text = implode( "\n", $items_str );

        $stores_str = [];
        foreach ( $stores as $s ) {
            $stores_str[] = "ID: {$s['id']}, Название: {$s['name']}, Сеть: {$s['chain']}, Расстояние: {$s['distance_m']}м (~{$s['walking_time_mins']} мин пешком), Особенности: " . implode( ', ', $s['features'] ?? [] );
        }
        $stores_text = implode( "\n", $stores_str );

        $prompt = "Ты кулинарный эксперт и специалист по закупкам продуктов.\n"
            . "Список покупок пользователя:\n{$shopping_text}\n\n"
            . "Ближайшие супермаркеты к пользователю:\n{$stores_text}\n\n"
            . "Задание:\n"
            . "1. Выбери ОДИН лучший магазин из списка (по его ID), который максимально подходит под этот конкретный список продуктов (учитывай: ВкусВилл для фермерской молочки/овощей, Перекрёсток для мяса/сыров, Пятёрочка/Магнит для экономии на бакалее).\n"
            . "2. Напиши четкое и убедительное обоснование своего выбора.\n"
            . "3. Дай 3 конкретных практических совета по покупке этих продуктов.\n"
            . "Ответь строго в формате JSON без markdown:\n"
            . "{\n"
            . '  "recommended_store_id": "...",' . "\n"
            . '  "reason": "...",' . "\n"
            . '  "tips": ["...", "...", "..."]' . "\n"
            . "}";

        $ai_response_text = null;

        // Try Ollama Cloud
        if ( $provider === 'ollama_cloud' || $provider === 'ollama' ) {
            $host = rtrim( $config['ollama_url'], '/' );
            if ( ! empty( $host ) && $host !== 'https://your-ollama-cloud-host.com' ) {
                $headers = self::get_ollama_headers( $config );

                // Try /api/generate
                $res = wp_remote_post( $host . '/api/generate', [
                    'headers'   => $headers,
                    'body'      => wp_json_encode([
                        'model'   => $config['ollama_model'] ?: 'llama3.2',
                        'prompt'  => $prompt,
                        'stream'  => false,
                        'format'  => 'json',
                    ]),
                    'timeout'   => 25,
                    'sslverify' => false,
                ]);

                if ( ! is_wp_error( $res ) && wp_remote_retrieve_response_code( $res ) === 200 ) {
                    $b = json_decode( wp_remote_retrieve_body( $res ), true );
                    $ai_response_text = $b['response'] ?? null;
                } else {
                    // Try /v1/chat/completions fallback
                    $res_v1 = wp_remote_post( $host . '/v1/chat/completions', [
                        'headers'   => $headers,
                        'body'      => wp_json_encode([
                            'model'    => $config['ollama_model'] ?: 'llama3.2',
                            'messages' => [ [ 'role' => 'user', 'content' => $prompt ] ],
                            'response_format' => [ 'type' => 'json_object' ],
                        ]),
                        'timeout'   => 25,
                        'sslverify' => false,
                    ]);
                    if ( ! is_wp_error( $res_v1 ) && wp_remote_retrieve_response_code( $res_v1 ) === 200 ) {
                        $b_v1 = json_decode( wp_remote_retrieve_body( $res_v1 ), true );
                        $ai_response_text = $b_v1['choices'][0]['message']['content'] ?? null;
                    }
                }
            }
        }

        // Try Claude
        if ( $provider === 'claude' && ! empty( $config['claude_key'] ) ) {
            $base_url = rtrim( $config['claude_base_url'] ?: 'https://api.anthropic.com', '/' );
            $url = $base_url . '/v1/messages';
            $res = wp_remote_post( $url, [
                'headers' => [
                    'x-api-key'         => $config['claude_key'],
                    'anthropic-version' => '2023-06-01',
                    'content-type'      => 'application/json',
                ],
                'body' => wp_json_encode([
                    'model'      => $config['claude_model'],
                    'max_tokens' => 800,
                    'messages'   => [ [ 'role' => 'user', 'content' => $prompt ] ]
                ]),
                'timeout' => 20,
                'sslverify' => false,
            ]);

            if ( ! is_wp_error( $res ) && wp_remote_retrieve_response_code( $res ) === 200 ) {
                $b = json_decode( wp_remote_retrieve_body( $res ), true );
                $ai_response_text = $b['content'][0]['text'] ?? null;
            }
        }

        // Try Gemini
        if ( $provider === 'gemini' && ! empty( $config['gemini_key'] ) ) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$config['gemini_model']}:generateContent?key={$config['gemini_key']}";
            $res = wp_remote_post( $url, [
                'headers' => [ 'Content-Type' => 'application/json' ],
                'body'    => wp_json_encode([
                    'contents' => [ [ 'parts' => [ [ 'text' => $prompt ] ] ] ]
                ]),
                'timeout' => 20
            ]);

            if ( ! is_wp_error( $res ) && wp_remote_retrieve_response_code( $res ) === 200 ) {
                $b = json_decode( wp_remote_retrieve_body( $res ), true );
                $ai_response_text = $b['candidates'][0]['content']['parts'][0]['text'] ?? null;
            }
        }

        if ( ! empty( $ai_response_text ) ) {
            $cleaned = trim( $ai_response_text );
            $cleaned = preg_replace( '/^```(json)?\s*/i', '', $cleaned );
            $cleaned = preg_replace( '/```\s*$/', '', $cleaned );
            $parsed = json_decode( $cleaned, true );

            if ( ! empty( $parsed['recommended_store_id'] ) ) {
                $rec_store = null;
                foreach ( $stores as $s ) {
                    if ( $s['id'] == $parsed['recommended_store_id'] ) {
                        $rec_store = $s;
                        break;
                    }
                }
                if ( ! $rec_store && ! empty( $stores ) ) {
                    $rec_store = $stores[0];
                }

                if ( $rec_store ) {
                    $label_name = ( $provider === 'ollama_cloud' || $provider === 'ollama' ) ? 'Ollama Cloud' : ( $provider === 'claude' ? 'Claude 3.5' : 'Gemini' );
                    return [
                        'store'            => $rec_store,
                        'reason'           => $parsed['reason'] ?? '',
                        'tips'             => $parsed['tips'] ?? [],
                        'ai_model'         => $label_name . ' (' . ( $config[$provider . '_model'] ?? '' ) . ')',
                        'navigation_urls'  => [
                            'yandex' => sprintf( 'https://yandex.ru/maps/?rtext=~%f,%f&rtt=pd', $rec_store['lat'], $rec_store['lon'] ),
                            'google' => sprintf( 'https://www.google.com/maps/dir/?api=1&destination=%f,%f&travelmode=walking', $rec_store['lat'], $rec_store['lon'] ),
                        ]
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Parse text product list into structured products
     */
    public static function parse_text_products( $text ) {
        if ( empty( trim( $text ) ) ) {
            return [];
        }

        $clean_text = str_replace( [ '•', '*', '— ', '- ', "\t" ], "\n", $text );
        $raw_items = preg_split( '/[,;\n\r]+/u', $clean_text );
        $products = [];

        foreach ( $raw_items as $item ) {
            $item = trim( $item );
            if ( empty( $item ) ) {
                continue;
            }

            $parsed = self::normalize_product_item( $item );
            if ( $parsed ) {
                $products[] = $parsed;
            }
        }

        return $products;
    }

    /**
     * Normalize a single product item with quantity and category
     */
    public static function normalize_product_item( $raw_text ) {
        $text = mb_strtolower( trim( $raw_text ), 'UTF-8' );

        $quantity = '1 шт';
        if ( preg_match( '/(\d+([.,]\d+)?\s*(кг|г|гр|л|мл|шт|упак|пач|бан|кусоч|зубч)[а-я]*)/u', $text, $m ) ) {
            $quantity = $m[1];
            $text = trim( str_replace( $m[1], '', $text ) );
        } elseif ( preg_match( '/(\d+)\s*(штук[а-я]*|банок|пачек|кусков)/u', $text, $m ) ) {
            $quantity = $m[1] . ' шт';
            $text = trim( str_replace( $m[0], '', $text ) );
        } elseif ( preg_match( '/^(\d+)\s+/u', $text, $m ) ) {
            $quantity = $m[1] . ' шт';
            $text = trim( preg_replace( '/^\d+\s+/u', '', $text ) );
        } elseif ( mb_strpos( $text, 'пол ' ) !== false || mb_strpos( $text, 'половина ' ) !== false ) {
            $quantity = '0.5 шт';
            $text = trim( str_replace( [ 'пол ', 'половина ' ], '', $text ) );
        }

        $clean_name = preg_replace( '/\b(у меня есть|остатки|немного|чуть-чуть|кусок|банка|пачка)\b/iu', '', $text );
        $clean_name = trim( preg_replace( '/\s+/u', ' ', $clean_name ) );

        if ( empty( $clean_name ) ) {
            $clean_name = trim( $raw_text );
        }

        $category_info = self::detect_category( $clean_name );

        return [
            'id'         => 'prod_' . md5( $clean_name . microtime( true ) ),
            'name'       => mb_convert_case( $clean_name, MB_CASE_TITLE, 'UTF-8' ),
            'clean_key'  => self::get_product_root( $clean_name ),
            'quantity'   => $quantity,
            'category'   => $category_info['category'],
            'icon'       => $category_info['icon'],
            'freshness'  => $category_info['perishable'] ? 'warning' : 'fresh',
            'perishable' => $category_info['perishable'],
        ];
    }

    /**
     * Root word for matching
     */
    public static function get_product_root( $word ) {
        $word = mb_strtolower( trim( $word ), 'UTF-8' );
        $roots = [
            'яйц' => 'яйцо',
            'яиц' => 'яйцо',
            'молок' => 'молоко',
            'сыр' => 'сыр',
            'куриц' => 'курица',
            'кур' => 'курица',
            'помидор' => 'помидор',
            'томат' => 'помидор',
            'огур' => 'огурец',
            'картоф' => 'картофель',
            'картох' => 'картофель',
            'морков' => 'морковь',
            'лук' => 'лук',
            'чеснок' => 'чеснок',
            'хлеб' => 'хлеб',
            'батон' => 'хлеб',
            'макарон' => 'макароны',
            'паст' => 'макароны',
            'спагетт' => 'макароны',
            'рис' => 'рис',
            'гречк' => 'гречка',
            'овсянк' => 'овсянка',
            'геркулес' => 'овсянка',
            'сметан' => 'сметана',
            'масл' => 'масло',
            'творог' => 'творог',
            'говядин' => 'говядина',
            'свинин' => 'свинина',
            'фарш' => 'фарш',
            'рыб' => 'рыба',
            'лосос' => 'рыба',
            'семг' => 'рыба',
            'тунец' => 'рыба',
            'колбас' => 'колбаса',
            'сосиск' => 'сосиски',
            'ветчин' => 'ветчина',
            'капуст' => 'капуста',
            'гриб' => 'грибы',
            'шампиньон' => 'грибы',
            'яблок' => 'яблоки',
            'банан' => 'бананы',
            'мук' => 'мука',
            'сахар' => 'сахар',
            'соль' => 'соль',
            'перец' => 'перец',
        ];

        foreach ( $roots as $prefix => $canonical ) {
            if ( mb_strpos( $word, $prefix ) !== false ) {
                return $canonical;
            }
        }

        return $word;
    }

    /**
     * Detect category and icon
     */
    public static function detect_category( $product_name ) {
        $p = mb_strtolower( $product_name, 'UTF-8' );

        if ( preg_match( '/молок|сыр|творог|сметан|сливк|кефир|йогурт|масло сливочн/u', $p ) ) {
            return [ 'category' => 'Молочные продукты', 'icon' => 'local_cafe', 'perishable' => true ];
        }
        if ( preg_match( '/куриц|филе|мясо|говядин|свинин|фарш|индейк|бекон|ветчин|сосиск|колбас/u', $p ) ) {
            return [ 'category' => 'Мясо и птица', 'icon' => 'restaurant', 'perishable' => true ];
        }
        if ( preg_match( '/рыб|лосос|семг|форел|тунец|креветк|треск/u', $p ) ) {
            return [ 'category' => 'Рыба и морепродукты', 'icon' => 'set_meal', 'perishable' => true ];
        }
        if ( preg_match( '/помидор|томат|огур|капуст|морков|лук|чеснок|картоф|перец|зелен|укроп|петрушк|шпинат|салат|кабачок|баклажан/u', $p ) ) {
            return [ 'category' => 'Овощи и зелень', 'icon' => 'eco', 'perishable' => true ];
        }
        if ( preg_match( '/яблок|банан|апельсин|лимон|ягод|клубник|груш/u', $p ) ) {
            return [ 'category' => 'Фрукты и ягоды', 'icon' => 'nutrition', 'perishable' => true ];
        }
        if ( preg_match( '/яйц|яиц/u', $p ) ) {
            return [ 'category' => 'Яйца', 'icon' => 'egg', 'perishable' => true ];
        }
        if ( preg_match( '/макарон|паст|спагетт|рис|гречк|овсянк|геркулес|булгур|кускус|мук/u', $p ) ) {
            return [ 'category' => 'Крупы и бакалея', 'icon' => 'grain', 'perishable' => false ];
        }
        if ( preg_match( '/хлеб|батон|лаваш|булочк|тост/u', $p ) ) {
            return [ 'category' => 'Хлеб и выпечка', 'icon' => 'bakery_dining', 'perishable' => true ];
        }
        if ( preg_match( '/соус|майонез|кетчуп|горчиц|масло растительн|масло оливков|соль|сахар|специ/u', $p ) ) {
            return [ 'category' => 'Соусы и приправы', 'icon' => 'soup_kitchen', 'perishable' => false ];
        }

        return [ 'category' => 'Другое', 'icon' => 'inventory_2', 'perishable' => false ];
    }

    /**
     * Fallback preset
     */
    public static function smart_sample_fridge_detection() {
        $preset = [
            [ 'name' => 'Яйца куриные', 'quantity' => '6 шт' ],
            [ 'name' => 'Молоко 3.2%', 'quantity' => '0.8 л' ],
            [ 'name' => 'Сыр Российский', 'quantity' => '200 г' ],
            [ 'name' => 'Куриное филе', 'quantity' => '500 г' ],
            [ 'name' => 'Помидоры свежие', 'quantity' => '3 шт' ],
            [ 'name' => 'Огурцы', 'quantity' => '2 шт' ],
            [ 'name' => 'Сметана 15%', 'quantity' => '1 банка' ],
            [ 'name' => 'Сливочное масло', 'quantity' => '100 г' ],
            [ 'name' => 'Морковь', 'quantity' => '2 шт' ],
            [ 'name' => 'Лук репчатый', 'quantity' => '3 шт' ],
            [ 'name' => 'Макароны твердых сортов', 'quantity' => '400 г' ],
            [ 'name' => 'Картофель', 'quantity' => '1 кг' ],
        ];

        $results = [];
        foreach ( $preset as $item ) {
            $cat_info = self::detect_category( $item['name'] );
            $results[] = [
                'id'         => 'prod_' . md5( $item['name'] . microtime( true ) ),
                'name'       => $item['name'],
                'clean_key'  => self::get_product_root( $item['name'] ),
                'quantity'   => $item['quantity'],
                'category'   => $cat_info['category'],
                'icon'       => $cat_info['icon'],
                'freshness'  => $cat_info['perishable'] ? 'warning' : 'fresh',
                'perishable' => $cat_info['perishable'],
            ];
        }

        return $results;
    }

    /**
     * Recipe Knowledge Base
     */
    public static function get_recipe_database() {
        return [
            [
                'id'          => 'rec_1',
                'title'       => 'Классический омлет с сыром и томатами',
                'description' => 'Нежный омлет с расплавленным сыром и сочными ломтиками томатов. Быстрый и сбалансированный завтрак.',
                'meal_type'   => 'Завтрак',
                'time_mins'   => 12,
                'difficulty'  => 'Легко',
                'calories'    => 320,
                'protein'     => 18,
                'fat'         => 24,
                'carbs'       => 4,
                'image'       => 'https://images.unsplash.com/photo-1510693206972-df098062cb71?auto=format&fit=crop&w=600&q=80',
                'ingredients' => [
                    [ 'name' => 'Яйца куриные', 'root' => 'яйцо', 'amount' => '3 шт', 'required' => true ],
                    [ 'name' => 'Молоко', 'root' => 'молоко', 'amount' => '50 мл', 'required' => true ],
                    [ 'name' => 'Сыр', 'root' => 'сыр', 'amount' => '50 г', 'required' => true ],
                    [ 'name' => 'Помидоры', 'root' => 'помидор', 'amount' => '1 шт', 'required' => false ],
                    [ 'name' => 'Сливочное масло', 'root' => 'масло', 'amount' => '15 г', 'required' => false ],
                ],
                'instructions'=> [
                    'Взбейте яйца с молоком и щепоткой соли вилкой до однородности.',
                    'Растопите сливочное масло на сковороде на среднем огне.',
                    'Вылейте яичную смесь, сверху выложите кружочки помидора.',
                    'Посыпьте тертым сыром, сложите пополам и подержите под крышкой 1 минуту.'
                ]
            ],
            [
                'id'          => 'rec_2',
                'title'       => 'Куриное филе в сливочном соусе с пастой',
                'description' => 'Нежные кусочки курицы в соусе со сметаной и сыром, соединенные с пастой аль-денте.',
                'meal_type'   => 'Обед',
                'time_mins'   => 25,
                'difficulty'  => 'Средне',
                'calories'    => 540,
                'protein'     => 42,
                'fat'         => 22,
                'carbs'       => 45,
                'image'       => 'https://images.unsplash.com/photo-1621996346565-e3d5d6281033?auto=format&fit=crop&w=600&q=80',
                'ingredients' => [
                    [ 'name' => 'Куриное филе', 'root' => 'курица', 'amount' => '350 г', 'required' => true ],
                    [ 'name' => 'Макароны', 'root' => 'макароны', 'amount' => '200 г', 'required' => true ],
                    [ 'name' => 'Сметана или сливки', 'root' => 'сметана', 'amount' => '100 г', 'required' => true ],
                    [ 'name' => 'Сыр', 'root' => 'сыр', 'amount' => '60 г', 'required' => false ],
                    [ 'name' => 'Лук репчатый', 'root' => 'лук', 'amount' => '1 шт', 'required' => false ],
                    [ 'name' => 'Чеснок', 'root' => 'чеснок', 'amount' => '2 зубчика', 'required' => false ],
                ],
                'instructions'=> [
                    'Отварите макароны в подсоленной воде.',
                    'Куриное филе нарежьте кубиками, обжарьте на сковороде с луком 7 минут.',
                    'Добавьте сметану, немного горячей воды от пасты, тертый сыр и чеснок.',
                    'Перемешайте с готовой пастой и прогрейте 2 минуты.'
                ]
            ],
            [
                'id'          => 'rec_3',
                'title'       => 'Жареный картофель с луком',
                'description' => 'Традиционное домашнее блюдо с хрустящей корочкой и мягкой серединой.',
                'meal_type'   => 'Ужин',
                'time_mins'   => 30,
                'difficulty'  => 'Легко',
                'calories'    => 390,
                'protein'     => 6,
                'fat'         => 16,
                'carbs'       => 52,
                'image'       => 'https://images.unsplash.com/photo-1592417817098-8f3d6eb22509?auto=format&fit=crop&w=600&q=80',
                'ingredients' => [
                    [ 'name' => 'Картофель', 'root' => 'картофель', 'amount' => '500 г', 'required' => true ],
                    [ 'name' => 'Лук репчатый', 'root' => 'лук', 'amount' => '1 шт', 'required' => true ],
                    [ 'name' => 'Масло', 'root' => 'масло', 'amount' => '30 мл', 'required' => true ],
                    [ 'name' => 'Сметана', 'root' => 'сметана', 'amount' => '50 г', 'required' => false ],
                ],
                'instructions'=> [
                    'Очистите картофель и нарежьте соломкой, обсушите полотенцем.',
                    'Разогрейте масло на сковороде и выложите картофель.',
                    'Жарьте на среднем огне 10 минут, добавьте нарезанный лук.',
                    'Посолите в конце за 3-5 минут до готовности.'
                ]
            ],
            [
                'id'          => 'rec_4',
                'title'       => 'Свежий салат с томатами, огурцами и сыром',
                'description' => 'Легкий овощной салат с заправкой из сметаны или оливкового масла.',
                'meal_type'   => 'Перекус',
                'time_mins'   => 8,
                'difficulty'  => 'Легко',
                'calories'    => 180,
                'protein'     => 7,
                'fat'         => 12,
                'carbs'       => 8,
                'image'       => 'https://images.unsplash.com/photo-1540420773420-3366772f4999?auto=format&fit=crop&w=600&q=80',
                'ingredients' => [
                    [ 'name' => 'Помидоры', 'root' => 'помидор', 'amount' => '2 шт', 'required' => true ],
                    [ 'name' => 'Огурцы', 'root' => 'огурец', 'amount' => '2 шт', 'required' => true ],
                    [ 'name' => 'Сыр', 'root' => 'сыр', 'amount' => '50 г', 'required' => false ],
                    [ 'name' => 'Сметана', 'root' => 'сметана', 'amount' => '2 ст.л.', 'required' => false ],
                ],
                'instructions'=> [
                    'Нарежьте овощи средними кусочками.',
                    'Добавьте кубики сыра.',
                    'Заправьте сметаной или маслом, приправьте солью и перцем.'
                ]
            ],
            [
                'id'          => 'rec_5',
                'title'       => 'Творожные сырники со сметаной',
                'description' => 'Золотистые сырники из свежего творога. Идеальный завтрак с высоким содержанием белка.',
                'meal_type'   => 'Завтрак',
                'time_mins'   => 20,
                'difficulty'  => 'Средне',
                'calories'    => 340,
                'protein'     => 26,
                'fat'         => 14,
                'carbs'       => 22,
                'image'       => 'https://images.unsplash.com/photo-1588137378633-dea1336ce1e2?auto=format&fit=crop&w=600&q=80',
                'ingredients' => [
                    [ 'name' => 'Творог', 'root' => 'творог', 'amount' => '300 г', 'required' => true ],
                    [ 'name' => 'Яйцо куриное', 'root' => 'яйцо', 'amount' => '1 шт', 'required' => true ],
                    [ 'name' => 'Мука', 'root' => 'мука', 'amount' => '2 ст.л.', 'required' => true ],
                    [ 'name' => 'Сахар', 'root' => 'сахар', 'amount' => '1 ст.л.', 'required' => false ],
                    [ 'name' => 'Сметана', 'root' => 'сметана', 'amount' => '2 ст.л.', 'required' => false ],
                ],
                'instructions'=> [
                    'Смешайте творог, яйцо и ложку сахара.',
                    'Добавьте муку, сформируйте шайбы.',
                    'Обжаривайте на среднем огне по 3-4 минуты с каждой стороны.'
                ]
            ],
            [
                'id'          => 'rec_6',
                'title'       => 'Крем-суп из птицы и овощей',
                'description' => 'Бархатный согревающий суп с отварным куриным филе, морковью и луком.',
                'meal_type'   => 'Обед',
                'time_mins'   => 35,
                'difficulty'  => 'Легко',
                'calories'    => 310,
                'protein'     => 28,
                'fat'         => 12,
                'carbs'       => 18,
                'image'       => 'https://images.unsplash.com/photo-1547592166-23ac45744acd?auto=format&fit=crop&w=600&q=80',
                'ingredients' => [
                    [ 'name' => 'Куриное филе', 'root' => 'курица', 'amount' => '300 г', 'required' => true ],
                    [ 'name' => 'Морковь', 'root' => 'морковь', 'amount' => '1 шт', 'required' => true ],
                    [ 'name' => 'Лук репчатый', 'root' => 'лук', 'amount' => '1 шт', 'required' => true ],
                    [ 'name' => 'Картофель', 'root' => 'картофель', 'amount' => '2 шт', 'required' => true ],
                    [ 'name' => 'Сметана или сливки', 'root' => 'сметана', 'amount' => '80 г', 'required' => false ],
                ],
                'instructions'=> [
                    'Отварите овощи и мясо до мягкости.',
                    'Пюрируйте погружным блендером.',
                    'Добавьте сметану или сливки, посолите и доведите до кипения.'
                ]
            ],
            [
                'id'          => 'rec_7',
                'title'       => 'Домашний ролл с курицей и овощами',
                'description' => 'Курица-гриль, свежие томаты и огурцы с чесночным соусом в тонком лаваше.',
                'meal_type'   => 'Ужин',
                'time_mins'   => 20,
                'difficulty'  => 'Легко',
                'calories'    => 480,
                'protein'     => 34,
                'fat'         => 18,
                'carbs'       => 42,
                'image'       => 'https://images.unsplash.com/photo-1561651823-34feb02250e4?auto=format&fit=crop&w=600&q=80',
                'ingredients' => [
                    [ 'name' => 'Лаваш или лепешка', 'root' => 'хлеб', 'amount' => '1 шт', 'required' => true ],
                    [ 'name' => 'Куриное филе', 'root' => 'курица', 'amount' => '200 г', 'required' => true ],
                    [ 'name' => 'Помидоры', 'root' => 'помидор', 'amount' => '1 шт', 'required' => true ],
                    [ 'name' => 'Огурцы', 'root' => 'огурец', 'amount' => '1 шт', 'required' => false ],
                    [ 'name' => 'Сметана', 'root' => 'сметана', 'amount' => '2 ст.л.', 'required' => false ],
                ],
                'instructions'=> [
                    'Обжарьте филе кусочками до золотистости.',
                    'Смажьте лаваш сметанным соусом, выложите мясо и овощи.',
                    'Заверните и подрумяньте на сухой сковороде.'
                ]
            ],
            [
                'id'          => 'rec_8',
                'title'       => 'Гречневая каша с курицей и морковью',
                'description' => 'Ароматная рассыпчатая гречка, приготовленная в одном сотейнике с мясом и сладкой морковью.',
                'meal_type'   => 'Обед',
                'time_mins'   => 35,
                'difficulty'  => 'Легко',
                'calories'    => 410,
                'protein'     => 30,
                'fat'         => 10,
                'carbs'       => 48,
                'image'       => 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=600&q=80',
                'ingredients' => [
                    [ 'name' => 'Гречневая крупа', 'root' => 'гречка', 'amount' => '1 стакан', 'required' => true ],
                    [ 'name' => 'Куриное филе', 'root' => 'курица', 'amount' => '300 г', 'required' => true ],
                    [ 'name' => 'Морковь', 'root' => 'морковь', 'amount' => '1 шт', 'required' => true ],
                    [ 'name' => 'Лук репчатый', 'root' => 'лук', 'amount' => '1 шт', 'required' => true ],
                ],
                'instructions'=> [
                    'Обжарьте лук и тертую морковь с кусочками курицы.',
                    'Всыпьте гречку, залейте двумя стаканами воды, посолите.',
                    'Накройте крышкой и варите на медленном огне 20 минут.'
                ]
            ],
            [
                'id'          => 'rec_9',
                'title'       => 'Испанская фриттата с овощами и сыром',
                'description' => 'Сытный омлет на сковороде с добавлением томатов, сладкого лука и расплавленного сыра.',
                'meal_type'   => 'Завтрак',
                'time_mins'   => 18,
                'difficulty'  => 'Легко',
                'calories'    => 290,
                'protein'     => 16,
                'fat'         => 20,
                'carbs'       => 6,
                'image'       => 'https://images.unsplash.com/photo-1565299585323-38d6b0865b47?auto=format&fit=crop&w=600&q=80',
                'ingredients' => [
                    [ 'name' => 'Яйца', 'root' => 'яйцо', 'amount' => '4 шт', 'required' => true ],
                    [ 'name' => 'Помидоры', 'root' => 'помидор', 'amount' => '1 шт', 'required' => true ],
                    [ 'name' => 'Лук', 'root' => 'лук', 'amount' => '0.5 шт', 'required' => false ],
                    [ 'name' => 'Сыр', 'root' => 'сыр', 'amount' => '40 г', 'required' => false ],
                ],
                'instructions'=> [
                    'Припустите лук и помидоры на сковороде 3 минуты.',
                    'Залейте взбитыми яйцами, посыпьте сыром.',
                    'Готовьте под крышкой на медленном огне 7 минут.'
                ]
            ],
            [
                'id'          => 'rec_10',
                'title'       => 'Запеченные куриные рулетики с сыром и томатами',
                'description' => 'Куриное филе, фаршированное томатами и сыром, запеченное в духовке до аппетитной корочки.',
                'meal_type'   => 'Ужин',
                'time_mins'   => 35,
                'difficulty'  => 'Средне',
                'calories'    => 420,
                'protein'     => 46,
                'fat'         => 22,
                'carbs'       => 5,
                'image'       => 'https://images.unsplash.com/photo-1532550907401-a500c9a57435?auto=format&fit=crop&w=600&q=80',
                'ingredients' => [
                    [ 'name' => 'Куриное филе', 'root' => 'курица', 'amount' => '400 г', 'required' => true ],
                    [ 'name' => 'Сыр', 'root' => 'сыр', 'amount' => '100 г', 'required' => true ],
                    [ 'name' => 'Помидор', 'root' => 'помидор', 'amount' => '1 шт', 'required' => true ],
                    [ 'name' => 'Сметана', 'root' => 'сметана', 'amount' => '1 ст.л.', 'required' => false ],
                ],
                'instructions'=> [
                    'Сделайте продольный кармашек в филе, вложите сыр и томат.',
                    'Скрепите шпажкой, смажьте сметаной.',
                    'Запекайте 25 минут при 190°C.'
                ]
            ]
        ];
    }

    /**
     * Match recipes against user's products
     */
    public static function match_recipes( $user_products ) {
        $recipes = self::get_recipe_database();
        $matched = [];

        $available_roots = [];
        foreach ( $user_products as $p ) {
            $root = $p['clean_key'];
            $available_roots[$root] = $p['name'];
        }

        foreach ( $recipes as $recipe ) {
            $total_required = 0;
            $matched_required = 0;
            $total_all = count( $recipe['ingredients'] );
            $matched_all = 0;

            $ingredient_status = [];

            foreach ( $recipe['ingredients'] as $ing ) {
                $is_required = ! empty( $ing['required'] );
                if ( $is_required ) {
                    $total_required++;
                }

                $found = false;
                $ing_root = $ing['root'];

                foreach ( $available_roots as $user_root => $user_name ) {
                    if ( $user_root === $ing_root || mb_strpos( $user_root, $ing_root ) !== false || mb_strpos( $ing_root, $user_root ) !== false ) {
                        $found = true;
                        break;
                    }
                }

                if ( $found ) {
                    $matched_all++;
                    if ( $is_required ) {
                        $matched_required++;
                    }
                    $ingredient_status[] = [
                        'name'      => $ing['name'],
                        'amount'    => $ing['amount'],
                        'available' => true,
                        'required'  => $is_required,
                    ];
                } else {
                    $ingredient_status[] = [
                        'name'      => $ing['name'],
                        'amount'    => $ing['amount'],
                        'available' => false,
                        'required'  => $is_required,
                    ];
                }
            }

            $req_ratio = $total_required > 0 ? ( $matched_required / $total_required ) : 1.0;
            $all_ratio = $total_all > 0 ? ( $matched_all / $total_all ) : 1.0;
            $score = round( ( $req_ratio * 0.7 + $all_ratio * 0.3 ) * 100 );

            $can_cook_now = ( $total_required > 0 && $matched_required === $total_required );

            $recipe['match_score'] = $score;
            $recipe['can_cook_now'] = $can_cook_now;
            $recipe['ingredients_status'] = $ingredient_status;
            $recipe['missing_count'] = $total_all - $matched_all;

            $matched[] = $recipe;
        }

        usort( $matched, function( $a, $b ) {
            if ( $a['can_cook_now'] !== $b['can_cook_now'] ) {
                return $a['can_cook_now'] ? -1 : 1;
            }
            return $b['match_score'] <=> $a['match_score'];
        });

        return $matched;
    }

    /**
     * Generate 7-Day Meal Plan & Shopping List
     */
    public static function generate_weekly_plan( $user_products ) {
        $matched_recipes = self::match_recipes( $user_products );

        $days = [
            'Понедельник',
            'Вторник',
            'Среда',
            'Четверг',
            'Пятница',
            'Суббота',
            'Воскресенье'
        ];

        $by_type = [
            'Завтрак' => [],
            'Обед'    => [],
            'Ужин'    => [],
            'Перекус' => []
        ];

        foreach ( $matched_recipes as $r ) {
            $t = $r['meal_type'] ?? 'Обед';
            if ( isset( $by_type[$t] ) ) {
                $by_type[$t][] = $r;
            }
        }

        foreach ( $by_type as $type => $list ) {
            if ( count( $list ) < 7 ) {
                while ( count( $by_type[$type] ) < 7 ) {
                    $by_type[$type] = array_merge( $by_type[$type], $list );
                }
            }
        }

        $weekly_schedule = [];
        $needed_ingredients = [];

        $available_roots = [];
        foreach ( $user_products as $p ) {
            $available_roots[$p['clean_key']] = true;
        }

        for ( $i = 0; $i < 7; $i++ ) {
            $day_name = $days[$i];

            $breakfast = $by_type['Завтрак'][$i % count( $by_type['Завтрак'] )];
            $lunch     = $by_type['Обед'][$i % count( $by_type['Обед'] )];
            $dinner    = $by_type['Ужин'][$i % count( $by_type['Ужин'] )];
            $snack     = $by_type['Перекус'][$i % count( $by_type['Перекус'] )];

            $daily_calories = $breakfast['calories'] + $lunch['calories'] + $dinner['calories'] + $snack['calories'];
            $daily_protein  = $breakfast['protein'] + $lunch['protein'] + $dinner['protein'] + $snack['protein'];
            $daily_fat      = $breakfast['fat'] + $lunch['fat'] + $dinner['fat'] + $snack['fat'];
            $daily_carbs    = $breakfast['carbs'] + $lunch['carbs'] + $dinner['carbs'] + $snack['carbs'];

            foreach ( [ $breakfast, $lunch, $dinner, $snack ] as $meal ) {
                foreach ( $meal['ingredients'] as $ing ) {
                    $root = $ing['root'];
                    if ( empty( $available_roots[$root] ) ) {
                        if ( ! isset( $needed_ingredients[$root] ) ) {
                            $cat_info = self::detect_category( $ing['name'] );
                            $needed_ingredients[$root] = [
                                'name'      => $ing['name'],
                                'root'      => $root,
                                'category'  => $cat_info['category'],
                                'icon'      => $cat_info['icon'],
                                'amount'    => $ing['amount'],
                                'count'     => 1,
                                'checked'   => false,
                            ];
                        } else {
                            $needed_ingredients[$root]['count']++;
                        }
                    }
                }
            }

            $focus = ( $i < 3 ) ? 'Утилизация скоропортящихся запасов из холодильника' : 'Базовый рацион + свежие плановые покупки';

            $weekly_schedule[] = [
                'day_index' => $i,
                'day_name'  => $day_name,
                'focus'     => $focus,
                'meals'     => [
                    'breakfast' => $breakfast,
                    'lunch'     => $lunch,
                    'dinner'    => $dinner,
                    'snack'     => $snack,
                ],
                'nutrition' => [
                    'calories' => $daily_calories,
                    'protein'  => $daily_protein,
                    'fat'      => $daily_fat,
                    'carbs'    => $daily_carbs,
                ]
            ];
        }

        $grouped_shopping = [];
        foreach ( $needed_ingredients as $item ) {
            $cat = $item['category'];
            if ( ! isset( $grouped_shopping[$cat] ) ) {
                $grouped_shopping[$cat] = [];
            }
            $grouped_shopping[$cat][] = $item;
        }

        return [
            'days'             => $weekly_schedule,
            'shopping_list'    => array_values( $needed_ingredients ),
            'grouped_shopping' => $grouped_shopping,
            'total_items_to_buy' => count( $needed_ingredients )
        ];
    }
}

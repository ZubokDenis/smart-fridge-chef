<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class SFC_Store_Locator {

    public static function find_and_recommend_stores( $lat, $lon, $shopping_list = [] ) {
        $lat = floatval( $lat );
        $lon = floatval( $lon );

        if ( empty( $lat ) || empty( $lon ) ) {
            return [
                'user_location'  => null,
                'stores'         => [],
                'recommendation' => null,
                'needs_location' => true,
                'message'        => 'Пожалуйста, поделитесь вашим местоположением или укажите город, чтобы найти ближайшие магазины.'
            ];
        }

        $cache_key = 'sfc_real_stores_' . md5( round( $lat, 3 ) . '_' . round( $lon, 3 ) );
        $cached_stores = get_transient( $cache_key );

        if ( false !== $cached_stores && is_array( $cached_stores ) ) {
            $stores = $cached_stores;
        } else {
            $stores = self::query_nominatim_real_stores( $lat, $lon );
            if ( ! empty( $stores ) ) {
                set_transient( $cache_key, $stores, 2 * HOUR_IN_SECONDS );
            }
        }

        foreach ( $stores as &$store ) {
            $dist_meters = self::haversine_distance( $lat, $lon, $store['lat'], $store['lon'] );
            $store['distance_m'] = round( $dist_meters );
            $store['walking_time_mins'] = max( 1, round( $dist_meters / 80 ) );
        }
        unset( $store );

        usort( $stores, function( $a, $b ) {
            return $a['distance_m'] <=> $b['distance_m'];
        });

        if ( empty( $stores ) ) {
            return [
                'user_location'  => [ 'lat' => $lat, 'lon' => $lon ],
                'stores'         => [],
                'recommendation' => null,
                'message'        => 'В радиусе 3-5 км на карте не найдено супермаркетов. Попробуйте указать другой адрес или район.'
            ];
        }

        $recommendation = SFC_AI_Engine::analyze_stores_with_ai( $stores, $shopping_list, [ 'lat' => $lat, 'lon' => $lon ] );

        if ( empty( $recommendation ) ) {
            $recommendation = self::calculate_recommendation( $stores, $shopping_list );
            $recommendation['ai_model'] = 'Экспертный кулинарный алгоритм';
        }

        return [
            'user_location'  => [ 'lat' => $lat, 'lon' => $lon ],
            'stores'         => $stores,
            'recommendation' => $recommendation,
        ];
    }

    public static function haversine_distance( $lat1, $lon1, $lat2, $lon2 ) {
        $earth_radius = 6371000;

        $dLat = deg2rad( $lat2 - $lat1 );
        $dLon = deg2rad( $lon2 - $lon1 );

        $a = sin( $dLat / 2 ) * sin( $dLat / 2 ) +
             cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) *
             sin( $dLon / 2 ) * sin( $dLon / 2 );

        $c = 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );
        return $earth_radius * $c;
    }

    public static function query_nominatim_real_stores( $lat, $lon ) {
        $dlat = 0.035;
        $dlon = 0.040 / max( 0.2, cos( deg2rad( $lat ) ) );

        $left   = $lon - $dlon;
        $right  = $lon + $dlon;
        $top    = $lat + $dlat;
        $bottom = $lat - $dlat;
        $viewbox = "{$left},{$top},{$right},{$bottom}";

        $raw_items = [];

        $url_supermarket = 'https://nominatim.openstreetmap.org/search?format=json&q=supermarket&viewbox=' . $viewbox . '&bounded=1&limit=15&addressdetails=1';
        $items1 = self::fetch_osm_json( $url_supermarket );
        if ( ! empty( $items1 ) && is_array( $items1 ) ) {
            $raw_items = array_merge( $raw_items, $items1 );
        }

        if ( count( $raw_items ) < 8 ) {
            $url_grocery = 'https://nominatim.openstreetmap.org/search?format=json&q=' . urlencode( 'продукты' ) . '&viewbox=' . $viewbox . '&bounded=1&limit=10&addressdetails=1';
            $items2 = self::fetch_osm_json( $url_grocery );
            if ( ! empty( $items2 ) && is_array( $items2 ) ) {
                $raw_items = array_merge( $raw_items, $items2 );
            }
        }

        if ( count( $raw_items ) < 4 ) {
            $url_grocery_en = 'https://nominatim.openstreetmap.org/search?format=json&q=grocery&viewbox=' . $viewbox . '&bounded=1&limit=10&addressdetails=1';
            $items3 = self::fetch_osm_json( $url_grocery_en );
            if ( ! empty( $items3 ) && is_array( $items3 ) ) {
                $raw_items = array_merge( $raw_items, $items3 );
            }
        }

        if ( empty( $raw_items ) ) {
            return [];
        }

        $stores = [];
        $seen_ids = [];
        $blacklist_types = [ 'bank', 'atm', 'pharmacy', 'dentist', 'hairdresser', 'fuel', 'car_repair', 'clothes', 'shoes', 'electronics', 'optician', 'post_office' ];

        foreach ( $raw_items as $item ) {
            $class = $item['class'] ?? '';
            $type  = $item['type'] ?? '';

            if ( $class === 'shop' ) {
                if ( in_array( $type, $blacklist_types, true ) ) {
                    continue;
                }
            } elseif ( $class === 'amenity' && $type === 'marketplace' ) {
            } else {
                continue;
            }

            $raw_name_lower = mb_strtolower( $item['name'] ?? '', 'UTF-8' );
            if ( mb_strpos( $raw_name_lower, 'банк' ) !== false ||
                 mb_strpos( $raw_name_lower, 'аптек' ) !== false ||
                 mb_strpos( $raw_name_lower, 'банкомат' ) !== false ) {
                continue;
            }

            $osm_id = $item['osm_id'] ?? ( $item['place_id'] ?? null );
            if ( $osm_id && isset( $seen_ids[ $osm_id ] ) ) {
                continue;
            }
            if ( $osm_id ) {
                $seen_ids[ $osm_id ] = true;
            }

            $s_lat = floatval( $item['lat'] );
            $s_lon = floatval( $item['lon'] );

            if ( empty( $s_lat ) || empty( $s_lon ) ) {
                continue;
            }

            $is_dup = false;
            foreach ( $stores as $prev ) {
                $dist_between = self::haversine_distance( $s_lat, $s_lon, $prev['lat'], $prev['lon'] );
                if ( $dist_between < 35 ) {
                    $is_dup = true;
                    break;
                }
            }
            if ( $is_dup ) {
                continue;
            }

            $name = ! empty( $item['name'] ) ? trim( $item['name'] ) : '';
            $addr_shop = $item['address']['shop'] ?? '';
            $road      = $item['address']['road'] ?? '';
            $house_num = $item['address']['house_number'] ?? '';

            if ( empty( $name ) || $name === $road || $name === $house_num || is_numeric( $name ) ) {
                if ( ! empty( $addr_shop ) ) {
                    $name = $addr_shop;
                } elseif ( ! empty( $item['address']['amenity'] ) ) {
                    $name = $item['address']['amenity'];
                } else {
                    $name = ( $type === 'supermarket' ) ? 'Супермаркет' : 'Продуктовый магазин';
                }
            }

            $name = preg_replace( '/^[\s,.-]+|[\s,.-]+$/u', '', $name );

            $address_parts = [];
            if ( ! empty( $road ) ) {
                $address_parts[] = $road;
            }
            if ( ! empty( $house_num ) ) {
                $address_parts[] = 'д. ' . $house_num;
            }
            if ( empty( $address_parts ) ) {
                if ( ! empty( $item['address']['suburb'] ) ) {
                    $address_parts[] = $item['address']['suburb'];
                }
                if ( ! empty( $item['address']['city'] ) ) {
                    $address_parts[] = $item['address']['city'];
                }
            }
            $address = ! empty( $address_parts ) ? implode( ', ', $address_parts ) : 'Адрес на карте';

            $chain_info = self::identify_chain( $name, $type );

            $stores[] = [
                'id'            => 'osm_' . ( $osm_id ?: count( $stores ) + 1 ),
                'name'          => $name,
                'chain'         => $chain_info['chain'],
                'brand_color'   => $chain_info['color'],
                'icon'          => $chain_info['icon'],
                'category'      => ( $type === 'supermarket' ) ? 'Супермаркет' : 'Магазин у дома',
                'lat'           => $s_lat,
                'lon'           => $s_lon,
                'address'       => $address,
                'opening_hours' => $item['address']['opening_hours'] ?? '08:00 - 22:00',
                'features'      => $chain_info['features'],
            ];
        }

        return $stores;
    }

    private static function fetch_osm_json( $url ) {
        $response = wp_remote_get( $url, [
            'timeout'   => 7,
            'headers'   => [
                'User-Agent'      => 'SmartFridgeChef/1.0 (WordPress)',
                'Accept-Language' => 'ru,en;q=0.8'
            ],
            'sslverify' => false,
        ]);

        if ( is_wp_error( $response ) ) {
            return [];
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) {
            return [];
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        return is_array( $data ) ? $data : [];
    }

    public static function identify_chain( $name, $type = 'supermarket' ) {
        $n = mb_strtolower( $name, 'UTF-8' );

        if ( mb_strpos( $n, 'евроопт' ) !== false || mb_strpos( $n, 'evroopt' ) !== false || mb_strpos( $n, 'грошык' ) !== false || mb_strpos( $n, 'хит' ) !== false ) {
            return [
                'chain'    => 'Евроопт',
                'color'    => '#10b981',
                'icon'     => 'local_mall',
                'features' => [ 'Выгодные цены', 'Широкий ассортимент', 'Акции и скидки' ]
            ];
        }
        if ( mb_strpos( $n, 'санта' ) !== false || mb_strpos( $n, 'santa' ) !== false ) {
            return [
                'chain'    => 'Санта',
                'color'    => '#0284c7',
                'icon'     => 'shopping_cart',
                'features' => [ 'Свежая рыба', 'Готовая кулинария', 'Молочные продукты' ]
            ];
        }
        if ( mb_strpos( $n, 'корона' ) !== false || mb_strpos( $n, 'korona' ) !== false ) {
            return [
                'chain'    => 'Корона',
                'color'    => '#ca8a04',
                'icon'     => 'storefront',
                'features' => [ 'Премиум выбор', 'Собственная пекарня', 'Свежее мясо и деликатесы' ]
            ];
        }
        if ( mb_strpos( $n, 'гиппо' ) !== false || mb_strpos( $n, 'gippo' ) !== false ) {
            return [
                'chain'    => 'Гиппо',
                'color'    => '#ea580c',
                'icon'     => 'local_mall',
                'features' => [ 'Гипермаркет', 'Свежие фрукты и овощи', 'Большой выбор' ]
            ];
        }
        if ( mb_strpos( $n, 'соседи' ) !== false || mb_strpos( $n, 'sosedi' ) !== false ) {
            return [
                'chain'    => 'Соседи',
                'color'    => '#f59e0b',
                'icon'     => 'store',
                'features' => [ 'Магазин у дома', 'Свежая выпечка', 'Удобное расположение' ]
            ];
        }
        if ( mb_strpos( $n, 'green' ) !== false || mb_strpos( $n, 'грин' ) !== false ) {
            return [
                'chain'    => 'Green',
                'color'    => '#059669',
                'icon'     => 'eco',
                'features' => [ 'Эко-продукты', 'Здоровое питание', 'Фермерская продукция' ]
            ];
        }

        if ( mb_strpos( $n, 'magnum' ) !== false || mb_strpos( $n, 'магнум' ) !== false ) {
            return [
                'chain'    => 'Magnum',
                'color'    => '#dc2626',
                'icon'     => 'local_mall',
                'features' => [ 'Крупнейшая сеть', 'Широкий выбор', 'Выгодные цены' ]
            ];
        }
        if ( mb_strpos( $n, 'small' ) !== false || mb_strpos( $n, 'смолл' ) !== false ) {
            return [
                'chain'    => 'Small',
                'color'    => '#2563eb',
                'icon'     => 'shopping_cart',
                'features' => [ 'Супермаркет у дома', 'Свежие продукты', 'Акции' ]
            ];
        }
        if ( mb_strpos( $n, 'galmart' ) !== false || mb_strpos( $n, 'галмарт' ) !== false ) {
            return [
                'chain'    => 'Galmart',
                'color'    => '#7c3aed',
                'icon'     => 'storefront',
                'features' => [ 'Премиум продукты', 'Органическая еда', 'Кулинария' ]
            ];
        }

        if ( mb_strpos( $n, 'fresco' ) !== false || mb_strpos( $n, 'ფრესკო' ) !== false ) {
            return [
                'chain'    => 'Fresco',
                'color'    => '#16a34a',
                'icon'     => 'storefront',
                'features' => [ 'Круглосуточно', 'Свежая выпечка', 'Кулинарный отдел' ]
            ];
        }
        if ( mb_strpos( $n, 'nikora' ) !== false || mb_strpos( $n, 'ნიკორა' ) !== false ) {
            return [
                'chain'    => 'Nikora',
                'color'    => '#0284c7',
                'icon'     => 'shopping_basket',
                'features' => [ 'Мясная гастрономия', 'Молочная продукция', 'У дома' ]
            ];
        }

        if ( mb_strpos( $n, 'вкусвилл' ) !== false || mb_strpos( $n, 'vkusvill' ) !== false ) {
            return [
                'chain'    => 'ВкусВилл',
                'color'    => '#10b981',
                'icon'     => 'eco',
                'features' => [ 'Свежая молочка', 'Эко-продукты', 'Чистый состав' ]
            ];
        }
        if ( mb_strpos( $n, 'пятёрочк' ) !== false || mb_strpos( $n, 'пятерочк' ) !== false || mb_strpos( $n, '5ka' ) !== false ) {
            return [
                'chain'    => 'Пятёрочка',
                'color'    => '#ef4444',
                'icon'     => 'shopping_basket',
                'features' => [ 'Выгодные цены', 'Скидки и акции', 'Базовая бакалея' ]
            ];
        }
        if ( mb_strpos( $n, 'магнит' ) !== false ) {
            return [
                'chain'    => 'Магнит',
                'color'    => '#dc2626',
                'icon'     => 'shopping_cart',
                'features' => [ 'Магазин у дома', 'Экономный выбор', 'Свежий хлеб' ]
            ];
        }
        if ( mb_strpos( $n, 'перекрёсток' ) !== false || mb_strpos( $n, 'перекресток' ) !== false ) {
            return [
                'chain'    => 'Перекрёсток',
                'color'    => '#059669',
                'icon'     => 'storefront',
                'features' => [ 'Свежее мясо и рыба', 'Премиум сыры', 'Собственная кулинария' ]
            ];
        }
        if ( mb_strpos( $n, 'лента' ) !== false ) {
            return [
                'chain'    => 'Лента',
                'color'    => '#2563eb',
                'icon'     => 'local_mall',
                'features' => [ 'Большой ассортимент', 'Закупка на неделю', 'Выгодные упаковки' ]
            ];
        }
        if ( mb_strpos( $n, 'дикси' ) !== false ) {
            return [
                'chain'    => 'Дикси',
                'color'    => '#f97316',
                'icon'     => 'store',
                'features' => [ 'У дома', 'Низкие цены', 'Свежие фрукты' ]
            ];
        }

        if ( mb_strpos( $n, 'carrefour' ) !== false ) {
            return [
                'chain'    => 'Carrefour',
                'color'    => '#0284c7',
                'icon'     => 'local_mall',
                'features' => [ 'Широкий ассортимент', 'Свежие продукты', 'Выгодные предложения' ]
            ];
        }
        if ( mb_strpos( $n, 'spar' ) !== false ) {
            return [
                'chain'    => 'Spar',
                'color'    => '#16a34a',
                'icon'     => 'storefront',
                'features' => [ 'Свежая выпечка', 'Качественные продукты', 'Удобный супермаркет' ]
            ];
        }
        if ( mb_strpos( $n, 'biedronka' ) !== false ) {
            return [
                'chain'    => 'Biedronka',
                'color'    => '#eab308',
                'icon'     => 'shopping_cart',
                'features' => [ 'Популярная сеть', 'Низкие цены', 'Свежие поставки' ]
            ];
        }
        if ( mb_strpos( $n, 'lidl' ) !== false || mb_strpos( $n, 'aldi' ) !== false ) {
            return [
                'chain'    => 'Дискаунтер',
                'color'    => '#0284c7',
                'icon'     => 'shopping_basket',
                'features' => [ 'Выгодные цены', 'Качественные базовые товары' ]
            ];
        }

        $is_supermarket = ( $type === 'supermarket' || mb_strpos( $n, 'маркет' ) !== false || mb_strpos( $n, 'универсам' ) !== false );
        return [
            'chain'    => $name,
            'color'    => '#475569',
            'icon'     => $is_supermarket ? 'local_mall' : 'storefront',
            'features' => $is_supermarket
                ? [ 'Широкий ассортимент', 'Все категории продуктов', 'Свежие овощи и бакалея' ]
                : [ 'Магазин у дома', 'Товары первой необходимости', 'Быстрые покупки' ]
        ];
    }

    public static function calculate_recommendation( $stores, $shopping_list ) {
        if ( empty( $stores ) ) {
            return null;
        }

        $cat_counts = [];
        $total_items = count( $shopping_list );

        foreach ( $shopping_list as $item ) {
            $cat = $item['category'] ?? 'Другое';
            $cat_counts[ $cat ] = ( $cat_counts[ $cat ] ?? 0 ) + 1;
        }

        $fresh_produce_count = ( $cat_counts['Овощи и зелень'] ?? 0 ) + ( $cat_counts['Фрукты и ягоды'] ?? 0 );
        $dairy_count         = $cat_counts['Молочные продукты'] ?? 0;
        $meat_count          = ( $cat_counts['Мясо и птица'] ?? 0 ) + ( $cat_counts['Рыба и морепродукты'] ?? 0 );
        $staples_count       = ( $cat_counts['Крупы и бакалея'] ?? 0 ) + ( $cat_counts['Яйца'] ?? 0 ) + ( $cat_counts['Другое'] ?? 0 );

        $best_store = null;
        $best_score = -99999;

        foreach ( $stores as $store ) {
            $score = 100;
            $score -= ( $store['distance_m'] / 25 );

            if ( $store['category'] === 'Супермаркет' && $total_items >= 4 ) {
                $score += 20;
            }

            if ( $score > $best_score ) {
                $best_score = $score;
                $best_store = $store;
            }
        }

        if ( ! $best_store ) {
            $best_store = $stores[0];
        }

        $reason = "Оптимальный выбор для вашего списка покупок: «{$best_store['name']}». Расположен в {$best_store['distance_m']} м от вас (~{$best_store['walking_time_mins']} мин пешком).";

        if ( ( $fresh_produce_count + $dairy_count + $meat_count ) > $staples_count ) {
            $reason .= " В ассортименте этого супермаркета легко найти все свежие ингредиенты (молочные продукты, овощи, мясо) для блюд недели.";
        } else {
            $reason .= " Удобная локация, чтобы быстро и экономно докупить недостающую бакалею и базовые ингредиенты.";
        }

        $tips = [
            'Время в пути пешком: ~' . $best_store['walking_time_mins'] . ' мин (' . $best_store['distance_m'] . ' м)',
        ];

        if ( $fresh_produce_count > 0 || $dairy_count > 0 ) {
            $tips[] = 'Обратите внимание на сроки годности молочных продуктов и свежесть зелени';
        } elseif ( $meat_count > 0 ) {
            $tips[] = 'Проверьте охлажденный мясной/рыбный отдел для основных блюд недели';
        } else {
            $tips[] = 'Все базовые позиции из вашего списка есть в наличии';
        }

        $tips[] = 'Отмечайте купленные товары галочками в списке покупок приложения';

        $yandex_route_url = sprintf(
            'https://yandex.ru/maps/?rtext=~%f,%f&rtt=pd',
            $best_store['lat'],
            $best_store['lon']
        );
        $google_route_url = sprintf(
            'https://www.google.com/maps/dir/?api=1&destination=%f,%f&travelmode=walking',
            $best_store['lat'],
            $best_store['lon']
        );

        return [
            'store'           => $best_store,
            'reason'          => $reason,
            'tips'            => $tips,
            'navigation_urls' => [
                'yandex' => $yandex_route_url,
                'google' => $google_route_url,
            ]
        ];
    }
}

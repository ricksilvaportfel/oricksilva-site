<?php
/**
 * O Rick Silva — Child Theme functions
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* =========================================================
   1. ENQUEUE PARENT + CHILD STYLES + GOOGLE FONTS
   ========================================================= */
add_action( 'wp_enqueue_scripts', function () {
    // Google Fonts
    wp_enqueue_style(
        'oricksilva-fonts',
        'https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400..700;1,9..144,400..700&family=Inter:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap',
        [],
        null
    );
    // Parent (Hello Elementor)
    wp_enqueue_style( 'hello-elementor', get_template_directory_uri() . '/style.css' );
    // Child — usa filemtime pra cache-bust automático
    $child_css_path = get_stylesheet_directory() . '/style.css';
    wp_enqueue_style(
        'oricksilva-child',
        get_stylesheet_directory_uri() . '/style.css',
        [ 'hello-elementor', 'oricksilva-fonts' ],
        file_exists( $child_css_path ) ? filemtime( $child_css_path ) : wp_get_theme()->get( 'Version' )
    );
}, 20 );

/* =========================================================
   2. THEME SUPPORTS + MENUS
   ========================================================= */
add_action( 'after_setup_theme', function () {
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'title-tag' );
    add_theme_support( 'html5', [ 'search-form', 'gallery', 'caption' ] );

    register_nav_menus( [
        'primary'  => 'Menu principal',
        'subnav'   => 'Sub-navegação (categorias)',
        'footer_1' => 'Rodapé — O Rick Silva',
        'footer_2' => 'Rodapé — Conteúdo',
        'footer_3' => 'Rodapé — Aprenda',
        'footer_4' => 'Rodapé — Legal',
    ] );
} );

/* =========================================================
   3. ROLE CUSTOMIZADA "COLUNISTA"
   ========================================================= */
add_action( 'after_switch_theme', function () {
    if ( ! get_role( 'columnist' ) ) {
        add_role( 'columnist', 'Colunista', [
            'read'                   => true,
            'edit_posts'             => true,
            'delete_posts'           => true,
            'publish_posts'          => true,
            'upload_files'           => true,
            'edit_published_posts'   => true,
            'delete_published_posts' => true,
        ] );
    }
} );

/* =========================================================
   4. CRIAR CATEGORIAS + TAGS INICIAIS (uma vez só)
   ========================================================= */
add_action( 'after_switch_theme', function () {
    // Categorias principais (menu)
    $categorias = [
        'artigos'     => 'Artigos',
        'colunistas'  => 'Colunistas',
        'materiais'   => 'Materiais',
        'ferramentas' => 'Ferramentas',
        'videos'      => 'Vídeos',
        'podcast'     => 'Podcast',
        'eventos'     => 'Eventos',
    ];
    foreach ( $categorias as $slug => $nome ) {
        if ( ! term_exists( $slug, 'category' ) ) {
            wp_insert_term( $nome, 'category', [ 'slug' => $slug ] );
        }
    }

    // Tags que controlam onde cada post aparece na home
    $tags = [
        'destaque'            => 'Destaque (hero principal)',
        'destaque-secundario' => 'Destaque secundário (subchamadas do hero)',
        'ao-vivo'             => 'Ao vivo (coluna do meio do hero)',
        'lateral-hero'        => 'Lateral hero (4 cards da direita)',
        'em-alta'             => 'Em alta (grid de 4 cards)',
        'conteudo-marca'      => 'Conteúdo de marca (patrocinado)',
    ];
    foreach ( $tags as $slug => $nome ) {
        if ( ! term_exists( $slug, 'post_tag' ) ) {
            wp_insert_term( $nome, 'post_tag', [ 'slug' => $slug ] );
        }
    }
} );

/* =========================================================
   5. HELPERS — QUERIES POR TAG/CATEGORIA
   ========================================================= */
function orick_get_posts_by_tag( $tag_slug, $limit = 4 ) {
    return new WP_Query( [
        'post_type'      => 'post',
        'posts_per_page' => $limit,
        'tag'            => $tag_slug,
        'ignore_sticky_posts' => true,
    ] );
}
function orick_get_posts_by_cat( $cat_slug, $limit = 4 ) {
    return new WP_Query( [
        'post_type'      => 'post',
        'posts_per_page' => $limit,
        'category_name'  => $cat_slug,
        'ignore_sticky_posts' => true,
    ] );
}

/* =========================================================
   6. HELPERS — APRESENTAÇÃO
   ========================================================= */
function orick_reading_time( $post_id = null ) {
    $post    = $post_id ? get_post( $post_id ) : get_post();
    $words   = str_word_count( wp_strip_all_tags( $post->post_content ) );
    $minutes = max( 1, (int) ceil( $words / 200 ) );
    return $minutes . ' min de leitura';
}
function orick_first_category_name( $post_id = null ) {
    $cats = get_the_category( $post_id );
    return $cats ? $cats[0]->name : 'Artigo';
}
function orick_columnist_bio( $user_id ) {
    $bio = get_user_meta( $user_id, 'description', true );
    return $bio ?: '';
}

/* =========================================================
   7. BODY CLASS
   ========================================================= */
add_filter( 'body_class', function ( $classes ) {
    $classes[] = 'oricksilva';
    return $classes;
} );

/* =========================================================
   8. INICIAIS PARA AVATAR PLACEHOLDER
   ========================================================= */
function orick_initials( $name ) {
    $parts = explode( ' ', trim( $name ) );
    $ini   = '';
    $ini  .= isset( $parts[0][0] ) ? mb_strtoupper( $parts[0][0] ) : '';
    if ( count( $parts ) > 1 ) {
        $last = end( $parts );
        $ini .= mb_strtoupper( $last[0] );
    }
    return $ini;
}

/* =========================================================
   9. COTAÇÕES — brapi.dev (cache 5 min, grátis, sem key)
   =========================================================
   Retorna array:
   [
     ['label'=>'IBOV','sub'=>'Ibovespa','price'=>128432.21,'chg'=>1.24,'currency'=>'pts'],
     ...
   ]
   ---------------------------------------------------------
   Obs.: a brapi grátis limita 100 requests/dia.
   Cache de 300s (5min) mantém a gente MUITO abaixo disso.
   ========================================================= */
function orick_fetch_quotes() {
    $cache_key = 'orick_quotes_v4';
    $cached    = get_transient( $cache_key );
    if ( $cached !== false && ! ( isset( $_GET['nocache'] ) && current_user_can( 'manage_options' ) ) ) {
        return $cached;
    }

    $out = [
        'ibov'      => null,
        'stocks'    => [],
        'currencies'=> [],
    ];

    // ---------- IBOVESPA + histórico ----------
    $ibov_url = 'https://brapi.dev/api/quote/^BVSP?range=1mo&interval=1d';
    $r = wp_remote_get( $ibov_url, [ 'timeout' => 15, 'headers' => [ 'Accept' => 'application/json' ] ] );
    if ( ! is_wp_error( $r ) && wp_remote_retrieve_response_code( $r ) === 200 ) {
        $d = json_decode( wp_remote_retrieve_body( $r ), true );
        if ( ! empty( $d['results'][0] ) ) {
            $row = $d['results'][0];
            $hist = [];
            if ( ! empty( $row['historicalDataPrice'] ) ) {
                foreach ( $row['historicalDataPrice'] as $h ) {
                    if ( isset( $h['close'] ) ) $hist[] = (float) $h['close'];
                }
            }
            $out['ibov'] = [
                'price'   => isset( $row['regularMarketPrice'] ) ? (float) $row['regularMarketPrice'] : null,
                'chg'     => isset( $row['regularMarketChangePercent'] ) ? (float) $row['regularMarketChangePercent'] : null,
                'open'    => isset( $row['regularMarketOpen'] ) ? (float) $row['regularMarketOpen'] : null,
                'low'     => isset( $row['regularMarketDayLow'] ) ? (float) $row['regularMarketDayLow'] : null,
                'high'    => isset( $row['regularMarketDayHigh'] ) ? (float) $row['regularMarketDayHigh'] : null,
                'history' => $hist,
            ];
        }
    }

    // ---------- ÍNDICES / ETFs — BATCH em 1 request ----------
    $stock_list = [
        'SMAL11' => 'Small Caps',
        'IVVB11' => 'S&P 500',
        'BOVA11' => 'Bovespa ETF',
        'HASH11' => 'Cripto Index',
        'PETR4'  => 'Petrobras',
    ];
    $stock_syms_qs = implode( ',', array_keys( $stock_list ) );
    $stock_url = 'https://brapi.dev/api/quote/' . rawurlencode( $stock_syms_qs );
    $sr = wp_remote_get( $stock_url, [ 'timeout' => 15, 'headers' => [ 'Accept' => 'application/json' ] ] );
    if ( ! is_wp_error( $sr ) && wp_remote_retrieve_response_code( $sr ) === 200 ) {
        $sd = json_decode( wp_remote_retrieve_body( $sr ), true );
        if ( ! empty( $sd['results'] ) ) {
            // Mantém a ordem definida em $stock_list
            $by_sym = [];
            foreach ( $sd['results'] as $rr ) {
                $sym = $rr['symbol'] ?? '';
                if ( isset( $stock_list[ $sym ] ) ) $by_sym[ $sym ] = $rr;
            }
            foreach ( $stock_list as $sym => $name ) {
                if ( ! isset( $by_sym[ $sym ] ) ) continue;
                $rw = $by_sym[ $sym ];
                $out['stocks'][] = [
                    'symbol' => $sym,
                    'name'   => $name,
                    'price'  => isset( $rw['regularMarketPrice'] ) ? (float) $rw['regularMarketPrice'] : null,
                    'chg'    => isset( $rw['regularMarketChangePercent'] ) ? (float) $rw['regularMarketChangePercent'] : null,
                ];
            }
        }
    }

    // ---------- MOEDAS ----------
    $curr_list = [
        'USD-BRL' => 'Dólar',
        'EUR-BRL' => 'Euro',
        'GBP-BRL' => 'Libra',
        'JPY-BRL' => 'Iene',
    ];
    $curr_qs  = implode( ',', array_keys( $curr_list ) );
    $curr_url = 'https://brapi.dev/api/v2/currency?currency=' . rawurlencode( $curr_qs );
    $cr = wp_remote_get( $curr_url, [ 'timeout' => 15, 'headers' => [ 'Accept' => 'application/json' ] ] );
    if ( ! is_wp_error( $cr ) && wp_remote_retrieve_response_code( $cr ) === 200 ) {
        $cd = json_decode( wp_remote_retrieve_body( $cr ), true );
        if ( ! empty( $cd['currency'] ) ) {
            foreach ( $cd['currency'] as $row ) {
                $pair = ( $row['fromCurrency'] ?? '' ) . '-' . ( $row['toCurrency'] ?? '' );
                if ( ! isset( $curr_list[ $pair ] ) ) continue;
                $out['currencies'][] = [
                    'label' => $curr_list[ $pair ],
                    'code'  => $row['fromCurrency'] ?? '',
                    'price' => isset( $row['bidPrice'] ) ? (float) $row['bidPrice'] : null,
                    'chg'   => isset( $row['bidVariation'] ) ? (float) $row['bidVariation'] : null,
                ];
            }
        }
    }

    $empty = ( $out['ibov'] === null && empty( $out['stocks'] ) && empty( $out['currencies'] ) );
    set_transient( $cache_key, $out, $empty ? MINUTE_IN_SECONDS : 5 * MINUTE_IN_SECONDS );
    return $out;
}

/**
 * Sparkline SVG a partir de array de preços
 */
function orick_sparkline_svg( $values, $w = 280, $h = 70, $stroke = '#A75232' ) {
    if ( empty( $values ) || count( $values ) < 2 ) return '';
    $min = min( $values );
    $max = max( $values );
    $range = max( 0.0001, $max - $min );
    $n = count( $values );
    $points = [];
    foreach ( $values as $i => $v ) {
        $x = ( $i / ( $n - 1 ) ) * $w;
        $y = $h - ( ( $v - $min ) / $range ) * ( $h - 4 ) - 2;
        $points[] = round( $x, 2 ) . ',' . round( $y, 2 );
    }
    $path = 'M ' . str_replace( ',', ' ', $points[0] );
    for ( $i = 1; $i < count( $points ); $i++ ) {
        $path .= ' L ' . str_replace( ',', ' ', $points[ $i ] );
    }
    // área sob a curva
    $area = $path . ' L ' . $w . ' ' . $h . ' L 0 ' . $h . ' Z';
    $id = 'sp' . wp_generate_uuid4();
    return sprintf(
        '<svg class="os-spark-svg" viewBox="0 0 %1$d %2$d" preserveAspectRatio="none" role="img" aria-hidden="true">
           <defs>
             <linearGradient id="%5$s" x1="0" y1="0" x2="0" y2="1">
               <stop offset="0%%" stop-color="%4$s" stop-opacity="0.25"/>
               <stop offset="100%%" stop-color="%4$s" stop-opacity="0"/>
             </linearGradient>
           </defs>
           <path d="%6$s" fill="url(#%5$s)" stroke="none"/>
           <path d="%3$s" fill="none" stroke="%4$s" stroke-width="1.5" stroke-linejoin="round" stroke-linecap="round"/>
         </svg>',
        $w, $h, esc_attr( $path ), esc_attr( $stroke ), esc_attr( $id ), esc_attr( $area )
    );
}

/* Formata preço em pt-BR */
function orick_fmt_price( $value ) {
    if ( $value === null ) return '—';
    // BRL/pts: separador decimal vírgula, milhar ponto
    return number_format( $value, 2, ',', '.' );
}
function orick_fmt_chg( $pct ) {
    if ( $pct === null ) return '—';
    $sign = $pct >= 0 ? '+' : '';
    return $sign . number_format( $pct, 2, ',', '.' ) . '%';
}

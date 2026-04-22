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
    $cache_key = 'orick_quotes_v2';
    $cached    = get_transient( $cache_key );
    if ( $cached !== false && ! ( isset( $_GET['nocache'] ) && current_user_can( 'manage_options' ) ) ) {
        return $cached;
    }

    // Tickers B3 que a brapi entrega de graça
    $tickers = [
        '^BVSP' => [ 'label' => 'IBOV',  'sub' => 'Ibovespa',       'currency' => 'pts' ],
        'PETR4' => [ 'label' => 'PETR4', 'sub' => 'Petrobras',      'currency' => 'R$'  ],
        'VALE3' => [ 'label' => 'VALE3', 'sub' => 'Vale',           'currency' => 'R$'  ],
        'ITUB4' => [ 'label' => 'ITUB4', 'sub' => 'Itaú',           'currency' => 'R$'  ],
        'BBAS3' => [ 'label' => 'BBAS3', 'sub' => 'Banco do Brasil','currency' => 'R$'  ],
    ];

    $out = [];

    // Busca cada ticker isoladamente pra não derrubar tudo se um falhar
    foreach ( $tickers as $symbol => $meta ) {
        $url  = 'https://brapi.dev/api/quote/' . rawurlencode( $symbol );
        $resp = wp_remote_get( $url, [
            'timeout' => 6,
            'headers' => [ 'Accept' => 'application/json' ],
        ] );

        if ( is_wp_error( $resp ) ) continue;
        if ( wp_remote_retrieve_response_code( $resp ) !== 200 ) continue;

        $data = json_decode( wp_remote_retrieve_body( $resp ), true );
        if ( empty( $data['results'][0] ) ) continue;

        $row   = $data['results'][0];
        $out[] = [
            'label'    => $meta['label'],
            'sub'      => $meta['sub'],
            'price'    => isset( $row['regularMarketPrice'] ) ? (float) $row['regularMarketPrice'] : null,
            'chg'      => isset( $row['regularMarketChangePercent'] ) ? (float) $row['regularMarketChangePercent'] : null,
            'currency' => $meta['currency'],
        ];
    }

    // Moedas (endpoint separado)
    $currencies = [
        'USD-BRL' => [ 'label' => 'Dólar', 'sub' => 'USD → BRL', 'currency' => 'R$' ],
        'EUR-BRL' => [ 'label' => 'Euro',  'sub' => 'EUR → BRL', 'currency' => 'R$' ],
    ];
    $curr_qs  = implode( ',', array_keys( $currencies ) );
    $curr_url = 'https://brapi.dev/api/v2/currency?currency=' . rawurlencode( $curr_qs );
    $cresp    = wp_remote_get( $curr_url, [
        'timeout' => 6,
        'headers' => [ 'Accept' => 'application/json' ],
    ] );
    if ( ! is_wp_error( $cresp ) && wp_remote_retrieve_response_code( $cresp ) === 200 ) {
        $cdata = json_decode( wp_remote_retrieve_body( $cresp ), true );
        if ( ! empty( $cdata['currency'] ) ) {
            foreach ( $cdata['currency'] as $row ) {
                $pair = ( $row['fromCurrency'] ?? '' ) . '-' . ( $row['toCurrency'] ?? '' );
                if ( ! isset( $currencies[ $pair ] ) ) continue;
                $out[] = [
                    'label'    => $currencies[ $pair ]['label'],
                    'sub'      => $currencies[ $pair ]['sub'],
                    'price'    => isset( $row['bidPrice'] ) ? (float) $row['bidPrice'] : null,
                    'chg'      => isset( $row['bidVariation'] ) ? (float) $row['bidVariation'] : null,
                    'currency' => $currencies[ $pair ]['currency'],
                ];
            }
        }
    }

    // Cacheia (mesmo vazio, por 1min só, pra não martelar a API se falhar)
    set_transient( $cache_key, $out, empty( $out ) ? MINUTE_IN_SECONDS : 5 * MINUTE_IN_SECONDS );
    return $out;
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

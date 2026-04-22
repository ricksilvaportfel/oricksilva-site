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
    // Child
    wp_enqueue_style(
        'oricksilva-child',
        get_stylesheet_directory_uri() . '/style.css',
        [ 'hello-elementor', 'oricksilva-fonts' ],
        wp_get_theme()->get( 'Version' )
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

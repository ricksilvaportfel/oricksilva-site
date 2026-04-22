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
   8.5. VÍDEO — helpers unificados (lê dos metas do plugin CPT 'video')
   =========================================================
   O CPT "video" (plugin Orick Ferramentas) já tem metabox próprio com:
   - _orick_youtube_id   (ID extraído)
   - _orick_video_url    (URL completa)
   - _orick_duracao      (mm:ss)
   - post_thumbnail      (baixado automaticamente do YouTube pelo sync RSS)

   Aqui expomos helpers que funcionam pra qualquer post/CPT.
*/

/**
 * Extrai o ID do YouTube de qualquer formato comum.
 */
function orick_youtube_id( $url ) {
    if ( ! $url ) return '';
    if ( preg_match( '~(?:youtube\.com/(?:watch\?v=|embed/|shorts/|v/)|youtu\.be/)([A-Za-z0-9_-]{11})~i', $url, $m ) ) {
        return $m[1];
    }
    return '';
}

/**
 * Retorna dados do vídeo pro post atual (ou $post_id).
 * Lê metas do plugin CPT 'video': _orick_youtube_id, _orick_video_url, _orick_duracao
 * Fallback pra post_thumbnail se não houver ID.
 */
function orick_video_data( $post_id = null ) {
    $post_id = $post_id ?: get_the_ID();

    $id  = get_post_meta( $post_id, '_orick_youtube_id', true );
    $url = get_post_meta( $post_id, '_orick_video_url', true );
    $dur = get_post_meta( $post_id, '_orick_duracao', true );

    // Se não achou o id salvo, tenta extrair da url
    if ( ! $id && $url ) {
        $id = orick_youtube_id( $url );
    }

    $thumb = '';
    if ( has_post_thumbnail( $post_id ) ) {
        $thumb = get_the_post_thumbnail_url( $post_id, 'large' );
    } elseif ( $id ) {
        $thumb = "https://i.ytimg.com/vi/{$id}/maxresdefault.jpg";
    }

    return [
        'id'        => $id,
        'url'       => $url,
        'watch_url' => $id ? "https://www.youtube.com/watch?v={$id}" : get_permalink( $post_id ),
        'embed_url' => $id ? "https://www.youtube.com/embed/{$id}" : '',
        'thumb'     => $thumb,
        'duration'  => $dur,
        'kicker'    => '',
    ];
}

/* =========================================================
   9. COTAÇÕES — brapi.dev (cache 5 min, token obrigatório)
   ========================================================= */

// Token da brapi (gerar em https://brapi.dev/dashboard)
if ( ! defined( 'ORICK_BRAPI_TOKEN' ) ) {
    define( 'ORICK_BRAPI_TOKEN', 'geeCqpxZHjsjJBDH5ArfjV' );
}

function orick_brapi_url( $endpoint, $extra = [] ) {
    $qs = array_merge( $extra, [ 'token' => ORICK_BRAPI_TOKEN ] );
    $sep = ( strpos( $endpoint, '?' ) === false ) ? '?' : '&';
    return 'https://brapi.dev' . $endpoint . $sep . http_build_query( $qs );
}

function orick_fetch_quotes() {
    $cache_key = 'orick_quotes_v5';
    $cached    = get_transient( $cache_key );
    if ( $cached !== false && ! ( isset( $_GET['nocache'] ) && current_user_can( 'manage_options' ) ) ) {
        return $cached;
    }

    $out = [
        'ibov'      => null,
        'stocks'    => [],
        'currencies'=> [],
    ];

    $args = [
        'timeout' => 15,
        'headers' => [
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . ORICK_BRAPI_TOKEN,
        ],
    ];

    // ---------- IBOVESPA + histórico ----------
    $ibov_url = orick_brapi_url( '/api/quote/^BVSP', [ 'range' => '1mo', 'interval' => '1d' ] );
    $r = wp_remote_get( $ibov_url, $args );
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

    // ---------- ÍNDICES / ETFs — 1 request por ativo (plano free brapi) ----------
    $stock_list = [
        'SMAL11' => 'Small Caps',
        'GPUS11' => 'GPU / IA',
        'IVVB11' => 'S&P 500',
        'BOVA11' => 'Bovespa ETF',
    ];
    foreach ( $stock_list as $sym => $name ) {
        $u  = orick_brapi_url( '/api/quote/' . rawurlencode( $sym ) );
        $rr = wp_remote_get( $u, $args );
        if ( is_wp_error( $rr ) || wp_remote_retrieve_response_code( $rr ) !== 200 ) continue;
        $dd = json_decode( wp_remote_retrieve_body( $rr ), true );
        if ( empty( $dd['results'][0] ) ) continue;
        $rw = $dd['results'][0];
        $out['stocks'][] = [
            'symbol' => $sym,
            'name'   => $name,
            'price'  => isset( $rw['regularMarketPrice'] ) ? (float) $rw['regularMarketPrice'] : null,
            'chg'    => isset( $rw['regularMarketChangePercent'] ) ? (float) $rw['regularMarketChangePercent'] : null,
        ];
    }

    // ---------- MOEDAS (brapi v1 moeda-a-moeda — plano free aceita 1 por vez) ----------
    $curr_list = [
        'USD-BRL' => 'Dólar',
        'EUR-BRL' => 'Euro',
        'GBP-BRL' => 'Libra',
        'JPY-BRL' => 'Iene',
    ];
    foreach ( $curr_list as $pair => $label ) {
        $u  = orick_brapi_url( '/api/v2/currency', [ 'currency' => $pair ] );
        $cr = wp_remote_get( $u, $args );
        if ( is_wp_error( $cr ) || wp_remote_retrieve_response_code( $cr ) !== 200 ) continue;
        $cd = json_decode( wp_remote_retrieve_body( $cr ), true );
        if ( empty( $cd['currency'][0] ) ) continue;
        $row = $cd['currency'][0];
        $out['currencies'][] = [
            'label' => $label,
            'code'  => $row['fromCurrency'] ?? explode( '-', $pair )[0],
            'price' => isset( $row['bidPrice'] ) ? (float) $row['bidPrice'] : null,
            'chg'   => isset( $row['bidVariation'] ) ? (float) $row['bidVariation'] : null,
        ];
    }

    $empty = ( $out['ibov'] === null && empty( $out['stocks'] ) && empty( $out['currencies'] ) );
    set_transient( $cache_key, $out, $empty ? MINUTE_IN_SECONDS : 5 * MINUTE_IN_SECONDS );
    return $out;
}

/* =========================================================
   5. CUSTOMIZER — Edição de textos no painel WP
   ========================================================= */
add_action( 'customize_register', function( $wp_customize ) {

    /* SEÇÃO: Logo do site */
    $wp_customize->add_section( 'os_logo', [
        'title'       => __( 'Logo do cabeçalho', 'oricksilva' ),
        'priority'    => 25,
        'description' => 'Suba uma imagem (PNG/SVG) para substituir o logo padrão "RS O Rick Silva". Deixe vazio para voltar ao logo padrão.',
    ] );

    $wp_customize->add_setting( 'os_logo_image', [
        'default'           => '',
        'sanitize_callback' => 'esc_url_raw',
        'transport'         => 'refresh',
    ] );
    $wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, 'os_logo_image', [
        'label'    => 'Imagem do logo',
        'section'  => 'os_logo',
        'settings' => 'os_logo_image',
    ] ) );

    $wp_customize->add_setting( 'os_logo_height', [
        'default'           => 32,
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ] );
    $wp_customize->add_control( 'os_logo_height', [
        'label'       => 'Altura do logo (px)',
        'description' => 'Entre 20 e 60 pixels',
        'section'     => 'os_logo',
        'type'        => 'number',
        'input_attrs' => [ 'min' => 20, 'max' => 60, 'step' => 1 ],
    ] );

    /* SEÇÃO: Newsletter na lateral dos artigos */
    $wp_customize->add_section( 'os_news_sidebar', [
        'title'       => __( 'Newsletter (sidebar dos artigos)', 'oricksilva' ),
        'priority'    => 30,
        'description' => 'Edite os textos do bloco de newsletter que aparece na lateral direita dos artigos.',
    ] );

    $fields = [
        'os_news_kicker'   => [ 'label' => 'Rótulo (topo)',     'default' => 'NEWSLETTER', 'type' => 'text' ],
        'os_news_title'    => [ 'label' => 'Título',             'default' => 'A pauta financeira na sua caixa, 3×/semana.', 'type' => 'textarea' ],
        'os_news_sub'      => [ 'label' => 'Descrição curta',    'default' => 'Análise sem ruído e 0 promessas.', 'type' => 'textarea' ],
        'os_news_btn_text' => [ 'label' => 'Texto do botão',     'default' => 'Assinar grátis', 'type' => 'text' ],
        'os_news_btn_url'  => [ 'label' => 'Link do botão',      'default' => '#newsletter', 'type' => 'url' ],
    ];
    foreach ( $fields as $id => $f ) {
        $wp_customize->add_setting( $id, [
            'default'           => $f['default'],
            'sanitize_callback' => $f['type'] === 'url' ? 'esc_url_raw' : 'sanitize_text_field',
            'transport'         => 'refresh',
        ] );
        $wp_customize->add_control( $id, [
            'label'    => $f['label'],
            'section'  => 'os_news_sidebar',
            'type'     => $f['type'],
            'settings' => $id,
        ] );
    }

} );

/**
 * DEBUG: acesse oricksilva.com.br/?brapi_debug=1 logado como admin pra ver status de cada endpoint
 */
add_action( 'wp', function() {
    if ( empty( $_GET['brapi_debug'] ) || ! current_user_can( 'manage_options' ) ) return;
    delete_transient( 'orick_quotes_v5' );
    header( 'Content-Type: text/plain; charset=utf-8' );
    $args = [
        'timeout' => 15,
        'headers' => [
            'Accept'        => 'application/json',
            'Authorization' => 'Bearer ' . ORICK_BRAPI_TOKEN,
        ],
    ];
    $tests = [
        'IBOV'         => [ 'url' => orick_brapi_url( '/api/quote/^BVSP', [ 'range' => '1mo', 'interval' => '1d' ] ), 'auth' => true ],
        'ETF single'   => [ 'url' => orick_brapi_url( '/api/quote/IVVB11' ), 'auth' => true ],
        'AwesomeAPI'   => [ 'url' => 'https://economia.awesomeapi.com.br/json/last/USD-BRL,EUR-BRL,GBP-BRL,JPY-BRL', 'auth' => false ],
        'AwesomeAPI 1' => [ 'url' => 'https://economia.awesomeapi.com.br/json/last/USD-BRL', 'auth' => false ],
    ];
    foreach ( $tests as $label => $t ) {
        $req_args = $t['auth']
            ? $args
            : [ 'timeout' => 10, 'headers' => [ 'Accept' => 'application/json' ] ];
        $r = wp_remote_get( $t['url'], $req_args );
        echo "\n=== $label ===\n";
        echo "URL: " . preg_replace( '/token=[^&]+/', 'token=***', $t['url'] ) . "\n";
        if ( is_wp_error( $r ) ) {
            echo "WP_ERROR: " . $r->get_error_message() . "\n";
            continue;
        }
        $code = wp_remote_retrieve_response_code( $r );
        $body = wp_remote_retrieve_body( $r );
        echo "HTTP: $code\n";
        echo "Body: " . substr( $body, 0, 500 ) . "\n";
    }
    exit;
});

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


/* =========================================================
   ARCHIVE: endpoint AJAX "Carregar mais"
   ========================================================= */

// Injeta o endpoint no head do arquivo (só em arquivos/categorias/tag/autor/search)
add_action( 'wp_footer', function() {
    if ( ! ( is_archive() || is_home() || is_search() ) ) return;
    $ajax_url = esc_js( admin_url( 'admin-ajax.php' ) );
    $nonce    = wp_create_nonce( 'oricksilva_load_more' );
    ?>
    <script>
    (function(){
      var btn = document.getElementById('os-load-more');
      if (!btn) return;
      btn.addEventListener('click', function() {
        var page = parseInt(btn.dataset.page || '1', 10);
        var max  = parseInt(btn.dataset.max || '1', 10);
        var q    = btn.dataset.query || '{}';
        btn.disabled = true;
        btn.textContent = 'Carregando…';

        var body = new URLSearchParams();
        body.append('action', 'oricksilva_load_more');
        body.append('nonce', '<?php echo $nonce; ?>');
        body.append('page', page + 1);
        body.append('query', q);

        fetch('<?php echo $ajax_url; ?>', { method: 'POST', body: body })
          .then(function(r){ return r.json(); })
          .then(function(data){
            if (data && data.success && data.html) {
              var grid = document.getElementById('os-archive-grid');
              var temp = document.createElement('div');
              temp.innerHTML = data.html;
              while (temp.firstChild) grid.appendChild(temp.firstChild);
              btn.dataset.page = (page + 1).toString();
              if ((page + 1) >= max) {
                btn.remove();
              } else {
                btn.disabled = false;
                btn.textContent = 'Carregar mais';
              }
            } else {
              btn.textContent = 'Nada mais a carregar';
              btn.disabled = true;
            }
          })
          .catch(function(){
            btn.disabled = false;
            btn.textContent = 'Erro — tentar de novo';
          });
      });
    })();
    </script>
    <?php
}, 99 );

add_action( 'wp_ajax_oricksilva_load_more', 'oricksilva_ajax_load_more' );
add_action( 'wp_ajax_nopriv_oricksilva_load_more', 'oricksilva_ajax_load_more' );
function oricksilva_ajax_load_more() {
    check_ajax_referer( 'oricksilva_load_more', 'nonce' );
    $page  = max( 1, intval( $_POST['page'] ?? 2 ) );
    $q_raw = json_decode( stripslashes( $_POST['query'] ?? '{}' ), true );
    $q_raw = is_array( $q_raw ) ? $q_raw : [];

    $args = [
        'post_type'      => 'post',
        'posts_per_page' => intval( get_option( 'posts_per_page', 12 ) ),
        'paged'          => $page,
    ];
    if ( ! empty( $q_raw['cat'] ) )    $args['cat']    = intval( $q_raw['cat'] );
    if ( ! empty( $q_raw['tag_id'] ) ) $args['tag_id'] = intval( $q_raw['tag_id'] );
    if ( ! empty( $q_raw['author'] ) ) $args['author'] = intval( $q_raw['author'] );
    if ( ! empty( $q_raw['s'] ) )      $args['s']      = sanitize_text_field( $q_raw['s'] );

    $q = new WP_Query( $args );
    if ( ! $q->have_posts() ) {
        wp_send_json_success( [ 'html' => '' ] );
    }

    ob_start();
    while ( $q->have_posts() ) : $q->the_post();
        $cats_p = get_the_category();
        $cat_p  = $cats_p ? $cats_p[0] : null;
        ?>
        <article class="os-card">
          <a href="<?php the_permalink(); ?>" class="os-card-link">
            <?php if ( has_post_thumbnail() ) : ?>
              <div class="os-card-img"><?php the_post_thumbnail( 'medium' ); ?></div>
            <?php else : ?>
              <div class="os-card-img os-img-placeholder"></div>
            <?php endif; ?>
            <div class="os-card-body">
              <?php if ( $cat_p ) : ?>
                <span class="os-card-cat"><?php echo esc_html( mb_strtoupper( $cat_p->name ) ); ?></span>
              <?php endif; ?>
              <h3 class="os-card-title"><?php the_title(); ?></h3>
              <div class="os-card-meta">
                <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'd/m' ) ); ?></time>
              </div>
            </div>
          </a>
        </article>
        <?php
    endwhile;
    wp_reset_postdata();
    $html = ob_get_clean();
    wp_send_json_success( [ 'html' => $html ] );
}

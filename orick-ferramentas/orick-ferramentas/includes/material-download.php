<?php
/**
 * Landing de download de material — /baixar/<slug>/
 *
 * Fluxo:
 *  1. Usuário clica "Baixar" num material com _orick_requer_cadastro=1
 *  2. É redirecionado pra /baixar/<slug>/ (esta rota)
 *  3. Se já é lead logado → servimos o download direto com log
 *  4. Se não → mostramos formulário (cadastro + login); após sucesso, libera
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Orick_Ferr_Material_Download {

    public static function init() {
        add_action( 'init', [ __CLASS__, 'add_rewrite' ] );
        add_filter( 'query_vars', function( $v ) { $v[] = 'orick_baixar_slug'; return $v; } );
        add_action( 'template_redirect', [ __CLASS__, 'handle' ] );
    }

    public static function add_rewrite() {
        add_rewrite_rule( '^baixar/([^/]+)/?$', 'index.php?orick_baixar_slug=$matches[1]', 'top' );
    }

    public static function handle() {
        $slug = get_query_var( 'orick_baixar_slug' );
        if ( ! $slug ) return;

        $post = get_page_by_path( $slug, OBJECT, 'material' );
        if ( ! $post ) {
            status_header( 404 );
            nocache_headers();
            include get_query_template( '404' );
            exit;
        }

        $requer = get_post_meta( $post->ID, '_orick_requer_cadastro', true ) === '1';
        $url    = Orick_Ferr_CPT_Material::download_url( $post->ID );

        // Não requer cadastro — redireciona pro arquivo
        if ( ! $requer ) {
            if ( $url ) { wp_safe_redirect( $url ); exit; }
        }

        $logado = Orick_Ferr_Auth::is_logged_in();

        // Logado + requer cadastro — servir download
        if ( $logado ) {
            // Log do download
            global $wpdb;
            $table = $wpdb->prefix . 'orick_lead_downloads';
            if ( $wpdb->get_var( "SHOW TABLES LIKE '$table'" ) === $table ) {
                $wpdb->insert( $table, [
                    'lead_id'    => Orick_Ferr_Auth::current_lead_id(),
                    'material_id'=> $post->ID,
                    'created_at' => current_time( 'mysql' ),
                ] );
            }
            if ( $url ) { wp_safe_redirect( $url ); exit; }
        }

        // Não logado — renderiza landing
        self::render_gate( $post );
        exit;
    }

    public static function render_gate( $post ) {
        $thumb = get_the_post_thumbnail_url( $post->ID, 'large' );
        $tipo_terms = get_the_terms( $post->ID, 'material_tipo' );
        $tipo = $tipo_terms ? $tipo_terms[0]->name : 'Material';
        $paginas = get_post_meta( $post->ID, '_orick_paginas', true );
        $tab = sanitize_key( $_GET['ofr_tab'] ?? 'cadastro' );
        $redirect_to = home_url( '/baixar/' . $post->post_name . '/' );

        get_header();
        ?>
        <main class="ofr-main">
          <article class="ofr-single">
            <header class="ofr-single-head">
              <div class="ofr-wrap">
                <nav class="ofr-breadcrumb">
                  <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Início</a>
                  <span>›</span>
                  <a href="<?php echo esc_url( get_post_type_archive_link( 'material' ) ); ?>">Materiais</a>
                  <span>›</span>
                  <span>Baixar</span>
                </nav>
                <span class="ofr-kicker">MATERIAL · <?php echo esc_html( strtoupper( $tipo ) ); ?><?php echo $paginas ? ' · ' . intval( $paginas ) . ' PÁGINAS' : ''; ?></span>
                <h1 class="ofr-single-title"><?php echo esc_html( $post->post_title ); ?></h1>
                <?php if ( $post->post_excerpt ) : ?>
                  <p class="ofr-single-lead"><?php echo esc_html( $post->post_excerpt ); ?></p>
                <?php endif; ?>
              </div>
            </header>

            <section class="ofr-single-body">
              <div class="ofr-wrap">
                <?php if ( $thumb ) : ?>
                  <div class="ofr-material-cover"><img src="<?php echo esc_url( $thumb ); ?>" alt=""></div>
                <?php endif; ?>

                <div class="ofr-gate">
                  <div class="ofr-gate-tabs">
                    <a href="<?php echo esc_url( add_query_arg( 'ofr_tab', 'cadastro' ) ); ?>" class="<?php echo $tab === 'cadastro' ? 'is-active' : ''; ?>">Criar cadastro</a>
                    <a href="<?php echo esc_url( add_query_arg( 'ofr_tab', 'login' ) ); ?>" class="<?php echo $tab === 'login' ? 'is-active' : ''; ?>">Já tenho conta</a>
                  </div>
                  <?php if ( $tab === 'login' ) : ?>
                    <?php echo orick_ferr_render_login( [
                      'redirect_to'   => $redirect_to,
                      'context_title' => 'Entre para baixar "' . esc_html( $post->post_title ) . '"',
                      'context_sub'   => 'Use a mesma conta que você já criou pras ferramentas.',
                    ] ); ?>
                  <?php else : ?>
                    <?php echo orick_ferr_render_cadastro( [
                      'redirect_to'   => $redirect_to,
                      'context_title' => 'Cadastre-se para baixar "' . esc_html( $post->post_title ) . '"',
                      'context_sub'   => 'Cadastro gratuito. Com ele você libera todos os materiais e ferramentas.',
                    ] ); ?>
                  <?php endif; ?>
                </div>
              </div>
            </section>
          </article>
        </main>
        <?php
        get_footer();
    }
}

Orick_Ferr_Material_Download::init();

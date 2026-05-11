<?php
/**
 * Shortcodes:
 *  [orick_ferr_cadastro]  — formulário de cadastro (pode ir em qualquer página)
 *  [orick_ferr_login]     — formulário de login
 *  [orick_ferr_grid count="6"] — grid de ferramentas (home / páginas)
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_shortcode( 'orick_ferr_cadastro', function( $atts ) {
    $atts = shortcode_atts( [
        'redirect_to'   => home_url( '/ferramentas/' ),
        'context_title' => 'Crie sua conta para acessar as ferramentas',
        'context_sub'   => 'Preencha os dados abaixo. O acesso é permanente e gratuito.',
    ], $atts );
    return orick_ferr_render_cadastro( $atts );
} );

add_shortcode( 'orick_ferr_login', function( $atts ) {
    $atts = shortcode_atts( [
        'redirect_to'   => home_url( '/ferramentas/' ),
        'context_title' => 'Entrar',
        'context_sub'   => 'Acesse sua conta para usar as ferramentas.',
    ], $atts );
    return orick_ferr_render_login( $atts );
} );

add_shortcode( 'orick_ferr_grid', function( $atts ) {
    $atts = shortcode_atts( [
        'count'   => 6,
        'destaque' => 0, // se 1, só as marcadas como destaque_home
    ], $atts );

    $args = [
        'post_type'      => 'ferramenta',
        'posts_per_page' => (int) $atts['count'],
        'post_status'    => 'publish',
    ];
    if ( $atts['destaque'] ) {
        $args['meta_query'] = [ [
            'key'   => '_orick_destaque_home',
            'value' => '1',
        ] ];
    }

    $q = new WP_Query( $args );
    if ( ! $q->have_posts() ) return '';

    ob_start(); ?>
    <div class="ofr-grid">
      <?php while ( $q->have_posts() ) : $q->the_post();
        $preco = get_post_meta( get_the_ID(), '_orick_preco', true ) ?: 'gratuito';
        $requer = get_post_meta( get_the_ID(), '_orick_requer_login', true );
      ?>
        <a href="<?php the_permalink(); ?>" class="ofr-grid-card">
          <?php if ( has_post_thumbnail() ) : ?>
            <div class="ofr-grid-thumb"><?php the_post_thumbnail( 'medium_large' ); ?></div>
          <?php endif; ?>
          <div class="ofr-grid-body">
            <div class="ofr-grid-meta">
              <span class="ofr-grid-preco ofr-grid-preco-<?php echo esc_attr( $preco ); ?>"><?php echo esc_html( ucfirst( $preco ) ); ?></span>
              <?php if ( $requer === '1' ) : ?>
                <span class="ofr-grid-lock">🔒 Login</span>
              <?php endif; ?>
            </div>
            <h3 class="ofr-grid-title"><?php the_title(); ?></h3>
            <p class="ofr-grid-excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
          </div>
        </a>
      <?php endwhile; wp_reset_postdata(); ?>
    </div>
    <?php
    return ob_get_clean();
} );

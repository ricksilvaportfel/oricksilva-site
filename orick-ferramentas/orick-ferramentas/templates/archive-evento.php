<?php
/**
 * Archive: Eventos — /eventos/
 * Hero (próximo evento) + grid tipográfico separado em Próximos / Passados.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();

$today = current_time( 'Y-m-d' );

$proximos = new WP_Query( [
    'post_type'      => 'evento',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'meta_key'       => '_orick_ev_data',
    'orderby'        => 'meta_value',
    'order'          => 'ASC',
    'meta_query'     => [ [ 'key' => '_orick_ev_data', 'value' => $today, 'compare' => '>=', 'type' => 'DATE' ] ],
] );
$passados = new WP_Query( [
    'post_type'      => 'evento',
    'post_status'    => 'publish',
    'posts_per_page' => 12,
    'meta_key'       => '_orick_ev_data',
    'orderby'        => 'meta_value',
    'order'          => 'DESC',
    'meta_query'     => [ [ 'key' => '_orick_ev_data', 'value' => $today, 'compare' => '<', 'type' => 'DATE' ] ],
] );

// Primeiro evento futuro vira hero
$hero_id  = 0;
$prox_ids = [];
if ( $proximos->have_posts() ) {
    while ( $proximos->have_posts() ) : $proximos->the_post();
        $prox_ids[] = get_the_ID();
    endwhile;
    wp_reset_postdata();
    $hero_id = array_shift( $prox_ids );
}

$render_grid = function( $ids ) {
    if ( ! $ids ) return;
    echo '<div class="event-grid">';
    foreach ( $ids as $pid ) oricksilva_render_event_card( $pid, 'default' );
    echo '</div>';
};
?>

<main class="os-archive os-archive--eventos">
  <header class="os-archive-head">
    <div class="os-wrap">
      <span class="os-archive-kicker">Eventos</span>
      <h1 class="os-archive-title">Onde nos <em>encontrar</em></h1>
      <p class="os-archive-desc">Palestras, workshops, lançamentos — presenciais e online.</p>
    </div>
  </header>

  <?php if ( $hero_id ) : ?>
    <section class="tool-hero-wrap">
      <div class="os-wrap">
        <?php oricksilva_render_event_card( $hero_id, 'hero' ); ?>
      </div>
    </section>
  <?php endif; ?>

  <div class="os-archive-grid-wrap">
    <div class="os-wrap">
      <?php if ( $prox_ids ) : ?>
        <h2 class="os-archive-subhead">Próximos</h2>
        <?php $render_grid( $prox_ids ); ?>
      <?php elseif ( ! $hero_id ) : ?>
        <div class="os-archive-empty">
          <h2>Nenhum evento agendado</h2>
          <p>Entre na newsletter pra ser avisado dos próximos encontros.</p>
        </div>
      <?php endif; ?>

      <?php if ( $passados->have_posts() ) :
        $pas_ids = [];
        while ( $passados->have_posts() ) : $passados->the_post();
          $pas_ids[] = get_the_ID();
        endwhile;
        wp_reset_postdata();
      ?>
        <h2 class="os-archive-subhead" style="margin-top:72px;">Passados</h2>
        <?php $render_grid( $pas_ids ); ?>
      <?php endif; ?>
    </div>
  </div>
</main>

<?php get_footer();

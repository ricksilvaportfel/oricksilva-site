<?php
/**
 * Single: Episódio de podcast
 * Layout conforme design-spec-podcast.md seção 6 — 720px centralizado,
 * iframe Spotify no topo, shownotes, plataformas, relacionados.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

while ( have_posts() ) : the_post();
    $d = orick_episodio_data( get_the_ID() );
    $dur_human = orick_ep_dur_human( $d['duracao'] );
    $kicker = 'EP ' . max( 1, $d['numero'] ) . ' · ' . get_the_date( 'd M Y' );
    if ( $dur_human ) $kicker .= ' · ' . $dur_human;

    $guest = '';
    if ( $d['convidado'] ) {
        $guest = 'com ' . $d['convidado'];
        if ( $d['convidado_cargo'] ) $guest .= ' (' . $d['convidado_cargo'] . ')';
    }

    // Resolve iframe do Spotify: meta _orick_ep_spotify_embed tem prioridade;
    // senão, deriva da URL do Spotify.
    $embed_html = '';
    if ( ! empty( $d['spotify_embed'] ) ) {
        $embed_html = sprintf(
            '<iframe src="%s" height="152" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy"></iframe>',
            esc_url( $d['spotify_embed'] )
        );
    } elseif ( $d['spotify'] && class_exists( 'Orick_Ferr_CPT_Episodio' ) ) {
        $embed_html = Orick_Ferr_CPT_Episodio::spotify_embed( $d['spotify'] );
    }
    ?>

    <main class="os-podcast-single">
      <span class="os-podcast-single-kicker"><?php echo esc_html( $kicker ); ?></span>
      <h1 class="os-podcast-single-title"><?php the_title(); ?></h1>
      <?php if ( $guest ) : ?>
        <div class="os-podcast-single-guest"><?php echo esc_html( $guest ); ?></div>
      <?php endif; ?>

      <?php if ( $embed_html ) : ?>
        <div class="os-podcast-single-embed"><?php echo $embed_html; // embed confiável: do próprio plugin ?></div>
      <?php endif; ?>

      <?php if ( $d['spotify'] || $d['apple'] || $d['youtube'] ) : ?>
        <div class="os-podcast-platforms">
          <?php if ( $d['spotify'] ) : ?>
            <a class="os-podcast-platform" href="<?php echo esc_url( $d['spotify'] ); ?>" target="_blank" rel="noopener">Spotify</a>
          <?php endif; ?>
          <?php if ( $d['apple'] ) : ?>
            <a class="os-podcast-platform" href="<?php echo esc_url( $d['apple'] ); ?>" target="_blank" rel="noopener">Apple</a>
          <?php endif; ?>
          <?php if ( $d['youtube'] ) : ?>
            <a class="os-podcast-platform" href="<?php echo esc_url( $d['youtube'] ); ?>" target="_blank" rel="noopener">YouTube</a>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <div class="os-podcast-single-content">
        <?php the_content(); ?>
      </div>

      <?php
      $related = new WP_Query( [
          'post_type'      => 'episodio',
          'posts_per_page' => 3,
          'post__not_in'   => [ get_the_ID() ],
          'orderby'        => 'date',
          'order'          => 'DESC',
      ] );
      if ( $related->have_posts() ) :
          $total_eps = orick_ep_total();
          $i = max( 1, $total_eps - 1 );
      ?>
        <section class="os-podcast-single-related">
          <h2 class="os-podcast-all-title">Mais episódios</h2>
          <div class="os-podcast-list">
            <?php while ( $related->have_posts() ) : $related->the_post();
              orick_ep_render_item( orick_episodio_data( get_the_ID() ), $i-- );
            endwhile; wp_reset_postdata(); ?>
          </div>
        </section>
      <?php endif; ?>
    </main>

<?php endwhile;
get_footer();

<?php
/**
 * Archive: Podcast — /podcast/
 * Layout conforme design-spec-podcast.md seção 4.
 * Header editorial + hero (card 1:1 + coluna de texto) + lista "Todos os episódios" + paginação.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

$ids = [];
if ( have_posts() ) {
    while ( have_posts() ) : the_post();
        $ids[] = get_the_ID();
    endwhile;
    rewind_posts();
}

$hero_id  = $ids[0] ?? 0;
$rest_ids = array_slice( $ids, 1 );

$total     = orick_ep_total();
$start_num = max( 1, $total );
?>

<main class="os-archive os-archive--podcast">
  <div class="os-wrap">

    <header class="os-archive-head">
      <span class="os-archive-kicker">Podcast</span>
      <h1 class="os-archive-title">Conversas <em>com quem está fazendo</em></h1>
      <p class="os-archive-desc">Episódios com convidados do mercado financeiro — bastidores, táticas e o que realmente move a agulha.</p>
    </header>

    <?php if ( ! $hero_id ) : ?>

      <div class="os-archive-empty" style="padding:40px 0;color:var(--text-mute,rgba(228,216,199,0.55));">Nenhum episódio publicado ainda.</div>

    <?php else :
      $hero  = orick_episodio_data( $hero_id );
      $h_dur = orick_ep_dur_human( $hero['duracao'] );
      $h_kick = 'EP ' . max( 1, $hero['numero'] );
      if ( $h_dur ) $h_kick .= ' · ' . $h_dur;
      $h_desc = $hero['spotify_embed'] ? '' : wp_trim_words( get_the_excerpt( $hero_id ), 45 );
    ?>

      <section class="os-podcast-hero">
        <?php orick_ep_render_featured( $hero ); ?>

        <div class="os-podcast-hero-meta">
          <span class="os-podcast-hero-kicker"><?php echo esc_html( $h_kick ); ?></span>
          <h2 class="os-podcast-hero-title"><?php echo esc_html( $hero['title'] ); ?></h2>

          <?php if ( $hero['convidado'] ) :
            $guest = 'com ' . $hero['convidado'];
            if ( $hero['convidado_cargo'] ) $guest .= ' (' . $hero['convidado_cargo'] . ')';
          ?>
            <div class="os-podcast-hero-guest"><?php echo esc_html( $guest ); ?></div>
          <?php endif; ?>

          <?php if ( $h_desc ) : ?>
            <p class="os-podcast-hero-desc"><?php echo esc_html( $h_desc ); ?></p>
          <?php endif; ?>

          <?php if ( $hero['spotify'] || $hero['apple'] || $hero['youtube'] ) : ?>
            <div class="os-podcast-platforms">
              <?php if ( $hero['spotify'] ) : ?>
                <a class="os-podcast-platform" href="<?php echo esc_url( $hero['spotify'] ); ?>" target="_blank" rel="noopener">Spotify</a>
              <?php endif; ?>
              <?php if ( $hero['apple'] ) : ?>
                <a class="os-podcast-platform" href="<?php echo esc_url( $hero['apple'] ); ?>" target="_blank" rel="noopener">Apple</a>
              <?php endif; ?>
              <?php if ( $hero['youtube'] ) : ?>
                <a class="os-podcast-platform" href="<?php echo esc_url( $hero['youtube'] ); ?>" target="_blank" rel="noopener">YouTube</a>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        </div>
      </section>

      <?php if ( $rest_ids ) : ?>
        <section class="os-podcast-all">
          <h2 class="os-podcast-all-title">Todos os episódios</h2>
          <div class="os-podcast-list">
            <?php
            $i = $start_num - 1;
            foreach ( $rest_ids as $pid ) :
              $d = orick_episodio_data( $pid );
              orick_ep_render_item( $d, $i-- );
            endforeach;
            ?>
          </div>
        </section>
      <?php endif; ?>

      <nav class="os-archive-pager">
        <?php echo paginate_links( [
          'prev_text' => '« Anteriores',
          'next_text' => 'Próximos »',
          'mid_size'  => 1,
        ] ); ?>
      </nav>

    <?php endif; ?>

  </div>
</main>

<?php get_footer();

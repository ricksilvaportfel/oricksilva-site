<?php
/**
 * Archive: Vídeos — /videos/
 *
 * Layout editorial: hero grande (último vídeo) + lista lateral (próximos 5)
 * + grid 3 col com "Mais vídeos".
 * Usa helpers orick_video_data() do tema filho; se não existir, cai em fallback simples.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$has_helper = function_exists( 'orick_video_data' );

get_header(); ?>

<main class="os-archive">

  <header class="os-archive-head os-wrap">
    <span class="os-archive-kicker">VÍDEOS</span>
    <h1 class="os-archive-title">Canal <em>O Rick Silva</em></h1>
    <p class="os-archive-desc">Análises, entrevistas e masterclasses. Atualizado automaticamente do YouTube.</p>
  </header>

  <?php if ( have_posts() ) :
    // pega todos os IDs pra manipular a ordem
    ?>

    <?php
    // PRIMEIRO: hero (post 1) + side list (posts 2-6)
    the_post();
    $hero = $has_helper ? orick_video_data( get_the_ID() ) : [
        'thumb' => has_post_thumbnail() ? get_the_post_thumbnail_url( get_the_ID(), 'large' ) : '',
        'watch_url' => get_permalink(),
        'duration' => get_post_meta( get_the_ID(), '_orick_duracao', true ),
        'kicker' => '',
    ];
    ?>

    <section class="os-wrap os-videos-hero">
      <a class="os-videos-hero-main" href="<?php echo esc_url( $hero['watch_url'] ); ?>" <?php if ( ! empty( $hero['id'] ) ) echo 'target="_blank" rel="noopener"'; ?>>
        <?php if ( $hero['thumb'] ) : ?>
          <img src="<?php echo esc_url( $hero['thumb'] ); ?>" alt="" loading="eager">
        <?php else : ?>
          <div class="os-fallback"></div>
        <?php endif; ?>
        <div class="os-play-circle">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
        </div>
        <?php if ( ! empty( $hero['duration'] ) ) : ?>
          <span class="os-video-dur"><?php echo esc_html( $hero['duration'] ); ?></span>
        <?php endif; ?>
        <div class="os-videos-hero-caption">
          <div class="os-card-cat">EM DESTAQUE</div>
          <h2 class="os-videos-hero-title"><?php the_title(); ?></h2>
          <div class="os-videos-hero-meta">
            <span><?php echo esc_html( get_the_date( 'd M Y' ) ); ?></span>
          </div>
        </div>
      </a>

      <aside class="os-videos-side">
        <div class="os-videos-side-title">A seguir</div>
        <div class="os-video-list">
        <?php
        $side_count = 0;
        while ( have_posts() && $side_count < 5 ) :
          the_post();
          $side_count++;
          $vi = $has_helper ? orick_video_data( get_the_ID() ) : [
              'thumb' => has_post_thumbnail() ? get_the_post_thumbnail_url( get_the_ID(), 'medium' ) : '',
              'watch_url' => get_permalink(),
              'duration' => get_post_meta( get_the_ID(), '_orick_duracao', true ),
              'kicker' => '',
          ];
        ?>
          <a class="os-video-item" href="<?php echo esc_url( $vi['watch_url'] ); ?>" <?php if ( ! empty( $vi['id'] ) ) echo 'target="_blank" rel="noopener"'; ?>>
            <div class="os-video-thumb">
              <?php if ( $vi['thumb'] ) : ?>
                <img src="<?php echo esc_url( $vi['thumb'] ); ?>" alt="" loading="lazy">
              <?php else : ?>
                <div class="os-fallback"></div>
              <?php endif; ?>
              <div class="os-mini-play"><svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></div>
              <?php if ( ! empty( $vi['duration'] ) ) : ?>
                <span class="os-video-dur-mini"><?php echo esc_html( $vi['duration'] ); ?></span>
              <?php endif; ?>
            </div>
            <div>
              <div class="os-card-cat" style="font-size:10px;">Vídeo</div>
              <div class="os-card-title" style="font-size:14px;margin-top:3px;line-height:1.25;"><?php the_title(); ?></div>
              <div style="font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--text-mute);margin-top:4px;">
                <?php echo esc_html( get_the_date( 'd M Y' ) ); ?>
              </div>
            </div>
          </a>
        <?php endwhile; ?>
        </div>
      </aside>
    </section>

    <?php
    // RESTANTE: grid 3 col
    $remaining_html = '';
    while ( have_posts() ) :
      the_post();
      $vg = $has_helper ? orick_video_data( get_the_ID() ) : [
          'thumb' => has_post_thumbnail() ? get_the_post_thumbnail_url( get_the_ID(), 'medium_large' ) : '',
          'watch_url' => get_permalink(),
          'duration' => get_post_meta( get_the_ID(), '_orick_duracao', true ),
          'kicker' => '',
      ];
      ob_start(); ?>
      <a class="os-video-card" href="<?php echo esc_url( $vg['watch_url'] ); ?>" <?php if ( ! empty( $vg['id'] ) ) echo 'target="_blank" rel="noopener"'; ?>>
        <div class="os-video-card-thumb">
          <?php if ( $vg['thumb'] ) : ?>
            <img src="<?php echo esc_url( $vg['thumb'] ); ?>" alt="" loading="lazy">
          <?php else : ?>
            <div class="os-fallback"></div>
          <?php endif; ?>
          <div class="os-mini-play" style="width:40px;height:40px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
          </div>
          <?php if ( ! empty( $vg['duration'] ) ) : ?>
            <span class="os-video-dur"><?php echo esc_html( $vg['duration'] ); ?></span>
          <?php endif; ?>
        </div>
        <div class="os-video-card-body">
          <div class="os-card-cat">Vídeo</div>
          <div class="os-card-title" style="font-size:16px;margin-top:6px;"><?php the_title(); ?></div>
          <div style="font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--text-mute);margin-top:8px;">
            <?php echo esc_html( get_the_date( 'd M Y' ) ); ?>
          </div>
        </div>
      </a>
      <?php $remaining_html .= ob_get_clean();
    endwhile;

    if ( $remaining_html ) : ?>
      <section class="os-wrap os-videos-grid">
        <h3 class="os-videos-grid-title">Mais vídeos</h3>
        <div class="os-videos-grid-cards"><?php echo $remaining_html; ?></div>
      </section>
    <?php endif; ?>

    <?php
    // Paginação
    $pag = paginate_links( [
        'prev_text' => '← Anteriores',
        'next_text' => 'Próximos →',
    ] );
    if ( $pag ) : ?>
      <nav class="os-archive-pager os-wrap"><?php echo $pag; ?></nav>
    <?php endif; ?>

  <?php else : ?>
    <section class="os-wrap" style="padding:80px 0;text-align:center;">
      <p style="color:var(--text-mute);">Nenhum vídeo publicado.</p>
    </section>
  <?php endif; ?>

</main>

<?php get_footer(); ?>

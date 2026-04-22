<?php
/**
 * Template Name: Vídeos (Listagem)
 * Template Post Type: page
 *
 * Atribua este template à página "Vídeos":
 * Páginas → Vídeos → Atributos da Página → Modelo → "Vídeos (Listagem)".
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$paged = max( 1, get_query_var( 'paged' ) ? get_query_var( 'paged' ) : ( get_query_var( 'page' ) ?: 1 ) );

// Busca posts com URL de YouTube preenchida, independente da categoria
$os_videos = new WP_Query( [
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => 13, // 1 hero + 12 grid
    'paged'          => $paged,
    'meta_query'     => [
        [
            'key'     => '_orick_video_url',
            'compare' => 'EXISTS',
        ],
        [
            'key'     => '_orick_video_url',
            'value'   => '',
            'compare' => '!=',
        ],
    ],
] );

get_header(); ?>

<main class="os-archive">

  <header class="os-archive-head os-wrap">
    <span class="os-archive-kicker">VÍDEOS</span>
    <h1 class="os-archive-title">Canal <em>O Rick Silva</em></h1>
    <p class="os-archive-desc">Análises, entrevistas e masterclasses. Novos vídeos toda semana.</p>
  </header>

  <?php if ( $os_videos->have_posts() ) :
    // HERO: primeiro vídeo grande + lista lateral de 5
    $os_videos->the_post();
    $hero = orick_video_data( get_the_ID() );
    ?>

    <section class="os-wrap os-videos-hero">
      <a class="os-videos-hero-main" href="<?php echo esc_url( $hero['watch_url'] ); ?>" target="_blank" rel="noopener">
        <?php if ( $hero['thumb'] ) : ?>
          <img src="<?php echo esc_url( $hero['thumb'] ); ?>" alt="" loading="eager">
        <?php else : ?>
          <div class="os-fallback"></div>
        <?php endif; ?>
        <div class="os-play-circle">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
        </div>
        <?php if ( $hero['duration'] ) : ?>
          <span class="os-video-dur"><?php echo esc_html( $hero['duration'] ); ?></span>
        <?php endif; ?>
        <div class="os-videos-hero-caption">
          <div class="os-card-cat"><?php echo esc_html( $hero['kicker'] ?: 'EM DESTAQUE' ); ?></div>
          <h2 class="os-videos-hero-title"><?php the_title(); ?></h2>
          <div class="os-videos-hero-meta">
            <span><?php echo esc_html( get_the_author() ); ?></span>
            <span>·</span>
            <span><?php echo esc_html( get_the_date( 'd M Y' ) ); ?></span>
          </div>
        </div>
      </a>

      <aside class="os-videos-side">
        <div class="os-videos-side-title">A seguir</div>
        <div class="os-video-list">
        <?php
        $count = 0;
        while ( $os_videos->have_posts() && $count < 5 ) :
          $os_videos->the_post();
          $count++;
          $vi = orick_video_data( get_the_ID() );
        ?>
          <a class="os-video-item" href="<?php echo esc_url( $vi['watch_url'] ); ?>" target="_blank" rel="noopener">
            <div class="os-video-thumb">
              <?php if ( $vi['thumb'] ) : ?>
                <img src="<?php echo esc_url( $vi['thumb'] ); ?>" alt="" loading="lazy">
              <?php else : ?>
                <div class="os-fallback"></div>
              <?php endif; ?>
              <div class="os-mini-play"><svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></div>
              <?php if ( $vi['duration'] ) : ?>
                <span class="os-video-dur-mini"><?php echo esc_html( $vi['duration'] ); ?></span>
              <?php endif; ?>
            </div>
            <div>
              <div class="os-card-cat" style="font-size:10px;"><?php echo esc_html( $vi['kicker'] ?: 'Vídeo' ); ?></div>
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
    // GRID: restante dos vídeos em cards 3 col
    $remaining_html = '';
    while ( $os_videos->have_posts() ) :
      $os_videos->the_post();
      $vg = orick_video_data( get_the_ID() );
      ob_start(); ?>
      <a class="os-video-card" href="<?php echo esc_url( $vg['watch_url'] ); ?>" target="_blank" rel="noopener">
        <div class="os-video-card-thumb">
          <?php if ( $vg['thumb'] ) : ?>
            <img src="<?php echo esc_url( $vg['thumb'] ); ?>" alt="" loading="lazy">
          <?php else : ?>
            <div class="os-fallback"></div>
          <?php endif; ?>
          <div class="os-mini-play" style="width:40px;height:40px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
          </div>
          <?php if ( $vg['duration'] ) : ?>
            <span class="os-video-dur"><?php echo esc_html( $vg['duration'] ); ?></span>
          <?php endif; ?>
        </div>
        <div class="os-video-card-body">
          <div class="os-card-cat"><?php echo esc_html( $vg['kicker'] ?: 'Vídeo' ); ?></div>
          <div class="os-card-title" style="font-size:16px;margin-top:6px;"><?php the_title(); ?></div>
          <div style="font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--text-mute);margin-top:8px;">
            <?php echo esc_html( get_the_date( 'd M Y' ) ); ?> · <?php echo esc_html( get_the_author() ); ?>
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

    <?php if ( $os_videos->max_num_pages > 1 ) : ?>
      <nav class="os-archive-pager os-wrap">
        <?php echo paginate_links( [
          'total'     => $os_videos->max_num_pages,
          'current'   => $paged,
          'prev_text' => '← Anteriores',
          'next_text' => 'Próximos →',
        ] ); ?>
      </nav>
    <?php endif; ?>

  <?php else : ?>
    <section class="os-wrap" style="padding:80px 0;text-align:center;">
      <p style="color:var(--text-mute);">
        Nenhum vídeo publicado. Crie um post e preencha <strong>URL do YouTube</strong> no editor.
      </p>
    </section>
  <?php endif; wp_reset_postdata(); ?>

</main>

<?php get_footer(); ?>

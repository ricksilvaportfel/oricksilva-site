<?php
/**
 * Archive: Vídeos — /videos/
 * Layout editorial conforme design-spec-videos.md:
 *   header → hero (1.4fr + 1fr) → grid "Mais vídeos" (3 col) → paginação
 * Usa as classes .os-* já definidas em style.css do tema oricksilva-child.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Resolve dados do vídeo (YouTube id, thumb, duração, URLs).
 */
if ( ! function_exists( 'orick_video_data' ) ) {
    function orick_video_data( $post_id ) {
        $yt  = get_post_meta( $post_id, '_orick_youtube_id', true );
        $url = get_post_meta( $post_id, '_orick_video_url', true );
        $dur = get_post_meta( $post_id, '_orick_duracao', true );

        $thumb = get_the_post_thumbnail_url( $post_id, 'large' );
        if ( ! $thumb && $yt ) {
            $thumb = 'https://i.ytimg.com/vi/' . $yt . '/maxresdefault.jpg';
        }

        $watch_url = $url ?: ( $yt ? 'https://www.youtube.com/watch?v=' . $yt : get_permalink( $post_id ) );

        return [
            'id'        => $yt,
            'thumb'     => $thumb,
            'watch_url' => $watch_url,
            'embed_url' => $yt ? 'https://www.youtube.com/embed/' . $yt : '',
            'duration'  => $dur,
            'is_yt'     => (bool) ( $yt || ( $url && strpos( $url, 'youtu' ) !== false ) ),
        ];
    }
}

/**
 * Renderiza thumb 16:9 com fallback. $size: 'big' | 'small' | 'card'.
 */
function orick_archive_video_thumb( $post_id, $data, $size = 'card', $eager = false ) {
    $loading = $eager ? 'eager' : 'lazy';
    if ( ! empty( $data['thumb'] ) ) {
        printf(
            '<img src="%s" alt="%s" loading="%s">',
            esc_url( $data['thumb'] ),
            esc_attr( get_the_title( $post_id ) ),
            esc_attr( $loading )
        );
    } else {
        echo '<div class="os-fallback"></div>';
    }
}

/** SVG do play (24×24 pro hero, 10×10 pro mini). */
function orick_archive_play_svg( $size = 24 ) {
    printf(
        '<svg width="%1$d" height="%1$d" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M8 5v14l11-7z"/></svg>',
        (int) $size
    );
}

/** Link attrs (abre YouTube em nova aba). */
function orick_archive_link_attrs( $data ) {
    if ( ! empty( $data['is_yt'] ) ) {
        return ' target="_blank" rel="noopener"';
    }
    return '';
}

get_header();

/* Coleta todos os posts da página corrente pra distribuir entre hero/side/grid. */
$all_posts = [];
if ( have_posts() ) {
    while ( have_posts() ) : the_post();
        $all_posts[] = get_the_ID();
    endwhile;
    rewind_posts();
}

$hero_id    = $all_posts[0] ?? 0;
$side_ids   = array_slice( $all_posts, 1, 5 );
$grid_ids   = array_slice( $all_posts, 6 );
?>

<main class="os-archive os-archive--videos">
  <div class="os-wrap">

    <!-- 1. Header editorial -->
    <header class="os-archive-head">
      <span class="os-archive-kicker">Vídeos</span>
      <h1 class="os-archive-title">Canal <em>O Rick Silva</em></h1>
      <p class="os-archive-desc">Análises, entrevistas e masterclasses. Tudo o que publiquei no canal, em ordem cronológica.</p>
    </header>

    <?php if ( ! $hero_id ) : ?>

      <div class="os-archive-empty">Nenhum vídeo disponível ainda.</div>

    <?php else :
      $hero   = orick_video_data( $hero_id );
      $h_cats = get_the_category( $hero_id );
      $h_kick = $h_cats ? strtoupper( $h_cats[0]->name ) : 'EM DESTAQUE';
    ?>

      <!-- 2. Hero (1.4fr + 1fr) -->
      <section class="os-videos-hero">

        <a class="os-video-big os-videos-hero-main"
           href="<?php echo esc_url( $hero['watch_url'] ); ?>"
           <?php echo orick_archive_link_attrs( $hero ); ?>>
          <?php orick_archive_video_thumb( $hero_id, $hero, 'big', true ); ?>

          <span class="os-play-circle" aria-hidden="true">
            <?php orick_archive_play_svg( 24 ); ?>
          </span>

          <?php if ( ! empty( $hero['duration'] ) ) : ?>
            <span class="os-video-dur"><?php echo esc_html( $hero['duration'] ); ?></span>
          <?php endif; ?>

          <div class="os-caption os-videos-hero-caption">
            <span class="os-card-cat"><?php echo esc_html( $h_kick ); ?></span>
            <h2 class="os-card-title os-videos-hero-title"><?php echo esc_html( get_the_title( $hero_id ) ); ?></h2>
            <div class="os-videos-hero-meta"><?php echo esc_html( get_the_date( 'd M Y', $hero_id ) ); ?></div>
          </div>
        </a>

        <aside class="os-videos-side">
          <div class="os-videos-side-title">A seguir</div>

          <?php if ( $side_ids ) : ?>
            <div class="os-video-list">
              <?php foreach ( $side_ids as $pid ) :
                $d = orick_video_data( $pid );
              ?>
                <a class="os-video-item"
                   href="<?php echo esc_url( $d['watch_url'] ); ?>"
                   <?php echo orick_archive_link_attrs( $d ); ?>>

                  <div class="os-video-thumb">
                    <?php orick_archive_video_thumb( $pid, $d, 'small' ); ?>
                    <span class="os-mini-play" aria-hidden="true">
                      <?php orick_archive_play_svg( 10 ); ?>
                    </span>
                    <?php if ( ! empty( $d['duration'] ) ) : ?>
                      <span class="os-video-dur-mini"><?php echo esc_html( $d['duration'] ); ?></span>
                    <?php endif; ?>
                  </div>

                  <div class="os-video-item-body">
                    <span class="os-card-cat">Episódio</span>
                    <h3 class="os-card-title"><?php echo esc_html( get_the_title( $pid ) ); ?></h3>
                    <div class="os-card-meta"><?php echo esc_html( get_the_date( 'd M Y', $pid ) ); ?></div>
                  </div>
                </a>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </aside>
      </section>

      <!-- 3. Grid "Mais vídeos" -->
      <?php if ( $grid_ids ) : ?>
        <section class="os-videos-grid">
          <h2 class="os-videos-grid-title">Mais vídeos</h2>
          <div class="os-videos-grid-cards">
            <?php foreach ( $grid_ids as $pid ) :
              $d = orick_video_data( $pid );
            ?>
              <a class="os-video-card"
                 href="<?php echo esc_url( $d['watch_url'] ); ?>"
                 <?php echo orick_archive_link_attrs( $d ); ?>>

                <div class="os-video-card-thumb">
                  <?php orick_archive_video_thumb( $pid, $d, 'card' ); ?>
                  <span class="os-play-circle" aria-hidden="true">
                    <?php orick_archive_play_svg( 24 ); ?>
                  </span>
                  <?php if ( ! empty( $d['duration'] ) ) : ?>
                    <span class="os-video-dur"><?php echo esc_html( $d['duration'] ); ?></span>
                  <?php endif; ?>
                </div>

                <div class="os-video-card-body">
                  <span class="os-card-cat">Vídeo</span>
                  <h3 class="os-card-title"><?php echo esc_html( get_the_title( $pid ) ); ?></h3>
                  <div class="os-card-meta"><?php echo esc_html( get_the_date( 'd M Y', $pid ) ); ?></div>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
        </section>
      <?php endif; ?>

      <!-- 4. Paginação -->
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

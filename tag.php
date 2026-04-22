<?php
/**
 * Tag archive — mini-home temática
 * /tag/{slug}/ — agrega artigos + vídeos + materiais + podcast + eventos daquela tag.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

$tag = get_queried_object();
if ( ! $tag || empty( $tag->term_id ) ) { get_template_part( 'archive' ); return; }

$tag_id   = (int) $tag->term_id;
$tag_name = $tag->name;
$tag_slug = $tag->slug;

// Helper — busca posts por tag + categoria (slug), N itens
$q_by_tag_cat = function( $cat_slug, $n ) use ( $tag_id ) {
    return new WP_Query( [
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => $n,
        'ignore_sticky_posts' => true,
        'tax_query'      => [
            'relation' => 'AND',
            [
                'taxonomy' => 'post_tag',
                'field'    => 'term_id',
                'terms'    => [ $tag_id ],
            ],
            [
                'taxonomy' => 'category',
                'field'    => 'slug',
                'terms'    => [ $cat_slug ],
            ],
        ],
    ] );
};

// Artigos = tag + categoria "artigos" (ou qualquer categoria que não seja uma das outras)
$q_artigos = new WP_Query( [
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => 8,
    'ignore_sticky_posts' => true,
    'tag_id'         => $tag_id,
    'category__not_in' => array_filter( array_map( function($slug) {
        $c = get_category_by_slug( $slug );
        return $c ? $c->term_id : 0;
    }, [ 'videos', 'podcast', 'materiais', 'eventos', 'ferramentas' ] ) ),
] );

// Vídeos = tag + categoria videos OU tag + meta _orick_video_url
$q_videos = new WP_Query( [
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => 4,
    'tag_id'         => $tag_id,
    'meta_query'     => [
        [ 'key' => '_orick_video_url', 'compare' => 'EXISTS' ],
        [ 'key' => '_orick_video_url', 'value' => '', 'compare' => '!=' ],
    ],
] );

$q_materiais = $q_by_tag_cat( 'materiais', 3 );
$q_podcast   = $q_by_tag_cat( 'podcast', 4 );
$q_eventos   = $q_by_tag_cat( 'eventos', 3 );

$total_count = $q_artigos->found_posts + $q_videos->found_posts + $q_materiais->found_posts + $q_podcast->found_posts + $q_eventos->found_posts;

get_header(); ?>

<main class="os-tag-home">

  <header class="os-tag-hero os-wrap">
    <span class="os-archive-kicker">TAG · <?php echo esc_html( strtoupper( $tag_name ) ); ?></span>
    <h1 class="os-archive-title"><?php echo esc_html( $tag_name ); ?></h1>
    <?php if ( $tag->description ) : ?>
      <p class="os-archive-desc"><?php echo esc_html( $tag->description ); ?></p>
    <?php endif; ?>
    <div class="os-tag-stats">
      <?php echo (int) $total_count; ?> publicações ·
      <?php echo (int) $q_artigos->found_posts; ?> artigos ·
      <?php echo (int) $q_videos->found_posts; ?> vídeos ·
      <?php echo (int) $q_materiais->found_posts; ?> materiais ·
      <?php echo (int) $q_podcast->found_posts; ?> episódios
    </div>
  </header>

  <?php /* ARTIGOS — 1 destaque + grid */ ?>
  <?php if ( $q_artigos->have_posts() ) : ?>
    <section class="os-wrap os-tag-section">
      <div class="os-sec-head">
        <h2 class="os-sec-title">Artigos sobre <em><?php echo esc_html( $tag_name ); ?></em></h2>
        <a class="os-sec-link" href="<?php echo esc_url( home_url( '/artigos/?tag=' . $tag_slug ) ); ?>">Ver todos →</a>
      </div>

      <div class="os-tag-artigos">
        <?php $i = 0; while ( $q_artigos->have_posts() ) : $q_artigos->the_post(); $i++; ?>
          <?php if ( $i === 1 ) : ?>
            <a class="os-tag-artigo-hero" href="<?php the_permalink(); ?>">
              <div class="os-tag-artigo-hero-img">
                <?php if ( has_post_thumbnail() ) the_post_thumbnail( 'large' ); else echo '<div class="os-fallback"></div>'; ?>
              </div>
              <div class="os-tag-artigo-hero-body">
                <?php $c = get_the_category(); if ( $c ) : ?>
                  <div class="os-card-cat"><?php echo esc_html( $c[0]->name ); ?></div>
                <?php endif; ?>
                <h3><?php the_title(); ?></h3>
                <p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22, '…' ) ); ?></p>
                <div class="os-tag-artigo-hero-meta">
                  <?php echo esc_html( get_the_author() ); ?> · <?php echo esc_html( get_the_date( 'd M Y' ) ); ?>
                </div>
              </div>
            </a>
          <?php else : ?>
            <a class="os-tag-artigo-card" href="<?php the_permalink(); ?>">
              <div class="os-tag-artigo-card-img">
                <?php if ( has_post_thumbnail() ) the_post_thumbnail( 'medium' ); else echo '<div class="os-fallback"></div>'; ?>
              </div>
              <div>
                <?php $c = get_the_category(); if ( $c ) : ?>
                  <div class="os-card-cat" style="font-size:10px;"><?php echo esc_html( $c[0]->name ); ?></div>
                <?php endif; ?>
                <div class="os-card-title" style="font-size:15px;margin-top:4px;"><?php the_title(); ?></div>
                <div style="font-family:'JetBrains Mono',monospace;font-size:10px;color:var(--text-mute);margin-top:6px;">
                  <?php echo esc_html( get_the_date( 'd M' ) ); ?>
                </div>
              </div>
            </a>
          <?php endif; ?>
        <?php endwhile; wp_reset_postdata(); ?>
      </div>
    </section>
  <?php endif; ?>

  <?php /* VÍDEOS */ ?>
  <?php if ( $q_videos->have_posts() ) : ?>
    <section class="os-wrap os-tag-section">
      <div class="os-sec-head">
        <h2 class="os-sec-title">Vídeos</h2>
        <a class="os-sec-link" href="<?php echo esc_url( home_url( '/videos/' ) ); ?>">Canal completo →</a>
      </div>
      <div class="os-videos-grid-cards">
        <?php while ( $q_videos->have_posts() ) : $q_videos->the_post();
          $v = orick_video_data( get_the_ID() ); ?>
          <a class="os-video-card" href="<?php echo esc_url( $v['watch_url'] ); ?>" target="_blank" rel="noopener">
            <div class="os-video-card-thumb">
              <?php if ( $v['thumb'] ) : ?>
                <img src="<?php echo esc_url( $v['thumb'] ); ?>" alt="" loading="lazy">
              <?php else : ?>
                <div class="os-fallback"></div>
              <?php endif; ?>
              <div class="os-mini-play" style="width:40px;height:40px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
              </div>
              <?php if ( $v['duration'] ) : ?>
                <span class="os-video-dur"><?php echo esc_html( $v['duration'] ); ?></span>
              <?php endif; ?>
            </div>
            <div class="os-video-card-body">
              <div class="os-card-cat"><?php echo esc_html( $v['kicker'] ?: 'Vídeo' ); ?></div>
              <div class="os-card-title" style="font-size:15px;margin-top:6px;"><?php the_title(); ?></div>
            </div>
          </a>
        <?php endwhile; wp_reset_postdata(); ?>
      </div>
    </section>
  <?php endif; ?>

  <?php /* MATERIAIS + PODCAST em duas colunas */ ?>
  <?php if ( $q_materiais->have_posts() || $q_podcast->have_posts() ) : ?>
    <section class="os-wrap os-tag-split">

      <?php if ( $q_materiais->have_posts() ) : ?>
        <div>
          <div class="os-sec-head" style="padding-top:0;">
            <h2 class="os-sec-title">Materiais</h2>
            <a class="os-sec-link" href="<?php echo esc_url( home_url( '/materiais/' ) ); ?>">Ver todos →</a>
          </div>
          <div class="os-tag-materiais">
          <?php while ( $q_materiais->have_posts() ) : $q_materiais->the_post(); ?>
            <a class="os-tag-material" href="<?php the_permalink(); ?>">
              <div class="os-tag-material-thumb">
                <?php if ( has_post_thumbnail() ) the_post_thumbnail( 'medium' ); else echo '<div class="os-fallback"></div>'; ?>
              </div>
              <div>
                <div class="os-card-cat" style="font-size:10px;">Material</div>
                <div class="os-card-title" style="font-size:14px;margin-top:4px;"><?php the_title(); ?></div>
              </div>
            </a>
          <?php endwhile; wp_reset_postdata(); ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if ( $q_podcast->have_posts() ) : ?>
        <div>
          <div class="os-sec-head" style="padding-top:0;">
            <h2 class="os-sec-title">Podcast</h2>
            <a class="os-sec-link" href="<?php echo esc_url( home_url( '/categoria/podcast/' ) ); ?>">Todos episódios →</a>
          </div>
          <div class="os-podcast-list">
          <?php $i = 1; while ( $q_podcast->have_posts() ) : $q_podcast->the_post(); ?>
            <a class="os-podcast-item" href="<?php the_permalink(); ?>">
              <span class="os-podcast-idx">#<?php echo str_pad( $i, 3, '0', STR_PAD_LEFT ); $i++; ?></span>
              <div>
                <div class="os-podcast-item-title"><?php the_title(); ?></div>
                <div style="font-family:'JetBrains Mono',monospace;font-size:10.5px;color:var(--text-mute);margin-top:2px;">Spotify · Apple · YouTube</div>
              </div>
              <span style="font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--text-dim);">▶</span>
            </a>
          <?php endwhile; wp_reset_postdata(); ?>
          </div>
        </div>
      <?php endif; ?>

    </section>
  <?php endif; ?>

  <?php /* EVENTOS */ ?>
  <?php if ( $q_eventos->have_posts() ) : ?>
    <section class="os-wrap os-tag-section">
      <div class="os-sec-head">
        <h2 class="os-sec-title">Eventos relacionados</h2>
        <a class="os-sec-link" href="<?php echo esc_url( home_url( '/categoria/eventos/' ) ); ?>">Agenda →</a>
      </div>
      <div class="os-tag-eventos">
        <?php while ( $q_eventos->have_posts() ) : $q_eventos->the_post(); ?>
          <a class="os-tag-evento" href="<?php the_permalink(); ?>">
            <div class="os-tag-evento-date">
              <?php echo esc_html( get_the_date( 'd M' ) ); ?>
            </div>
            <div class="os-card-title" style="font-size:16px;"><?php the_title(); ?></div>
            <div style="font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--text-mute);margin-top:6px;">
              <?php echo esc_html( wp_trim_words( get_the_excerpt(), 12, '…' ) ); ?>
            </div>
          </a>
        <?php endwhile; wp_reset_postdata(); ?>
      </div>
    </section>
  <?php endif; ?>

  <?php if ( $total_count === 0 ) : ?>
    <section class="os-wrap" style="padding:80px 0;text-align:center;">
      <p style="color:var(--text-mute);">Nenhum conteúdo publicado com a tag <strong><?php echo esc_html( $tag_name ); ?></strong> ainda.</p>
    </section>
  <?php endif; ?>

</main>

<?php get_footer(); ?>

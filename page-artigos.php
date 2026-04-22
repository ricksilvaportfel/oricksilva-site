<?php
/**
 * Template Name: Artigos (Listagem)
 * Template Post Type: page
 *
 * Use este template atribuindo-o à página "Artigos" pelo editor do WordPress:
 * Páginas → Artigos → Atributos da Página → Modelo → "Artigos (Listagem)".
 *
 * Força o layout editorial independente do que o Elementor ou o tema-pai fizerem.
 */
if ( ! defined( 'ABSPATH' ) ) exit;

// Roda uma query manual dos posts mais recentes (não dependemos do loop da página).
$paged = max( 1, get_query_var( 'paged' ) ? get_query_var( 'paged' ) : ( get_query_var( 'page' ) ?: 1 ) );
$os_query = new WP_Query( [
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'posts_per_page' => 12,
    'paged'          => $paged,
    'ignore_sticky_posts' => true,
] );

get_header(); ?>

<main class="os-archive">

  <header class="os-archive-head os-wrap">
    <span class="os-archive-kicker">ARTIGOS</span>
    <h1 class="os-archive-title">Todos os <em>artigos</em></h1>
  </header>

  <?php
  $cats = get_categories( [ 'hide_empty' => true, 'number' => 12, 'orderby' => 'count', 'order' => 'DESC' ] );
  if ( $cats ) : ?>
    <nav class="os-archive-filters os-wrap" aria-label="Filtrar por categoria">
      <a href="<?php echo esc_url( home_url( '/artigos/' ) ); ?>" class="os-filter-chip is-active">Todos</a>
      <?php foreach ( $cats as $c ) : ?>
        <a href="<?php echo esc_url( get_category_link( $c->term_id ) ); ?>" class="os-filter-chip">
          <?php echo esc_html( $c->name ); ?>
        </a>
      <?php endforeach; ?>
    </nav>
  <?php endif; ?>

  <?php if ( $os_query->have_posts() ) : ?>

    <section class="os-archive-top os-wrap">
      <?php
      $n = 0;
      while ( $os_query->have_posts() && $n < 3 ) : $os_query->the_post(); $n++;
          $cats_p = get_the_category();
          $cat_p  = $cats_p ? $cats_p[0] : null;
          $is_hero = ( $n === 1 ); ?>
          <a class="os-top-card <?php echo $is_hero ? 'is-hero' : 'is-side'; ?>" href="<?php the_permalink(); ?>">
            <?php if ( has_post_thumbnail() ) : ?>
              <div class="os-top-img"><?php the_post_thumbnail( $is_hero ? 'large' : 'medium_large' ); ?></div>
            <?php else : ?>
              <div class="os-top-img os-img-placeholder"></div>
            <?php endif; ?>
            <div class="os-top-body">
              <?php if ( $cat_p ) : ?>
                <span class="os-top-cat"><?php echo esc_html( mb_strtoupper( $cat_p->name ) ); ?></span>
              <?php endif; ?>
              <h2 class="os-top-title"><?php the_title(); ?></h2>
              <?php if ( $is_hero && has_excerpt() ) : ?>
                <p class="os-top-sub"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 28 ) ); ?></p>
              <?php endif; ?>
              <div class="os-top-meta">
                <span><?php the_author(); ?></span>
                <span class="os-meta-sep">·</span>
                <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date( 'd \d\e F' ) ); ?></time>
              </div>
            </div>
          </a>
      <?php endwhile; ?>
    </section>

    <section class="os-archive-grid-wrap os-wrap">
      <h2 class="os-archive-subhead">Mais recentes</h2>
      <div class="os-archive-grid" id="os-archive-grid">
        <?php while ( $os_query->have_posts() ) : $os_query->the_post();
            $cats_p = get_the_category();
            $cat_p  = $cats_p ? $cats_p[0] : null; ?>
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
        <?php endwhile; ?>
      </div>

      <?php
      $max_pages = $os_query->max_num_pages;
      if ( $max_pages > 1 ) :
          $query_string = wp_json_encode( [ 'cat' => '', 'tag_id' => '', 'author' => '', 's' => '' ] );
          ?>
          <div class="os-archive-more">
            <button type="button" id="os-load-more"
                    data-page="1"
                    data-max="<?php echo esc_attr( $max_pages ); ?>"
                    data-query='<?php echo esc_attr( $query_string ); ?>'
                    class="os-btn-ghost">
              Carregar mais
            </button>
          </div>
      <?php endif; ?>
    </section>

    <?php wp_reset_postdata(); ?>

  <?php else : ?>
    <section class="os-wrap os-archive-empty">
      <h2 class="font-serif">Nada por aqui ainda.</h2>
      <p>Publique seu primeiro artigo em <strong>Posts → Adicionar novo</strong>.</p>
    </section>
  <?php endif; ?>

</main>

<?php get_footer(); ?>

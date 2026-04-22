<?php
/**
 * Template: Archive universal
 *
 * Cobre: /artigos/, /category/*, /tag/*, /author/*, /yyyy/mm/, etc.
 * Layout híbrido: 1 destaque grande + 2 médios no topo, depois grid minimalista.
 * Filtros horizontais no topo (categorias do blog). Carregar mais via AJAX.
 */
if ( ! defined( 'ABSPATH' ) ) exit;
get_header(); ?>

<main class="os-archive">

  <!-- Cabeçalho do arquivo -->
  <header class="os-archive-head os-wrap">
    <span class="os-archive-kicker">
      <?php
      if ( is_category() )    echo 'CATEGORIA';
      elseif ( is_tag() )      echo 'TAG';
      elseif ( is_author() )   echo 'COLUNISTA';
      elseif ( is_date() )     echo 'ARQUIVO';
      elseif ( is_search() )   echo 'BUSCA';
      else                     echo 'ARTIGOS';
      ?>
    </span>
    <h1 class="os-archive-title">
      <?php
      if ( is_category() || is_tag() ) {
          single_term_title();
      } elseif ( is_author() ) {
          echo esc_html( get_the_author() );
      } elseif ( is_date() ) {
          echo esc_html( single_month_title( ' ', false ) );
      } elseif ( is_search() ) {
          echo 'Busca: <em>' . esc_html( get_search_query() ) . '</em>';
      } else {
          echo 'Todos os <em>artigos</em>';
      }
      ?>
    </h1>
    <?php
    $desc = is_category() || is_tag() ? term_description() : '';
    if ( $desc ) : ?>
      <div class="os-archive-desc"><?php echo wp_kses_post( $desc ); ?></div>
    <?php elseif ( is_author() ) :
      $author_bio = get_the_author_meta( 'description' );
      if ( $author_bio ) : ?>
        <div class="os-archive-desc"><?php echo esc_html( $author_bio ); ?></div>
    <?php endif; endif; ?>
  </header>

  <!-- Filtros de categoria (só em /artigos/ e category) -->
  <?php if ( ! is_search() && ! is_author() && ! is_date() && ! is_tag() ) :
      $cats = get_categories( [ 'hide_empty' => true, 'number' => 12, 'orderby' => 'count', 'order' => 'DESC' ] );
      $current_cat_id = is_category() ? get_queried_object_id() : 0;
      if ( $cats ) : ?>
        <nav class="os-archive-filters os-wrap" aria-label="Filtrar por categoria">
          <a href="<?php echo esc_url( home_url( '/artigos/' ) ); ?>" class="os-filter-chip <?php echo ! $current_cat_id ? 'is-active' : ''; ?>">Todos</a>
          <?php foreach ( $cats as $c ) : ?>
            <a href="<?php echo esc_url( get_category_link( $c->term_id ) ); ?>" class="os-filter-chip <?php echo $c->term_id === $current_cat_id ? 'is-active' : ''; ?>">
              <?php echo esc_html( $c->name ); ?>
            </a>
          <?php endforeach; ?>
        </nav>
  <?php endif; endif; ?>

  <?php if ( have_posts() ) : ?>

    <?php /* ---------- TOPO: 1 destaque + 2 médios (jornal) ---------- */
    $featured = []; $i = 0;
    while ( have_posts() && $i < 3 ) : the_post();
        $featured[] = get_the_ID();
        $i++;
    endwhile;
    rewind_posts();

    if ( ! empty( $featured ) ) : ?>
      <section class="os-archive-top os-wrap">
        <?php
        $n = 0;
        while ( have_posts() && $n < 3 ) : the_post(); $n++;
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
    <?php endif; ?>

    <?php /* ---------- GRID MINIMALISTA 4 COLUNAS ---------- */ ?>
    <section class="os-archive-grid-wrap os-wrap">
      <h2 class="os-archive-subhead">Mais recentes</h2>
      <div class="os-archive-grid" id="os-archive-grid">
        <?php while ( have_posts() ) : the_post();
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

      <?php /* Carregar mais (via AJAX) */
      global $wp_query;
      $max_pages = $wp_query->max_num_pages;
      if ( $max_pages > 1 ) :
          $current_page = max( 1, get_query_var( 'paged' ) );
          $query_string = json_encode( [
              'cat'    => is_category() ? get_queried_object_id() : '',
              'tag_id' => is_tag() ? get_queried_object_id() : '',
              'author' => is_author() ? get_queried_object_id() : '',
              's'      => is_search() ? get_search_query() : '',
          ] );
          ?>
          <div class="os-archive-more">
            <button type="button" id="os-load-more"
                    data-page="<?php echo esc_attr( $current_page ); ?>"
                    data-max="<?php echo esc_attr( $max_pages ); ?>"
                    data-query='<?php echo esc_attr( $query_string ); ?>'
                    class="os-btn-ghost">
              Carregar mais
            </button>
          </div>
      <?php endif; ?>
    </section>

  <?php else : ?>
    <section class="os-wrap os-archive-empty">
      <h2 class="font-serif">Nada por aqui ainda.</h2>
      <p>Tente outra categoria ou volte <a href="<?php echo esc_url( home_url( '/' ) ); ?>">à home</a>.</p>
    </section>
  <?php endif; ?>

</main>

<?php get_footer(); ?>

<?php
/**
 * Template: Post individual (artigo)
 * Visual: estilo editorial InfoMoney adaptado ao sistema O Rick Silva.
 */
get_header(); ?>

<?php while ( have_posts() ) : the_post();

    // ---- meta do post
    $post_id     = get_the_ID();
    $author_id   = get_the_author_meta( 'ID' );
    $author_name = get_the_author();
    $author_url  = get_author_posts_url( $author_id );
    $author_bio  = get_the_author_meta( 'description' );
    $author_img  = get_avatar_url( $author_id, [ 'size' => 96 ] );

    $categories  = get_the_category();
    $primary_cat = ! empty( $categories ) ? $categories[0] : null;

    $tags        = get_the_tags() ?: [];

    // tempo de leitura (180 palavras/min)
    $word_count  = str_word_count( wp_strip_all_tags( get_the_content() ) );
    $read_time   = max( 1, ceil( $word_count / 180 ) );

    // subtítulo: excerpt manual do post (se o autor preencheu) ou 1º parágrafo
    $subtitle = has_excerpt() ? get_the_excerpt() : '';
?>

<article class="os-article" data-post-id="<?php echo esc_attr( $post_id ); ?>">

  <!-- Breadcrumb -->
  <nav class="os-breadcrumb os-wrap" aria-label="Você está aqui">
    <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Início</a>
    <span class="os-bc-sep">›</span>
    <?php if ( $primary_cat ) : ?>
      <a href="<?php echo esc_url( get_category_link( $primary_cat->term_id ) ); ?>"><?php echo esc_html( $primary_cat->name ); ?></a>
      <span class="os-bc-sep">›</span>
    <?php endif; ?>
    <span class="os-bc-current"><?php the_title(); ?></span>
  </nav>

  <!-- Header editorial -->
  <header class="os-article-head os-wrap">
    <?php if ( $primary_cat ) : ?>
      <a href="<?php echo esc_url( get_category_link( $primary_cat->term_id ) ); ?>" class="os-article-cat">
        <?php echo esc_html( mb_strtoupper( $primary_cat->name ) ); ?>
      </a>
    <?php endif; ?>

    <h1 class="os-article-title"><?php the_title(); ?></h1>

    <?php if ( $subtitle ) : ?>
      <p class="os-article-subtitle"><?php echo esc_html( $subtitle ); ?></p>
    <?php endif; ?>

    <div class="os-article-meta">
      <a href="<?php echo esc_url( $author_url ); ?>" class="os-article-author">
        <?php if ( $author_img ) : ?>
          <img src="<?php echo esc_url( $author_img ); ?>" alt="" class="os-author-avatar" width="36" height="36" />
        <?php endif; ?>
        <span class="os-author-name"><?php echo esc_html( $author_name ); ?></span>
      </a>
      <span class="os-article-meta-sep">·</span>
      <time class="os-article-date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>">
        <?php echo esc_html( get_the_date( 'd \d\e F \d\e Y' ) ); ?> · <?php echo esc_html( get_the_time( 'H:i' ) ); ?>
      </time>
      <span class="os-article-meta-sep">·</span>
      <span class="os-article-read"><?php echo esc_html( $read_time ); ?> min de leitura</span>

      <!-- Share (topo) -->
      <?php if ( function_exists( 'os_render_share_buttons' ) ) : ?>
        <div class="os-article-share">
          <?php echo os_render_share_buttons(); ?>
        </div>
      <?php endif; ?>
    </div>
  </header>

  <!-- Imagem de destaque com legenda -->
  <?php if ( has_post_thumbnail() ) : ?>
    <figure class="os-article-hero os-wrap">
      <?php the_post_thumbnail( 'large', [ 'class' => 'os-hero-img' ] ); ?>
      <?php
      $caption = get_the_post_thumbnail_caption();
      if ( $caption ) : ?>
        <figcaption class="os-hero-caption"><?php echo esc_html( $caption ); ?></figcaption>
      <?php endif; ?>
    </figure>
  <?php endif; ?>

  <!-- Corpo + Sidebar (layout com share lateral e sidebar sticky) -->
  <div class="os-article-layout os-wrap">

    <!-- Share vertical sticky (desktop) -->
    <?php if ( function_exists( 'os_render_share_buttons' ) ) : ?>
      <aside class="os-share-rail" aria-label="Compartilhar">
        <span class="os-share-rail-label">COMPARTILHAR</span>
        <?php echo os_render_share_buttons(); ?>
      </aside>
    <?php endif; ?>

    <!-- Corpo -->
    <div class="os-article-body">
      <?php the_content(); ?>

      <?php
      // Tags do post (rodapé do corpo)
      if ( ! empty( $tags ) ) : ?>
        <div class="os-article-tags">
          <span class="os-tags-label">Tags:</span>
          <?php foreach ( $tags as $tag ) : ?>
            <a href="<?php echo esc_url( get_tag_link( $tag->term_id ) ); ?>" class="os-tag-chip"><?php echo esc_html( $tag->name ); ?></a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Sidebar sticky -->
    <aside class="os-article-sidebar">

      <?php /* Mais lidos (últimos 30 dias, 5 posts) */
      $popular = new WP_Query( [
        'post_type'      => 'post',
        'posts_per_page' => 5,
        'post__not_in'   => [ $post_id ],
        'date_query'     => [ [ 'after' => '30 days ago' ] ],
        'orderby'        => 'comment_count',
        'order'          => 'DESC',
      ] );
      if ( ! $popular->have_posts() ) {
        // fallback: posts recentes
        $popular = new WP_Query( [
          'post_type'      => 'post',
          'posts_per_page' => 5,
          'post__not_in'   => [ $post_id ],
          'orderby'        => 'date',
          'order'          => 'DESC',
        ] );
      }
      if ( $popular->have_posts() ) : ?>
        <div class="os-sidebar-block">
          <h3 class="os-sidebar-title">Mais lidos</h3>
          <ol class="os-sidebar-list">
            <?php $n = 0; while ( $popular->have_posts() ) : $popular->the_post(); $n++; ?>
              <li>
                <span class="os-sidebar-num"><?php echo str_pad( $n, 2, '0', STR_PAD_LEFT ); ?></span>
                <a href="<?php the_permalink(); ?>" class="os-sidebar-link"><?php the_title(); ?></a>
              </li>
            <?php endwhile; wp_reset_postdata(); ?>
          </ol>
        </div>
      <?php endif; ?>

      <?php /* Newsletter embutida (editável em Aparência → Personalizar → Newsletter sidebar) */ ?>
      <div class="os-sidebar-block os-sidebar-news">
        <span class="os-sidebar-kicker"><?php echo esc_html( get_theme_mod( 'os_news_kicker', 'NEWSLETTER' ) ); ?></span>
        <h3 class="os-sidebar-news-title"><?php echo esc_html( get_theme_mod( 'os_news_title', 'A pauta financeira na sua caixa, 3×/semana.' ) ); ?></h3>
        <p class="os-sidebar-news-sub"><?php echo esc_html( get_theme_mod( 'os_news_sub', 'Análise sem ruído e 0 promessas.' ) ); ?></p>
        <a href="<?php echo esc_url( get_theme_mod( 'os_news_btn_url', '#newsletter' ) ); ?>" class="os-btn os-sidebar-news-btn"><?php echo esc_html( get_theme_mod( 'os_news_btn_text', 'Assinar grátis' ) ); ?></a>
      </div>

    </aside>

  </div>

  <!-- Bio do autor -->
  <?php if ( $author_bio ) : ?>
    <aside class="os-article-author-box os-wrap">
      <?php if ( $author_img ) : ?>
        <img src="<?php echo esc_url( get_avatar_url( $author_id, [ 'size' => 160 ] ) ); ?>" alt="" class="os-author-box-avatar" width="80" height="80" />
      <?php endif; ?>
      <div class="os-author-box-content">
        <span class="os-author-box-kicker">SOBRE O AUTOR</span>
        <a href="<?php echo esc_url( $author_url ); ?>" class="os-author-box-name"><?php echo esc_html( $author_name ); ?></a>
        <p class="os-author-box-bio"><?php echo esc_html( $author_bio ); ?></p>
        <a href="<?php echo esc_url( $author_url ); ?>" class="os-author-box-link">Ver todos os artigos →</a>
      </div>
    </aside>
  <?php endif; ?>

  <!-- Posts relacionados (mesma categoria) -->
  <?php if ( $primary_cat ) :
    $related = new WP_Query( [
      'post_type'      => 'post',
      'posts_per_page' => 4,
      'post__not_in'   => [ $post_id ],
      'cat'            => $primary_cat->term_id,
      'orderby'        => 'date',
      'order'          => 'DESC',
    ] );
    if ( $related->have_posts() ) : ?>
      <section class="os-article-related os-wrap">
        <div class="os-sec-head" style="padding-top:0;">
          <h2 class="os-sec-title">Leia também</h2>
          <a href="<?php echo esc_url( get_category_link( $primary_cat->term_id ) ); ?>" class="os-sec-link">Mais de <?php echo esc_html( $primary_cat->name ); ?> →</a>
        </div>
        <div class="os-related-grid">
          <?php while ( $related->have_posts() ) : $related->the_post(); ?>
            <a class="os-related-card" href="<?php the_permalink(); ?>">
              <?php if ( has_post_thumbnail() ) : ?>
                <div class="os-related-img"><?php the_post_thumbnail( 'medium' ); ?></div>
              <?php else : ?>
                <div class="os-related-img os-related-img-placeholder"></div>
              <?php endif; ?>
              <span class="os-related-cat"><?php
                $rc = get_the_category();
                echo $rc ? esc_html( mb_strtoupper( $rc[0]->name ) ) : 'ARTIGO';
              ?></span>
              <h3 class="os-related-title"><?php the_title(); ?></h3>
              <span class="os-related-date"><?php echo esc_html( get_the_date( 'd/m' ) ); ?></span>
            </a>
          <?php endwhile; wp_reset_postdata(); ?>
        </div>
      </section>
    <?php endif;
  endif; ?>

</article>

<?php endwhile; ?>

<script>
// Copiar link do artigo
document.querySelectorAll('.os-share-btn[data-action="copy"]').forEach(function(btn) {
  btn.addEventListener('click', function() {
    navigator.clipboard.writeText(window.location.href).then(function() {
      var originalTitle = btn.title;
      btn.title = 'Copiado!';
      btn.classList.add('is-copied');
      setTimeout(function() {
        btn.title = originalTitle;
        btn.classList.remove('is-copied');
      }, 2000);
    });
  });
});
</script>

<?php get_footer(); ?>

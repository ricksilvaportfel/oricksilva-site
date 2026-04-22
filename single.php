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

      <!-- Share -->
      <div class="os-article-share">
        <button type="button" class="os-share-btn" data-action="copy" title="Copiar link" aria-label="Copiar link">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.72"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.72-1.72"/></svg>
        </button>
        <a href="https://wa.me/?text=<?php echo rawurlencode( get_the_title() . ' ' . get_permalink() ); ?>" target="_blank" rel="noopener" class="os-share-btn" title="Compartilhar no WhatsApp" aria-label="WhatsApp">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M17.47 14.38c-.3-.15-1.75-.87-2.02-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.08-.3-.15-1.25-.46-2.38-1.47-.88-.78-1.47-1.75-1.65-2.05-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.6-.92-2.2-.24-.58-.49-.5-.67-.51-.17-.01-.37-.01-.57-.01s-.52.07-.8.37c-.27.3-1.05 1.02-1.05 2.5s1.07 2.9 1.22 3.1c.15.2 2.1 3.2 5.08 4.49.71.3 1.26.49 1.69.62.71.22 1.36.19 1.87.12.57-.08 1.75-.71 2-1.4.25-.7.25-1.29.17-1.4-.07-.12-.27-.2-.57-.35zM12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.92 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2zm0 18.13c-1.48 0-2.93-.4-4.2-1.15l-.3-.18-3.12.82.83-3.04-.2-.31a8.22 8.22 0 0 1-1.26-4.36c0-4.54 3.7-8.24 8.25-8.24 2.2 0 4.27.86 5.83 2.42a8.18 8.18 0 0 1 2.42 5.83c0 4.54-3.7 8.24-8.25 8.24z"/></svg>
        </a>
        <a href="https://twitter.com/intent/tweet?text=<?php echo rawurlencode( get_the_title() ); ?>&url=<?php echo rawurlencode( get_permalink() ); ?>" target="_blank" rel="noopener" class="os-share-btn" title="Compartilhar no X" aria-label="X">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
        </a>
        <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo rawurlencode( get_permalink() ); ?>" target="_blank" rel="noopener" class="os-share-btn" title="Compartilhar no LinkedIn" aria-label="LinkedIn">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M20.45 20.45h-3.56v-5.57c0-1.33-.03-3.04-1.85-3.04-1.85 0-2.14 1.45-2.14 2.95v5.66H9.35V9h3.42v1.56h.05c.48-.91 1.65-1.87 3.4-1.87 3.64 0 4.31 2.4 4.31 5.52v6.24zM5.34 7.43a2.07 2.07 0 1 1 0-4.14 2.07 2.07 0 0 1 0 4.14zM7.12 20.45H3.56V9h3.56v11.45zM22.22 0H1.77C.79 0 0 .77 0 1.72v20.56C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.72V1.72C24 .77 23.2 0 22.22 0z"/></svg>
        </a>
      </div>
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
    <aside class="os-share-rail" aria-label="Compartilhar">
      <span class="os-share-rail-label">COMPARTILHAR</span>
      <button type="button" class="os-share-btn" data-action="copy" title="Copiar link" aria-label="Copiar link">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.72"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.72-1.72"/></svg>
      </button>
      <a href="https://wa.me/?text=<?php echo rawurlencode( get_the_title() . ' ' . get_permalink() ); ?>" target="_blank" rel="noopener" class="os-share-btn" title="WhatsApp" aria-label="WhatsApp">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="currentColor"><path d="M17.47 14.38c-.3-.15-1.75-.87-2.02-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.08-.3-.15-1.25-.46-2.38-1.47-.88-.78-1.47-1.75-1.65-2.05-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.02-.52-.08-.15-.67-1.6-.92-2.2-.24-.58-.49-.5-.67-.51-.17-.01-.37-.01-.57-.01s-.52.07-.8.37c-.27.3-1.05 1.02-1.05 2.5s1.07 2.9 1.22 3.1c.15.2 2.1 3.2 5.08 4.49.71.3 1.26.49 1.69.62.71.22 1.36.19 1.87.12.57-.08 1.75-.71 2-1.4.25-.7.25-1.29.17-1.4-.07-.12-.27-.2-.57-.35zM12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38c1.45.79 3.08 1.21 4.74 1.21 5.46 0 9.91-4.45 9.91-9.92 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2z"/></svg>
      </a>
      <a href="https://twitter.com/intent/tweet?text=<?php echo rawurlencode( get_the_title() ); ?>&url=<?php echo rawurlencode( get_permalink() ); ?>" target="_blank" rel="noopener" class="os-share-btn" title="X" aria-label="X">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
      </a>
      <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo rawurlencode( get_permalink() ); ?>" target="_blank" rel="noopener" class="os-share-btn" title="LinkedIn" aria-label="LinkedIn">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M20.45 20.45h-3.56v-5.57c0-1.33-.03-3.04-1.85-3.04-1.85 0-2.14 1.45-2.14 2.95v5.66H9.35V9h3.42v1.56h.05c.48-.91 1.65-1.87 3.4-1.87 3.64 0 4.31 2.4 4.31 5.52v6.24zM5.34 7.43a2.07 2.07 0 1 1 0-4.14 2.07 2.07 0 0 1 0 4.14zM7.12 20.45H3.56V9h3.56v11.45zM22.22 0H1.77C.79 0 0 .77 0 1.72v20.56C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.72V1.72C24 .77 23.2 0 22.22 0z"/></svg>
      </a>
    </aside>

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

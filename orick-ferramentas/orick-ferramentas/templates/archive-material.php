<?php
/**
 * Archive: Materiais — /materiais/
 * Usa o padrão .os-archive-* + .os-card-* do tema (editorial, consistente com /artigos/, /videos/).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();

$tipos   = get_terms( [ 'taxonomy' => 'material_tipo', 'hide_empty' => true ] );
$current = is_tax( 'material_tipo' ) ? get_queried_object()->slug : '';
?>

<main class="os-archive">
  <header class="os-archive-head">
    <div class="os-wrap">
      <span class="os-archive-kicker">Materiais</span>
      <h1 class="os-archive-title">E-books, planilhas <em>e templates</em></h1>
      <p class="os-archive-desc">Conteúdo denso pra você aplicar na prática. Baixe gratuitamente — alguns pedem cadastro, outros são abertos.</p>
    </div>
  </header>

  <?php if ( $tipos && ! is_wp_error( $tipos ) ) : ?>
    <nav class="os-archive-filters">
      <div class="os-wrap">
        <a href="<?php echo esc_url( get_post_type_archive_link( 'material' ) ); ?>" class="os-filter-chip <?php echo ! $current ? 'is-active' : ''; ?>">Todos</a>
        <?php foreach ( $tipos as $t ) : ?>
          <a href="<?php echo esc_url( get_term_link( $t ) ); ?>" class="os-filter-chip <?php echo $current === $t->slug ? 'is-active' : ''; ?>"><?php echo esc_html( $t->name ); ?></a>
        <?php endforeach; ?>
      </div>
    </nav>
  <?php endif; ?>

  <div class="os-archive-grid-wrap">
    <div class="os-wrap">
      <?php if ( have_posts() ) : ?>
        <div class="os-archive-grid">
          <?php while ( have_posts() ) : the_post();
            $tipo_terms = get_the_terms( get_the_ID(), 'material_tipo' );
            $tipo       = $tipo_terms ? $tipo_terms[0]->name : 'Material';
            $requer     = get_post_meta( get_the_ID(), '_orick_requer_cadastro', true ) === '1';
            $paginas    = (int) get_post_meta( get_the_ID(), '_orick_paginas', true );
            $link       = $requer
                ? home_url( '/baixar/' . get_post_field( 'post_name' ) . '/' )
                : ( Orick_Ferr_CPT_Material::download_url( get_the_ID() ) ?: get_permalink() );
            $is_direct  = ! $requer && Orick_Ferr_CPT_Material::download_url( get_the_ID() );
            $meta_line  = [];
            if ( $paginas ) $meta_line[] = $paginas . ' páginas';
            $meta_line[] = $requer ? 'Cadastro necessário' : 'Acesso direto';
          ?>
            <article class="os-card">
              <a class="os-card-link" href="<?php echo esc_url( $link ); ?>" <?php echo $is_direct ? 'target="_blank" rel="noopener"' : ''; ?>>
                <div class="os-card-img <?php echo has_post_thumbnail() ? '' : 'os-img-placeholder'; ?>">
                  <?php if ( has_post_thumbnail() ) the_post_thumbnail( 'medium_large', [ 'loading' => 'lazy' ] ); ?>
                </div>
                <div class="os-card-body">
                  <div class="os-card-cat"><?php echo esc_html( $tipo ); ?></div>
                  <h2 class="os-card-title"><?php the_title(); ?></h2>
                  <?php if ( has_excerpt() ) : ?>
                    <p class="os-card-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18, '…' ) ); ?></p>
                  <?php endif; ?>
                  <div class="os-card-meta"><?php echo esc_html( implode( ' · ', $meta_line ) ); ?></div>
                </div>
              </a>
            </article>
          <?php endwhile; ?>
        </div>

        <?php
        $pager = paginate_links( [
          'prev_text' => '« Anteriores',
          'next_text' => 'Próximos »',
          'mid_size'  => 1,
          'type'      => 'array',
        ] );
        if ( $pager ) echo '<nav class="os-archive-pager">' . implode( '', $pager ) . '</nav>';
        ?>

      <?php else : ?>
        <div class="os-archive-empty">
          <h2>Nenhum material disponível</h2>
          <p>Em breve, novos e-books, planilhas e templates.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<?php get_footer();

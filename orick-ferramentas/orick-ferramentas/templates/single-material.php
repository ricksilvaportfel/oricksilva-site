<?php
/**
 * Single: Material — exibe capa + descrição + botão de download
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();

while ( have_posts() ) : the_post();
    $tipo_terms = get_the_terms( get_the_ID(), 'material_tipo' );
    $tipo = $tipo_terms ? $tipo_terms[0]->name : 'Material';
    $requer = get_post_meta( get_the_ID(), '_orick_requer_cadastro', true ) === '1';
    $paginas = get_post_meta( get_the_ID(), '_orick_paginas', true );
    $link = $requer ? home_url( '/baixar/' . get_post_field( 'post_name' ) . '/' ) : ( Orick_Ferr_CPT_Material::download_url( get_the_ID() ) ?: '#' );
    ?>
    <main class="ofr-main">
      <article class="ofr-single">
        <header class="ofr-single-head">
          <div class="ofr-wrap">
            <nav class="ofr-breadcrumb">
              <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Início</a>
              <span>›</span>
              <a href="<?php echo esc_url( get_post_type_archive_link( 'material' ) ); ?>">Materiais</a>
              <span>›</span>
              <span><?php the_title(); ?></span>
            </nav>
            <span class="ofr-kicker">MATERIAL · <?php echo esc_html( strtoupper( $tipo ) ); ?><?php echo $paginas ? ' · ' . intval( $paginas ) . ' PÁGINAS' : ''; ?></span>
            <h1 class="ofr-single-title"><?php the_title(); ?></h1>
            <?php if ( get_the_excerpt() ) : ?>
              <p class="ofr-single-lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
            <?php endif; ?>
          </div>
        </header>
        <section class="ofr-single-body">
          <div class="ofr-wrap">
            <?php if ( has_post_thumbnail() ) : ?>
              <div class="ofr-material-cover" style="margin-bottom:32px;max-width:420px;"><?php the_post_thumbnail( 'large' ); ?></div>
            <?php endif; ?>
            <?php the_content(); ?>
            <div class="ofr-cta-externo" style="margin-top:32px;">
              <a href="<?php echo esc_url( $link ); ?>" class="ofr-btn ofr-btn-primary ofr-btn-lg" <?php echo ! $requer ? 'target="_blank" rel="noopener"' : ''; ?>>Baixar material ↓</a>
              <?php if ( $requer ) : ?><p class="ofr-cta-externo-hint">Cadastro gratuito necessário.</p><?php endif; ?>
            </div>
          </div>
        </section>
      </article>
    </main>
<?php endwhile;
get_footer();

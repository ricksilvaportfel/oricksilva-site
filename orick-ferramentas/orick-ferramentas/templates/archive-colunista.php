<?php
/**
 * Archive: Colunistas — /colunistas/
 *
 * Agrega:
 *   1. CPT 'colunista' (novo fluxo, gerenciável pelo admin)
 *   2. WP_Users com orick_is_colunista=1 (fluxo legado, ainda suportado)
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();

// 1) CPT colunista (novos)
$cpt_q = new WP_Query( [
    'post_type'      => 'colunista',
    'post_status'    => 'publish',
    'posts_per_page' => -1,
    'orderby'        => [ 'menu_order' => 'DESC', 'date' => 'DESC' ],
] );

// 2) Users legados
$legacy_users = class_exists( 'Orick_Ferr_Colunista' ) ? Orick_Ferr_Colunista::get_all() : [];

$total = $cpt_q->found_posts + count( $legacy_users );
?>

<main class="os-archive os-archive--colunistas">
  <header class="os-archive-head">
    <div class="os-wrap">
      <span class="os-archive-kicker">Colunistas</span>
      <h1 class="os-archive-title">Quem <em>escreve aqui</em></h1>
      <p class="os-archive-desc">Vozes selecionadas do mercado financeiro brasileiro — assessores, planejadores, gestores e pesquisadores.</p>
    </div>
  </header>

  <div class="os-archive-grid-wrap">
    <div class="os-wrap">
      <?php if ( ! $total ) : ?>
        <div class="os-archive-empty">
          <h2>Nenhum colunista publicado</h2>
          <p>Cadastre em <strong>Colunistas → Adicionar novo</strong> no menu do WordPress.</p>
        </div>
      <?php else : ?>
        <div class="os-archive-grid">

          <?php // Renderiza CPTs primeiro
          if ( $cpt_q->have_posts() ) : while ( $cpt_q->have_posts() ) : $cpt_q->the_post();
            $d = orick_colunista_data( get_the_ID(), 'cpt' );
            $bits = [];
            if ( $d['cargo'] )         $bits[] = $d['cargo'];
            if ( $d['periodicidade'] ) $bits[] = ucfirst( $d['periodicidade'] );
          ?>
            <article class="os-card">
              <a class="os-card-link" href="<?php echo esc_url( $d['link'] ); ?>">
                <div class="os-card-img" style="aspect-ratio:1/1;">
                  <?php if ( $d['avatar'] ) : ?>
                    <img src="<?php echo esc_url( $d['avatar'] ); ?>" alt="<?php echo esc_attr( $d['name'] ); ?>" loading="lazy">
                  <?php else : ?>
                    <div class="os-img-placeholder"></div>
                  <?php endif; ?>
                </div>
                <div class="os-card-body">
                  <div class="os-card-cat">Colunista</div>
                  <h2 class="os-card-title"><?php echo esc_html( $d['name'] ); ?></h2>
                  <?php if ( has_excerpt( get_the_ID() ) ) : ?>
                    <p class="os-card-excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22, '…' ) ); ?></p>
                  <?php endif; ?>
                  <?php if ( $bits ) : ?>
                    <div class="os-card-meta"><?php echo esc_html( implode( ' · ', $bits ) ); ?></div>
                  <?php endif; ?>
                </div>
              </a>
            </article>
          <?php endwhile; wp_reset_postdata(); endif; ?>

          <?php // Depois renderiza Users legados (compatibilidade)
          foreach ( $legacy_users as $u ) :
            $d = orick_colunista_data( $u->ID, 'user' );
            $bits = [];
            if ( $d['cargo'] )         $bits[] = $d['cargo'];
            if ( $d['periodicidade'] ) $bits[] = ucfirst( $d['periodicidade'] );
            $link = $d['tag_unica'] ? get_tag_link( get_term_by( 'slug', $d['tag_unica'], 'post_tag' ) ) : $d['link'];
          ?>
            <article class="os-card">
              <a class="os-card-link" href="<?php echo esc_url( $link ?: '#' ); ?>">
                <div class="os-card-img" style="aspect-ratio:1/1;">
                  <img src="<?php echo esc_url( $d['avatar'] ); ?>" alt="<?php echo esc_attr( $d['name'] ); ?>" loading="lazy">
                </div>
                <div class="os-card-body">
                  <div class="os-card-cat">Colunista</div>
                  <h2 class="os-card-title"><?php echo esc_html( $d['name'] ); ?></h2>
                  <?php if ( $d['bio'] ) : ?>
                    <p class="os-card-excerpt"><?php echo esc_html( wp_trim_words( $d['bio'], 22, '…' ) ); ?></p>
                  <?php endif; ?>
                  <?php if ( $bits ) : ?>
                    <div class="os-card-meta"><?php echo esc_html( implode( ' · ', $bits ) ); ?></div>
                  <?php endif; ?>
                </div>
              </a>
            </article>
          <?php endforeach; ?>

        </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<?php get_footer();

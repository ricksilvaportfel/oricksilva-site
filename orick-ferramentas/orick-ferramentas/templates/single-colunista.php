<?php
/**
 * Single: Colunista — /colunistas/{slug}/
 * Perfil editorial com foto, cargo, bio, redes sociais e os artigos
 * dele(a) (filtrados pela tag única, se configurada).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();

while ( have_posts() ) : the_post();
    $d = orick_colunista_data( get_the_ID(), 'cpt' );

    // Lista de artigos via tag única (se configurada).
    $posts_q = null;
    if ( $d['tag_unica'] ) {
        $posts_q = new WP_Query( [
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 12,
            'tag'            => $d['tag_unica'],
        ] );
    }

    $ig_url = orick_social_url( $d['instagram'], 'https://instagram.com/' );
    $tw_url = orick_social_url( $d['twitter'],   'https://twitter.com/' );
    ?>

    <main class="os-colunista-single">
      <div class="os-wrap">

        <header class="os-colunista-head">
          <div class="os-colunista-photo">
            <?php if ( $d['avatar'] ) : ?>
              <img src="<?php echo esc_url( $d['avatar'] ); ?>" alt="<?php echo esc_attr( $d['name'] ); ?>">
            <?php else : ?>
              <div class="os-img-placeholder" style="aspect-ratio:1/1;"></div>
            <?php endif; ?>
          </div>
          <div class="os-colunista-info">
            <span class="os-archive-kicker">Colunista</span>
            <h1 class="os-colunista-name"><?php echo esc_html( $d['name'] ); ?></h1>
            <?php if ( $d['cargo'] ) : ?>
              <div class="os-colunista-cargo"><?php echo esc_html( $d['cargo'] ); ?>
                <?php if ( $d['periodicidade'] ) : ?>
                  <span class="os-colunista-period"> · <?php echo esc_html( ucfirst( $d['periodicidade'] ) ); ?></span>
                <?php endif; ?>
              </div>
            <?php endif; ?>
            <?php if ( $d['bio'] ) : ?>
              <div class="os-colunista-bio"><?php echo wp_kses_post( $d['bio'] ); ?></div>
            <?php endif; ?>
            <?php if ( $d['linkedin'] || $ig_url || $tw_url || $d['site'] ) : ?>
              <div class="os-colunista-socials">
                <?php if ( $d['linkedin'] ) : ?>
                  <a class="os-colunista-social" href="<?php echo esc_url( $d['linkedin'] ); ?>" target="_blank" rel="noopener">LinkedIn ↗</a>
                <?php endif; ?>
                <?php if ( $ig_url ) : ?>
                  <a class="os-colunista-social" href="<?php echo esc_url( $ig_url ); ?>" target="_blank" rel="noopener">Instagram ↗</a>
                <?php endif; ?>
                <?php if ( $tw_url ) : ?>
                  <a class="os-colunista-social" href="<?php echo esc_url( $tw_url ); ?>" target="_blank" rel="noopener">Twitter ↗</a>
                <?php endif; ?>
                <?php if ( $d['site'] ) : ?>
                  <a class="os-colunista-social" href="<?php echo esc_url( $d['site'] ); ?>" target="_blank" rel="noopener">Site ↗</a>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        </header>

        <?php if ( $posts_q && $posts_q->have_posts() ) : ?>
          <section class="os-colunista-posts">
            <h2 class="os-archive-subhead">Artigos de <?php echo esc_html( $d['name'] ); ?></h2>
            <div class="os-archive-grid">
              <?php while ( $posts_q->have_posts() ) : $posts_q->the_post(); ?>
                <article class="os-card">
                  <a class="os-card-link" href="<?php the_permalink(); ?>">
                    <div class="os-card-img <?php echo has_post_thumbnail() ? '' : 'os-img-placeholder'; ?>">
                      <?php if ( has_post_thumbnail() ) the_post_thumbnail( 'medium_large', [ 'loading' => 'lazy' ] ); ?>
                    </div>
                    <div class="os-card-body">
                      <?php $cats = get_the_category();
                      if ( $cats ) : ?>
                        <div class="os-card-cat"><?php echo esc_html( $cats[0]->name ); ?></div>
                      <?php endif; ?>
                      <h3 class="os-card-title"><?php the_title(); ?></h3>
                      <div class="os-card-meta"><?php echo esc_html( get_the_date( 'd M Y' ) ); ?></div>
                    </div>
                  </a>
                </article>
              <?php endwhile; wp_reset_postdata(); ?>
            </div>
          </section>
        <?php elseif ( $d['tag_unica'] ) : ?>
          <p style="color:var(--text-mute,rgba(228,216,199,0.55));font-size:14px;padding:32px 0;">
            Nenhum artigo publicado ainda com a tag <code><?php echo esc_html( $d['tag_unica'] ); ?></code>.
          </p>
        <?php endif; ?>

      </div>
    </main>
<?php endwhile;
get_footer();

<?php
/**
 * Single: Vídeo
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();

while ( have_posts() ) : the_post();
    $yt = get_post_meta( get_the_ID(), '_orick_youtube_id', true );
    $dur = get_post_meta( get_the_ID(), '_orick_duracao', true );
    ?>
    <main class="ofr-main">
      <article class="ofr-single">
        <header class="ofr-single-head">
          <div class="ofr-wrap">
            <nav class="ofr-breadcrumb">
              <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Início</a>
              <span>›</span>
              <a href="<?php echo esc_url( get_post_type_archive_link( 'video' ) ); ?>">Vídeos</a>
              <span>›</span>
              <span><?php the_title(); ?></span>
            </nav>
            <span class="ofr-kicker">VÍDEO · <?php echo esc_html( get_the_date( 'd/m/Y' ) ); ?><?php echo $dur ? ' · ' . esc_html( $dur ) : ''; ?></span>
            <h1 class="ofr-single-title"><?php the_title(); ?></h1>
          </div>
        </header>
        <section class="ofr-single-body">
          <div class="ofr-wrap">
            <?php if ( $yt ) : ?>
              <div style="position:relative;padding-top:56.25%;margin-bottom:32px;background:#000;">
                <iframe src="https://www.youtube.com/embed/<?php echo esc_attr( $yt ); ?>" style="position:absolute;inset:0;width:100%;height:100%;border:0;" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>
              </div>
            <?php endif; ?>
            <?php the_content(); ?>
          </div>
        </section>
      </article>
    </main>
<?php endwhile;
get_footer();

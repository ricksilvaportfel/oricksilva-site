<?php
/**
 * Single: Evento
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
get_header();

while ( have_posts() ) : the_post();
    $data = get_post_meta( get_the_ID(), '_orick_ev_data', true );
    $hora_ini = get_post_meta( get_the_ID(), '_orick_ev_hora_ini', true );
    $hora_fim = get_post_meta( get_the_ID(), '_orick_ev_hora_fim', true );
    $formato = get_post_meta( get_the_ID(), '_orick_ev_formato', true );
    $local = get_post_meta( get_the_ID(), '_orick_ev_local', true );
    $cidade = get_post_meta( get_the_ID(), '_orick_ev_cidade', true );
    $gratuito = get_post_meta( get_the_ID(), '_orick_ev_gratuito', true ) === '1';
    $preco = get_post_meta( get_the_ID(), '_orick_ev_preco', true );
    $link = get_post_meta( get_the_ID(), '_orick_ev_link_inscricao', true );
    $status = get_post_meta( get_the_ID(), '_orick_ev_status', true );

    $status_label = [
        'em_breve' => 'Inscrições em breve',
        'abertas' => 'Inscrições abertas',
        'ultimas' => 'Últimas vagas',
        'encerradas' => 'Inscrições encerradas',
        'finalizado' => 'Evento finalizado',
    ][ $status ] ?? '';
    $can_subscribe = in_array( $status, [ 'abertas', 'ultimas' ], true );
    ?>
    <main class="ofr-main">
      <article class="ofr-single">
        <header class="ofr-single-head">
          <div class="ofr-wrap">
            <nav class="ofr-breadcrumb">
              <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Início</a>
              <span>›</span>
              <a href="<?php echo esc_url( get_post_type_archive_link( 'evento' ) ); ?>">Eventos</a>
              <span>›</span>
              <span><?php the_title(); ?></span>
            </nav>
            <span class="ofr-kicker">EVENTO · <?php echo esc_html( $data ? date_i18n( 'd \d\e F \d\e Y', strtotime( $data ) ) : '' ); ?></span>
            <h1 class="ofr-single-title"><?php the_title(); ?></h1>
            <?php if ( get_the_excerpt() ) : ?><p class="ofr-single-lead"><?php echo esc_html( get_the_excerpt() ); ?></p><?php endif; ?>
          </div>
        </header>
        <section class="ofr-single-body">
          <div class="ofr-wrap">
            <?php if ( has_post_thumbnail() ) : ?>
              <div style="margin-bottom:32px;"><?php the_post_thumbnail( 'large' ); ?></div>
            <?php endif; ?>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:20px;margin-bottom:32px;padding:24px;border:1px solid var(--ofr-border);background:var(--ofr-bg-2);">
              <div>
                <div style="font-family:'JetBrains Mono',monospace;font-size:10.5px;letter-spacing:0.15em;text-transform:uppercase;color:var(--ofr-fg-dim);margin-bottom:6px;">Data e horário</div>
                <div style="font-family:'Fraunces',serif;font-size:18px;">
                  <?php echo esc_html( $data ? date_i18n( 'd/m/Y', strtotime( $data ) ) : '—' ); ?><br>
                  <?php if ( $hora_ini ) : ?><span style="font-size:14px;color:var(--ofr-fg-dim);"><?php echo esc_html( $hora_ini ); ?><?php echo $hora_fim ? ' – ' . esc_html( $hora_fim ) : ''; ?></span><?php endif; ?>
                </div>
              </div>
              <div>
                <div style="font-family:'JetBrains Mono',monospace;font-size:10.5px;letter-spacing:0.15em;text-transform:uppercase;color:var(--ofr-fg-dim);margin-bottom:6px;">Formato</div>
                <div style="font-family:'Fraunces',serif;font-size:18px;">
                  <?php echo esc_html( ucfirst( $formato ?: 'A definir' ) ); ?><br>
                  <?php if ( $local ) : ?><span style="font-size:14px;color:var(--ofr-fg-dim);"><?php echo esc_html( $local ); ?><?php echo $cidade ? ', ' . esc_html( $cidade ) : ''; ?></span><?php endif; ?>
                </div>
              </div>
              <div>
                <div style="font-family:'JetBrains Mono',monospace;font-size:10.5px;letter-spacing:0.15em;text-transform:uppercase;color:var(--ofr-fg-dim);margin-bottom:6px;">Investimento</div>
                <div style="font-family:'Fraunces',serif;font-size:18px;color:<?php echo $gratuito ? 'var(--ofr-success, #7A8B5C)' : 'var(--ofr-accent)'; ?>;">
                  <?php echo $gratuito ? 'Gratuito' : esc_html( $preco ?: 'Consultar' ); ?>
                </div>
              </div>
            </div>

            <?php the_content(); ?>

            <div class="ofr-cta-externo" style="margin-top:40px;">
              <?php if ( $can_subscribe && $link ) : ?>
                <a href="<?php echo esc_url( $link ); ?>" class="ofr-btn ofr-btn-primary ofr-btn-lg" target="_blank" rel="noopener">Quero me inscrever ↗</a>
              <?php else : ?>
                <span class="ofr-btn ofr-btn-lg" style="opacity:.6;cursor:default;"><?php echo esc_html( $status_label ); ?></span>
              <?php endif; ?>
            </div>
          </div>
        </section>
      </article>
    </main>
<?php endwhile;
get_footer();

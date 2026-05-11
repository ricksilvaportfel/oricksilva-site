<?php
/**
 * Single de ferramenta: aplica gate de auth se _orick_requer_login=1.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

while ( have_posts() ) : the_post();
    $post_id        = get_the_ID();
    $simulator_slug = get_post_meta( $post_id, '_orick_simulator_slug', true );
    $requer_login   = get_post_meta( $post_id, '_orick_requer_login', true ) === '1';
    $link_externo   = get_post_meta( $post_id, '_orick_link_externo', true );
    $como_usar      = get_post_meta( $post_id, '_orick_como_usar', true );
    $preco          = get_post_meta( $post_id, '_orick_preco', true ) ?: 'gratuito';

    $logado = Orick_Ferr_Auth::is_logged_in();
    $precisa_logar = $requer_login && ! $logado;
    ?>

    <main class="ofr-main">
      <article class="ofr-single">
        <header class="ofr-single-head">
          <div class="ofr-wrap">
            <nav class="ofr-breadcrumb">
              <a href="<?php echo esc_url( home_url( '/' ) ); ?>">Início</a>
              <span>›</span>
              <a href="<?php echo esc_url( get_post_type_archive_link( 'ferramenta' ) ); ?>">Ferramentas</a>
              <span>›</span>
              <span><?php the_title(); ?></span>
            </nav>

            <span class="ofr-kicker">FERRAMENTA · <?php echo esc_html( strtoupper( $preco ) ); ?></span>
            <h1 class="ofr-single-title"><?php the_title(); ?></h1>
            <?php if ( get_the_excerpt() ) : ?>
              <p class="ofr-single-lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
            <?php endif; ?>

            <?php echo orick_ferr_session_bar(); ?>
          </div>
        </header>

        <section class="ofr-single-body">
          <div class="ofr-wrap">
            <?php if ( $como_usar ) : ?>
              <div class="ofr-como-usar">
                <h3>Como usar</h3>
                <?php echo wpautop( esc_html( $como_usar ) ); ?>
              </div>
            <?php endif; ?>

            <?php the_content(); ?>

            <?php if ( $precisa_logar ) : ?>
              <?php
              $redirect_to = get_permalink( $post_id );
              $tab = sanitize_key( $_GET['ofr_tab'] ?? 'cadastro' );
              ?>
              <div class="ofr-gate">
                <div class="ofr-gate-tabs">
                  <a href="<?php echo esc_url( add_query_arg( 'ofr_tab', 'cadastro', get_permalink() ) ); ?>" class="<?php echo $tab === 'cadastro' ? 'is-active' : ''; ?>">Criar cadastro</a>
                  <a href="<?php echo esc_url( add_query_arg( 'ofr_tab', 'login', get_permalink() ) ); ?>" class="<?php echo $tab === 'login' ? 'is-active' : ''; ?>">Já tenho conta</a>
                </div>
                <?php if ( $tab === 'login' ) : ?>
                  <?php echo orick_ferr_render_login( [
                    'redirect_to'   => $redirect_to,
                    'context_title' => 'Entre para usar "' . esc_html( get_the_title() ) . '"',
                    'context_sub'   => 'Use a mesma conta que você já criou pras outras ferramentas.',
                  ] ); ?>
                <?php else : ?>
                  <?php echo orick_ferr_render_cadastro( [
                    'redirect_to'   => $redirect_to,
                    'context_title' => 'Cadastre-se para usar "' . esc_html( get_the_title() ) . '"',
                    'context_sub'   => 'Cadastro gratuito e rápido. Com ele, você libera todas as ferramentas.',
                  ] ); ?>
                <?php endif; ?>
              </div>

            <?php elseif ( $link_externo ) : ?>
              <div class="ofr-cta-externo">
                <a href="<?php echo esc_url( $link_externo ); ?>" target="_blank" rel="noopener" class="ofr-btn ofr-btn-primary ofr-btn-lg">Abrir ferramenta ↗</a>
                <p class="ofr-cta-externo-hint">Esta ferramenta abre em uma nova aba.</p>
              </div>

            <?php elseif ( $simulator_slug ) : ?>
              <div class="ofr-simulator ofr-simulator-<?php echo esc_attr( $simulator_slug ); ?>">
                <?php
                $sim_file = ORICK_FERR_DIR . 'simulators/' . sanitize_file_name( $simulator_slug ) . '.php';
                if ( file_exists( $sim_file ) ) {
                    include $sim_file;
                } else {
                    echo '<div class="ofr-alert ofr-alert-warn">Simulador não encontrado. Contate o administrador.</div>';
                }
                ?>
              </div>
            <?php endif; ?>
          </div>
        </section>
      </article>
    </main>

<?php endwhile;

get_footer();

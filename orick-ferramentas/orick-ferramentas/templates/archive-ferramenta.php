<?php
/**
 * Archive de /ferramentas/
 *  - ?orick_action=cadastro → form de cadastro
 *  - ?orick_action=login    → form de login
 *  - default                → hero (1 ferramenta destacada) + grid de cards tipográficos
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

get_header();

$action = sanitize_key( $_GET['orick_action'] ?? '' );
$redirect_to = esc_url_raw( $_GET['redirect_to'] ?? home_url( '/ferramentas/' ) );

if ( $action === 'cadastro' ) :
    echo '<main class="ofr-main">' . orick_ferr_render_cadastro( [ 'redirect_to' => $redirect_to ] ) . '</main>';
    get_footer();
    return;
endif;

if ( $action === 'login' ) :
    echo '<main class="ofr-main">' . orick_ferr_render_login( [ 'redirect_to' => $redirect_to ] ) . '</main>';
    get_footer();
    return;
endif;

// Coleta todos os IDs da página corrente. O primeiro marcado como "destaque_home" vira o hero.
$all_ids = [];
if ( have_posts() ) {
    while ( have_posts() ) : the_post();
        $all_ids[] = get_the_ID();
    endwhile;
    rewind_posts();
}

$hero_id  = 0;
$rest_ids = $all_ids;
foreach ( $all_ids as $i => $pid ) {
    if ( get_post_meta( $pid, '_orick_destaque_home', true ) === '1' ) {
        $hero_id = $pid;
        array_splice( $rest_ids, $i, 1 );
        break;
    }
}
// Se nenhuma marcada, o primeiro post vira o hero
if ( ! $hero_id && $rest_ids ) {
    $hero_id = array_shift( $rest_ids );
}

$taxes = get_terms( [ 'taxonomy' => 'ferramenta_cat', 'hide_empty' => true ] );
?>

<main class="os-archive os-archive--ferramentas">
  <header class="os-archive-head">
    <div class="os-wrap">
      <span class="os-archive-kicker">Ferramentas</span>
      <h1 class="os-archive-title">Simuladores <em>&amp; calculadoras</em></h1>
      <p class="os-archive-desc">Ferramentas criadas por quem vive o mercado. Use à vontade — algumas exigem cadastro gratuito.</p>
      <?php echo orick_ferr_session_bar(); ?>
    </div>
  </header>

  <?php if ( $hero_id ) : ?>
    <section class="tool-hero-wrap">
      <div class="os-wrap">
        <?php oricksilva_render_tool_card( $hero_id, 'hero' ); ?>
      </div>
    </section>
  <?php endif; ?>

  <?php if ( $taxes && ! is_wp_error( $taxes ) ) : ?>
    <nav class="os-archive-filters">
      <div class="os-wrap">
        <a href="<?php echo esc_url( get_post_type_archive_link( 'ferramenta' ) ); ?>" class="os-filter-chip <?php echo is_post_type_archive( 'ferramenta' ) && ! is_tax() ? 'is-active' : ''; ?>">Todas</a>
        <?php foreach ( $taxes as $t ) : ?>
          <a href="<?php echo esc_url( get_term_link( $t ) ); ?>" class="os-filter-chip <?php echo is_tax( 'ferramenta_cat', $t->slug ) ? 'is-active' : ''; ?>"><?php echo esc_html( $t->name ); ?></a>
        <?php endforeach; ?>
      </div>
    </nav>
  <?php endif; ?>

  <div class="os-archive-grid-wrap">
    <div class="os-wrap">
      <?php if ( $rest_ids ) : ?>
        <div class="tool-grid">
          <?php foreach ( $rest_ids as $pid ) oricksilva_render_tool_card( $pid, 'default' ); ?>
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

      <?php elseif ( ! $hero_id ) : ?>
        <div class="os-archive-empty">
          <h2>Nenhuma ferramenta ainda</h2>
          <p>Volte em breve — estamos preparando simuladores e calculadoras.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</main>

<?php get_footer(); ?>

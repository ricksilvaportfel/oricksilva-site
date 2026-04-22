<!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="os-topbar">
  <div class="os-wrap os-topbar-inner">
    <div class="os-brand">
      <button class="os-icon-btn" aria-label="Menu">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="7" x2="21" y2="7"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="17" x2="15" y2="17"/></svg>
      </button>
      <a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="display:flex;align-items:center;gap:10px;">
        <?php
        $os_logo_url = get_theme_mod( 'os_logo_image', '' );
        $os_logo_h   = intval( get_theme_mod( 'os_logo_height', 32 ) );
        if ( $os_logo_url ) : ?>
          <img src="<?php echo esc_url( $os_logo_url ); ?>" alt="<?php bloginfo( 'name' ); ?>" style="height:<?php echo esc_attr( $os_logo_h ); ?>px;width:auto;display:block;">
        <?php else : ?>
          <div class="os-brand-mark">RS</div>
          <div class="os-brand-name">O <em>Rick</em> Silva</div>
        <?php endif; ?>
      </a>
    </div>

    <?php
    if ( has_nav_menu( 'primary' ) ) {
        wp_nav_menu( [
            'theme_location' => 'primary',
            'container'      => false,
            'menu_class'     => 'os-nav',
            'depth'          => 1,
            'fallback_cb'    => false,
        ] );
    } else {
        echo '<ul class="os-nav">';
        $default_nav = [
            'Início' => home_url('/'),
            'Artigos' => home_url('/categoria/artigos/'),
            'Colunistas' => home_url('/categoria/colunistas/'),
            'Materiais' => home_url('/categoria/materiais/'),
            'Ferramentas' => post_type_exists( 'ferramenta' ) ? get_post_type_archive_link( 'ferramenta' ) : home_url('/categoria/ferramentas/'),
            'Vídeos' => home_url('/categoria/videos/'),
            'Podcast' => home_url('/categoria/podcast/'),
            'Eventos' => home_url('/categoria/eventos/'),
        ];
        foreach ( $default_nav as $label => $url ) {
            echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></li>';
        }
        echo '</ul>';
    }
    ?>

    <div class="os-header-right">
      <form class="os-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
        <input type="search" name="s" placeholder="Buscar colunas, ferramentas…" value="<?php echo esc_attr( get_search_query() ); ?>">
      </form>

      <?php
      /* Auth area — Entrar / Olá, Nome */
      if ( is_user_logged_in() ) :
        $u = wp_get_current_user();
        $first_name = $u->first_name ?: explode( ' ', $u->display_name )[0];
        $logout_url = wp_logout_url( home_url( '/' ) );
        $is_admin_user = current_user_can( 'manage_options' );
        ?>
        <div class="os-auth">
          <div class="os-auth-greet">
            <button type="button" class="os-auth-greet-btn" aria-haspopup="menu" aria-expanded="false">
              Olá, <strong><?php echo esc_html( $first_name ); ?></strong>
              <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="os-auth-menu" role="menu">
              <?php if ( $is_admin_user ) : ?>
                <a href="<?php echo esc_url( admin_url() ); ?>">Painel</a>
              <?php endif; ?>
              <a href="<?php echo esc_url( admin_url( 'profile.php' ) ); ?>">Meu perfil</a>
              <hr>
              <a href="<?php echo esc_url( $logout_url ); ?>">Sair</a>
            </div>
          </div>
        </div>
        <script>
        (function(){
          var btn = document.querySelector('.os-auth-greet-btn');
          var menu = document.querySelector('.os-auth-menu');
          if (!btn || !menu) return;
          btn.addEventListener('click', function(e){
            e.stopPropagation();
            menu.classList.toggle('is-open');
            btn.setAttribute('aria-expanded', menu.classList.contains('is-open'));
          });
          document.addEventListener('click', function(e){
            if (!menu.contains(e.target) && !btn.contains(e.target)) {
              menu.classList.remove('is-open');
              btn.setAttribute('aria-expanded', 'false');
            }
          });
        })();
        </script>
      <?php else :
        $login_url = wp_login_url( home_url( $_SERVER['REQUEST_URI'] ?? '/' ) );
        // Se existe a página de login do plugin Ferramentas, usa ela
        if ( function_exists( 'orick_tools_login_url' ) ) {
          $login_url = orick_tools_login_url();
        }
        ?>
        <a href="<?php echo esc_url( $login_url ); ?>" class="os-auth-login">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
          Entrar
        </a>
      <?php endif; ?>

      <a href="#newsletter" class="os-btn">Newsletter gratuita</a>
    </div>
  </div>
</header>

<div class="os-subnav">
  <div class="os-wrap os-subnav-inner">
    <?php
    if ( has_nav_menu( 'subnav' ) ) {
        wp_nav_menu( [
            'theme_location' => 'subnav',
            'container'      => false,
            'items_wrap'     => '%3$s',
            'depth'          => 1,
            'fallback_cb'    => false,
        ] );
    } else {
        $sub = [ 'Mercado Financeiro', 'Wealth Advisor', 'Seguros & Risco', 'Corporate', 'Carreira', 'Planejamento', 'Comportamental' ];
        foreach ( $sub as $s ) echo '<a href="#">' . esc_html( $s ) . '</a>';
    }
    ?>
    <span class="os-subnav-date"><?php echo mb_strtoupper( wp_date( 'D · d M Y · H:i' ) ); ?> BRT</span>
  </div>
</div>

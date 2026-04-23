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
      <button class="os-icon-btn os-menu-toggle" aria-label="Abrir menu" aria-expanded="false" aria-controls="os-mobile-menu" type="button">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="7" x2="21" y2="7"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="17" x2="15" y2="17"/></svg>
      </button>
      <a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="display:flex;align-items:center;gap:10px;">
        <?php
        $os_logo_url = get_theme_mod( 'os_logo_image', '' );
        $os_logo_h   = intval( get_theme_mod( 'os_logo_height', 40 ) );
        if ( $os_logo_url ) : ?>
          <img src="<?php echo esc_url( $os_logo_url ); ?>" alt="<?php bloginfo( 'name' ); ?>" style="max-height:<?php echo esc_attr( $os_logo_h ); ?>px;">
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
            'Artigos' => home_url('/categoria/artigos/'),
            'Colunistas' => home_url('/categoria/colunistas/'),
            'Materiais' => home_url('/categoria/materiais/'),
            'Ferramentas' => post_type_exists( 'ferramenta' ) ? get_post_type_archive_link( 'ferramenta' ) : home_url('/categoria/ferramentas/'),
            'Vídeos' => post_type_exists( 'video' ) ? get_post_type_archive_link( 'video' ) : home_url('/categoria/videos/'),
            'Podcast' => home_url('/categoria/podcast/'),
            'Eventos' => home_url('/categoria/eventos/'),
        ];
        foreach ( $default_nav as $label => $url ) {
            echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a></li>';
        }
        echo '</ul>';
    }
    ?>

    <button class="os-mobile-close" aria-label="Fechar menu" type="button">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="6" y1="6" x2="18" y2="18"/><line x1="6" y1="18" x2="18" y2="6"/></svg>
    </button>

    <div class="os-header-right">
      <form class="os-search" role="search" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
        <input type="search" name="s" placeholder="Buscar colunas, ferramentas…" value="<?php echo esc_attr( get_search_query() ); ?>">
      </form>

      <?php
      /* Auth area — Prioriza LEAD de Ferramentas (público) > WP admin (interno) */
      $os_ferr_lead   = class_exists( 'Orick_Ferr_Auth' ) ? Orick_Ferr_Auth::current_lead() : null;
      $os_wp_logged   = is_user_logged_in();
      $os_show_user   = null; // 'lead' | 'wp' | null

      if ( $os_ferr_lead ) {
          $os_show_user = 'lead';
          $os_first     = explode( ' ', $os_ferr_lead->nome )[0];
      } elseif ( $os_wp_logged ) {
          $os_show_user = 'wp';
          $u = wp_get_current_user();
          $os_first = $u->first_name ?: explode( ' ', $u->display_name )[0];
      }

      if ( $os_show_user ) : ?>
        <div class="os-auth">
          <div class="os-auth-greet">
            <button type="button" class="os-auth-greet-btn" aria-haspopup="menu" aria-expanded="false">
              Olá, <strong><?php echo esc_html( $os_first ); ?></strong>
              <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="os-auth-menu" role="menu">
              <?php if ( $os_show_user === 'lead' ) :
                $ferr_url  = home_url( '/ferramentas/' );
                $logout_url = wp_nonce_url( add_query_arg( 'orick_ferr_action_link', 'logout', $ferr_url ), 'orick_ferr_logout' );
                ?>
                <a href="<?php echo esc_url( $ferr_url ); ?>">Minhas ferramentas</a>
                <?php if ( $os_wp_logged && current_user_can( 'manage_options' ) ) : ?>
                  <a href="<?php echo esc_url( admin_url() ); ?>">Painel (admin)</a>
                <?php endif; ?>
                <hr>
                <a href="<?php echo esc_url( $logout_url ); ?>">Sair</a>
              <?php else : /* WP admin only */
                $logout_url = wp_logout_url( home_url( '/' ) );
                ?>
                <?php if ( current_user_can( 'manage_options' ) ) : ?>
                  <a href="<?php echo esc_url( admin_url() ); ?>">Painel</a>
                <?php endif; ?>
                <a href="<?php echo esc_url( admin_url( 'profile.php' ) ); ?>">Meu perfil</a>
                <hr>
                <a href="<?php echo esc_url( $logout_url ); ?>">Sair</a>
              <?php endif; ?>
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
        // Ninguém logado — manda pro login do plugin Ferramentas
        $login_url = class_exists( 'Orick_Ferr_Auth' )
          ? Orick_Ferr_Auth::login_url( $_SERVER['REQUEST_URI'] ?? '/' )
          : wp_login_url( home_url( $_SERVER['REQUEST_URI'] ?? '/' ) );
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

<script>
(function(){
  var body = document.body;
  var toggle = document.querySelector('.os-menu-toggle');
  var close  = document.querySelector('.os-mobile-close');
  if (!toggle) return;
  function open(){  body.classList.add('is-menu-open');    toggle.setAttribute('aria-expanded','true');  }
  function shut(){  body.classList.remove('is-menu-open'); toggle.setAttribute('aria-expanded','false'); }
  toggle.addEventListener('click', function(){ body.classList.contains('is-menu-open') ? shut() : open(); });
  if (close) close.addEventListener('click', shut);
  // Clicar num link do menu fecha
  document.querySelectorAll('.os-nav a, .os-subnav-inner a').forEach(function(a){ a.addEventListener('click', shut); });
  // ESC fecha
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') shut(); });
})();
</script>

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
        // label => slug da tag (cada item abre /tag/{slug}/ — mini-home temática renderizada por tag.php)
        $sub = [
            'Mercado Financeiro' => 'mercado-financeiro',
            'Wealth Advisor'     => 'wealth-advisor',
            'Seguros & Risco'    => 'seguros-risco',
            'Corporate'          => 'corporate',
            'Carreira'           => 'carreira',
            'Planejamento'       => 'planejamento',
            'Comercial'          => 'comercial',
        ];
        foreach ( $sub as $label => $slug ) {
            printf(
                '<a href="%s">%s</a>',
                esc_url( home_url( '/tag/' . $slug . '/' ) ),
                esc_html( $label )
            );
        }
    }
    ?>
    <span class="os-subnav-date"><?php echo mb_strtoupper( wp_date( 'D · d M Y · H:i' ) ); ?> BRT</span>
  </div>
</div>

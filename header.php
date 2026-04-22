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
        <div class="os-brand-mark">RS</div>
        <div class="os-brand-name">O <em>Rick</em> Silva</div>
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

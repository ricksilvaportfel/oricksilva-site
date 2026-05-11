<?php
/**
 * Roteamento de templates públicos:
 *  - /ferramentas/           → listagem de ferramentas (ou form de cadastro/login se ?orick_action)
 *  - /ferramentas/{slug}/    → single da ferramenta (com gate de auth se requer_login)
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Intercepta /ferramentas/?orick_action=login|cadastro e renderiza o form.
 * Roda no archive da CPT.
 */
add_filter( 'template_include', function( $template ) {
    // single de ferramenta: aplica gate de auth
    if ( is_singular( 'ferramenta' ) ) {
        $plugin_tpl = ORICK_FERR_DIR . 'templates/single-ferramenta.php';
        if ( file_exists( $plugin_tpl ) ) return $plugin_tpl;
    }

    // archive de ferramenta: listagem (ou forms)
    if ( is_post_type_archive( 'ferramenta' ) || is_tax( 'ferramenta_cat' ) ) {
        $plugin_tpl = ORICK_FERR_DIR . 'templates/archive-ferramenta.php';
        if ( file_exists( $plugin_tpl ) ) return $plugin_tpl;
    }

    // Material
    if ( is_singular( 'material' ) ) {
        $tpl = ORICK_FERR_DIR . 'templates/single-material.php';
        if ( file_exists( $tpl ) ) return $tpl;
    }
    if ( is_post_type_archive( 'material' ) || is_tax( 'material_tipo' ) ) {
        $tpl = ORICK_FERR_DIR . 'templates/archive-material.php';
        if ( file_exists( $tpl ) ) return $tpl;
    }

    // Vídeo
    if ( is_singular( 'video' ) ) {
        $tpl = ORICK_FERR_DIR . 'templates/single-video.php';
        if ( file_exists( $tpl ) ) return $tpl;
    }
    if ( is_post_type_archive( 'video' ) ) {
        $tpl = ORICK_FERR_DIR . 'templates/archive-video.php';
        if ( file_exists( $tpl ) ) return $tpl;
    }

    // Episódio
    if ( is_singular( 'episodio' ) ) {
        $tpl = ORICK_FERR_DIR . 'templates/single-episodio.php';
        if ( file_exists( $tpl ) ) return $tpl;
    }
    if ( is_post_type_archive( 'episodio' ) ) {
        $tpl = ORICK_FERR_DIR . 'templates/archive-episodio.php';
        if ( file_exists( $tpl ) ) return $tpl;
    }

    // Evento
    if ( is_singular( 'evento' ) ) {
        $tpl = ORICK_FERR_DIR . 'templates/single-evento.php';
        if ( file_exists( $tpl ) ) return $tpl;
    }
    if ( is_post_type_archive( 'evento' ) ) {
        $tpl = ORICK_FERR_DIR . 'templates/archive-evento.php';
        if ( file_exists( $tpl ) ) return $tpl;
    }

    // Colunista — CPT
    if ( is_singular( 'colunista' ) ) {
        $tpl = ORICK_FERR_DIR . 'templates/single-colunista.php';
        if ( file_exists( $tpl ) ) return $tpl;
    }
    if ( is_post_type_archive( 'colunista' ) || get_query_var( 'orick_colunistas' ) ) {
        $tpl = ORICK_FERR_DIR . 'templates/archive-colunista.php';
        if ( file_exists( $tpl ) ) return $tpl;
    }

    return $template;
} );

/**
 * Helper: bloco de ação no topo quando usuário está logado (nome + logout).
 */
function orick_ferr_session_bar() {
    $lead = Orick_Ferr_Auth::current_lead();
    if ( ! $lead ) return '';
    $logout_url = wp_nonce_url( add_query_arg( 'orick_ferr_action_link', 'logout', home_url( '/ferramentas/' ) ), 'orick_ferr_logout' );
    $nome = esc_html( $lead->nome );
    $first = esc_html( explode( ' ', $lead->nome )[0] );
    return '<div class="ofr-session-bar">
      <span class="ofr-session-hello">Olá, <strong>' . $first . '</strong></span>
      <form method="post" action="' . esc_url( home_url( '/' ) ) . '" style="display:inline;">
        ' . wp_nonce_field( 'orick_ferr_logout', '_orick_nonce', true, false ) . '
        <input type="hidden" name="orick_ferr_action" value="logout">
        <button type="submit" class="ofr-session-logout">Sair</button>
      </form>
    </div>';
}

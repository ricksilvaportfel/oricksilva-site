<?php
/**
 * Ícone SVG por post_type — usado no slot lateral-hero da home
 * quando o post não tem imagem destacada (fallback editorial).
 *
 * Reutiliza os SVGs de ferramenta e evento (já definidos em
 * render-tool-card.php / render-event-card.php) e adiciona ícones
 * próprios pra video, episodio e material.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! function_exists( 'oricksilva_lateral_icon_svg' ) ) {
    function oricksilva_lateral_icon_svg( $post_id ) {
        $pt = get_post_type( $post_id );

        if ( $pt === 'ferramenta' ) {
            $svg = get_post_meta( $post_id, '_orick_tool_icon_svg', true );
            if ( $svg ) return $svg;
            $type = get_post_meta( $post_id, '_orick_tool_type', true ) ?: 'simulador';
            return function_exists( 'oricksilva_default_tool_icon' ) ? oricksilva_default_tool_icon( $type ) : '';
        }

        if ( $pt === 'evento' ) {
            $formato = get_post_meta( $post_id, '_orick_ev_formato', true ) ?: 'presencial';
            return function_exists( 'oricksilva_default_event_icon' ) ? oricksilva_default_event_icon( $formato ) : '';
        }

        if ( $pt === 'video' ) {
            // Play em círculo
            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="9"/>
                <polygon points="10,8 17,12 10,16" fill="currentColor" stroke="none"/>
            </svg>';
        }

        if ( $pt === 'episodio' ) {
            // Microfone
            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="9" y="3" width="6" height="12" rx="3"/>
                <path d="M5 11a7 7 0 0 0 14 0"/>
                <line x1="12" y1="18" x2="12" y2="22"/>
                <line x1="8"  y1="22" x2="16" y2="22"/>
            </svg>';
        }

        if ( $pt === 'material' ) {
            // Documento com seta de download
            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/>
                <polyline points="14,3 14,8 19,8"/>
                <line x1="12" y1="12" x2="12" y2="17"/>
                <polyline points="9,15 12,18 15,15"/>
            </svg>';
        }

        return '';
    }
}

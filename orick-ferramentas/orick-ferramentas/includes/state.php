<?php
/**
 * Orick Ferramentas — Estado da ferramenta por lead
 *
 * Permite que ferramentas (simuladores) salvem e recuperem configurações
 * por usuário logado (lead), via AJAX. Usa a tabela orick_lead_tool_state.
 *
 * Endpoints:
 *   POST /wp-admin/admin-ajax.php?action=orick_ferr_save_state
 *   POST /wp-admin/admin-ajax.php?action=orick_ferr_load_state
 *
 * Ambos exigem:
 *   - lead logado (cookie orick_ferr_session válido)
 *   - nonce válido (orick_ferr_state)
 *   - tool_slug whitelist
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Orick_Ferr_State {

    /** Slugs permitidos (precisam bater com o dropdown do CPT) */
    const ALLOWED_TOOLS = [
        'planejamento-if',
        'goal-based',
        'planejamento-comercial',
        'fee-commission',
    ];

    /** Retorna o state de uma ferramenta pra um lead. Pode ser chamado server-side. */
    public static function get( $lead_id, $tool_slug ) {
        global $wpdb;
        if ( ! in_array( $tool_slug, self::ALLOWED_TOOLS, true ) ) return null;
        $table = $wpdb->prefix . 'orick_lead_tool_state';
        $row = $wpdb->get_row( $wpdb->prepare(
            "SELECT data FROM $table WHERE lead_id = %d AND tool_slug = %s LIMIT 1",
            (int) $lead_id, $tool_slug
        ) );
        if ( ! $row ) return null;
        $decoded = json_decode( $row->data, true );
        return is_array( $decoded ) ? $decoded : null;
    }

    /** Salva state (upsert). */
    public static function save( $lead_id, $tool_slug, $data ) {
        global $wpdb;
        if ( ! in_array( $tool_slug, self::ALLOWED_TOOLS, true ) ) return false;
        $table = $wpdb->prefix . 'orick_lead_tool_state';

        // Limita tamanho (200kb) por segurança
        $json = wp_json_encode( $data );
        if ( $json === false || strlen( $json ) > 204800 ) return false;

        return (bool) $wpdb->query( $wpdb->prepare(
            "INSERT INTO $table (lead_id, tool_slug, data, updated_at)
             VALUES (%d, %s, %s, %s)
             ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = VALUES(updated_at)",
            (int) $lead_id, $tool_slug, $json, current_time( 'mysql' )
        ) );
    }
}

/* ---------- AJAX: load ---------- */
add_action( 'wp_ajax_orick_ferr_load_state',        'orick_ferr_ajax_load_state' );
add_action( 'wp_ajax_nopriv_orick_ferr_load_state', 'orick_ferr_ajax_load_state' );
function orick_ferr_ajax_load_state() {
    check_ajax_referer( 'orick_ferr_state', '_nonce' );
    $lead_id = Orick_Ferr_Auth::current_lead_id();
    if ( ! $lead_id ) wp_send_json_error( [ 'message' => 'Sessão expirada. Faça login novamente.' ], 401 );

    $tool = sanitize_key( $_POST['tool'] ?? '' );
    $data = Orick_Ferr_State::get( $lead_id, $tool );
    wp_send_json_success( [ 'data' => $data ] );
}

/* ---------- AJAX: save ---------- */
add_action( 'wp_ajax_orick_ferr_save_state',        'orick_ferr_ajax_save_state' );
add_action( 'wp_ajax_nopriv_orick_ferr_save_state', 'orick_ferr_ajax_save_state' );
function orick_ferr_ajax_save_state() {
    check_ajax_referer( 'orick_ferr_state', '_nonce' );
    $lead_id = Orick_Ferr_Auth::current_lead_id();
    if ( ! $lead_id ) wp_send_json_error( [ 'message' => 'Sessão expirada. Faça login novamente.' ], 401 );

    $tool = sanitize_key( $_POST['tool'] ?? '' );
    $raw  = wp_unslash( $_POST['data'] ?? '' );
    $data = json_decode( $raw, true );
    if ( ! is_array( $data ) ) wp_send_json_error( [ 'message' => 'Payload inválido.' ], 400 );

    $ok = Orick_Ferr_State::save( $lead_id, $tool, $data );
    if ( ! $ok ) wp_send_json_error( [ 'message' => 'Falha ao salvar.' ], 500 );

    wp_send_json_success( [ 'saved_at' => current_time( 'mysql' ) ] );
}

/* ---------- helper: expõe ajax vars ao front só quando precisa ---------- */
function orick_ferr_localize_state_vars() {
    return [
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'orick_ferr_state' ),
    ];
}

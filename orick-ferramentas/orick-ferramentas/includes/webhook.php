<?php
/**
 * Webhook — envia lead pra URL configurada após cadastro.
 * Disparado em background via wp_remote_post (não bloqueia redirect).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'orick_ferr_after_register', function( $lead_id ) {
    $url = get_option( 'orick_ferr_webhook_url' );
    if ( empty( $url ) ) return;

    global $wpdb;
    $table = $wpdb->prefix . ORICK_FERR_TABLE;
    $lead = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $lead_id ), ARRAY_A );
    if ( ! $lead ) return;

    // Monta payload
    $payload = [
        'lead_id'         => (int) $lead['id'],
        'nome'            => $lead['nome'],
        'email'           => $lead['email'],
        'telefone'        => $lead['telefone'],
        'cpf'             => $lead['cpf'],
        'profissao'       => $lead['profissao'],
        'profissao_outra' => $lead['profissao_outra'],
        'aum_atendido'    => $lead['aum_atendido'] !== null ? (float) $lead['aum_atendido'] : null,
        'ip'              => $lead['ip_cadastro'],
        'user_agent'      => $lead['user_agent'],
        'created_at'      => $lead['created_at'],
        'origem'          => 'oricksilva.com.br/ferramentas',
    ];

    $secret = get_option( 'orick_ferr_webhook_secret' );
    $body = wp_json_encode( $payload );
    $signature = hash_hmac( 'sha256', $body, $secret );

    $args = [
        'body'      => $body,
        'headers'   => [
            'Content-Type'          => 'application/json',
            'X-Orick-Signature'     => $signature,
            'X-Orick-Event'         => 'lead.created',
            'User-Agent'            => 'OrickFerramentas/1.0',
        ],
        'timeout'   => 10,
        'blocking'  => true, // aguarda resposta pra salvar status
        'sslverify' => true,
    ];

    $response = wp_remote_post( $url, $args );

    // Salva status
    if ( is_wp_error( $response ) ) {
        $wpdb->update( $table, [
            'webhook_status'   => 'error',
            'webhook_response' => $response->get_error_message(),
        ], [ 'id' => $lead_id ] );
    } else {
        $code = wp_remote_retrieve_response_code( $response );
        $body_response = wp_remote_retrieve_body( $response );
        $wpdb->update( $table, [
            'webhook_status'   => ( $code >= 200 && $code < 300 ) ? 'success' : 'error',
            'webhook_response' => substr( 'HTTP ' . $code . ' | ' . $body_response, 0, 65535 ),
        ], [ 'id' => $lead_id ] );
    }
}, 10, 1 );

/**
 * Retry manual de webhook (via admin).
 */
function orick_ferr_retry_webhook( $lead_id ) {
    do_action( 'orick_ferr_after_register', $lead_id );
}

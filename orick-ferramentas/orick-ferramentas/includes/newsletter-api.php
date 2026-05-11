<?php
/**
 * REST API endpoint para envio de newsletter via MailPoet.
 *
 * POST /wp-json/orick/v1/newsletter/send
 * Body JSON: { "subject": "...", "html": "...", "list_id": 3 }
 * Auth: Application Password (Basic Auth)
 *
 * Cria uma newsletter no MailPoet e agenda envio imediato para a lista especificada.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'rest_api_init', function () {
    register_rest_route( 'orick/v1', '/newsletter/send', [
        'methods'             => 'POST',
        'callback'            => 'orick_newsletter_send',
        'permission_callback' => function ( $request ) {
            return current_user_can( 'edit_posts' );
        },
    ] );
} );

function orick_newsletter_send( WP_REST_Request $request ) {
    $subject = sanitize_text_field( $request->get_param( 'subject' ) );
    $html    = $request->get_param( 'html' );
    $list_id = intval( $request->get_param( 'list_id' ) ?: 3 );

    if ( empty( $subject ) || empty( $html ) ) {
        return new WP_REST_Response( [
            'success' => false,
            'error'   => 'subject e html sao obrigatorios',
        ], 400 );
    }

    // Verifica se MailPoet está ativo
    if ( ! class_exists( \MailPoet\API\API::class ) ) {
        return new WP_REST_Response( [
            'success' => false,
            'error'   => 'MailPoet nao esta ativo',
        ], 500 );
    }

    try {
        $mailpoet = \MailPoet\API\API::MP( 'v1' );

        // Cria a newsletter
        $newsletter = $mailpoet->addNewsletter( [
            'subject' => $subject,
            'type'    => 'standard',
            'body'    => [
                'content' => [
                    'type'    => 'container',
                    'columnLayout' => false,
                    'orientation'  => 'vertical',
                    'blocks'       => [
                        [
                            'type' => 'html',
                            'html' => $html,
                        ],
                    ],
                ],
            ],
        ] );

        $newsletter_id = $newsletter['id'] ?? $newsletter->id ?? null;

        if ( ! $newsletter_id ) {
            return new WP_REST_Response( [
                'success' => false,
                'error'   => 'Falha ao criar newsletter no MailPoet',
            ], 500 );
        }

        // Associa a lista
        $mailpoet->addNewsletterToLists( $newsletter_id, [ $list_id ] );

        // Envia
        $mailpoet->sendNewsletter( $newsletter_id );

        return new WP_REST_Response( [
            'success'       => true,
            'newsletter_id' => $newsletter_id,
            'subject'       => $subject,
            'list_id'       => $list_id,
            'message'       => 'Newsletter enviada com sucesso',
        ], 200 );

    } catch ( \Exception $e ) {
        return new WP_REST_Response( [
            'success' => false,
            'error'   => $e->getMessage(),
        ], 500 );
    }
}

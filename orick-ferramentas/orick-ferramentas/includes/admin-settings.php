<?php
/**
 * Admin → Ferramentas → Configurações (webhook URL + dias de sessão).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'admin_menu', function() {
    add_submenu_page(
        'edit.php?post_type=ferramenta',
        'Configurações',
        'Configurações',
        'manage_options',
        'orick-ferr-settings',
        'orick_ferr_settings_page'
    );
} );

add_action( 'admin_init', function() {
    register_setting( 'orick_ferr_settings', 'orick_ferr_webhook_url', [
        'sanitize_callback' => 'esc_url_raw',
    ] );
    register_setting( 'orick_ferr_settings', 'orick_ferr_webhook_secret', [
        'sanitize_callback' => 'sanitize_text_field',
    ] );
    register_setting( 'orick_ferr_settings', 'orick_ferr_session_days', [
        'sanitize_callback' => 'absint',
    ] );
    register_setting( 'orick_ferr_settings', 'orick_ferr_youtube_channel_id', [
        'sanitize_callback' => 'sanitize_text_field',
    ] );
} );

/* teste de webhook */
add_action( 'admin_init', function() {
    if ( empty( $_POST['orick_ferr_test_webhook'] ) ) return;
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sem permissão.' );
    check_admin_referer( 'orick_ferr_test_webhook' );

    $url = get_option( 'orick_ferr_webhook_url' );
    $result = [];
    if ( empty( $url ) ) {
        $result = [ 'ok' => false, 'msg' => 'Nenhuma URL configurada.' ];
    } else {
        $payload = [
            'lead_id'   => 0,
            'nome'      => 'Teste Webhook',
            'email'     => 'teste@example.com',
            'telefone'  => '11999999999',
            'cpf'       => '00000000000',
            'profissao' => 'assessor',
            'aum_atendido' => 1000000,
            'origem'    => 'oricksilva.com.br/ferramentas',
            'event'     => 'test',
        ];
        $body = wp_json_encode( $payload );
        $sig  = hash_hmac( 'sha256', $body, get_option( 'orick_ferr_webhook_secret' ) );
        $resp = wp_remote_post( $url, [
            'body'    => $body,
            'headers' => [
                'Content-Type'       => 'application/json',
                'X-Orick-Signature'  => $sig,
                'X-Orick-Event'      => 'test',
            ],
            'timeout' => 10,
        ] );
        if ( is_wp_error( $resp ) ) {
            $result = [ 'ok' => false, 'msg' => 'Erro de conexão: ' . $resp->get_error_message() ];
        } else {
            $code = wp_remote_retrieve_response_code( $resp );
            $body_r = wp_remote_retrieve_body( $resp );
            $result = [
                'ok'  => $code >= 200 && $code < 300,
                'msg' => 'HTTP ' . $code . ' — ' . substr( $body_r, 0, 500 ),
            ];
        }
    }
    set_transient( 'orick_ferr_test_result', $result, 60 );
    wp_safe_redirect( add_query_arg( 'tested', '1' ) );
    exit;
} );

function orick_ferr_settings_page() {
    $test_result = get_transient( 'orick_ferr_test_result' );
    if ( $test_result ) delete_transient( 'orick_ferr_test_result' );

    $webhook_secret = get_option( 'orick_ferr_webhook_secret' );
    ?>
    <div class="wrap">
      <h1>Configurações — Ferramentas</h1>

      <?php if ( $test_result ) : ?>
        <div class="notice notice-<?php echo $test_result['ok'] ? 'success' : 'error'; ?> is-dismissible">
          <p><strong>Teste de webhook:</strong> <?php echo esc_html( $test_result['msg'] ); ?></p>
        </div>
      <?php endif; ?>

      <form method="post" action="options.php">
        <?php settings_fields( 'orick_ferr_settings' ); ?>

        <h2>Webhook</h2>
        <p>A cada novo cadastro, um POST JSON é enviado pra URL abaixo. Use-o pra integrar com Marketing Cloud, Zapier, n8n, RD Station, etc.</p>

        <table class="form-table">
          <tr>
            <th><label for="orick_ferr_webhook_url">URL do Webhook</label></th>
            <td>
              <input type="url" name="orick_ferr_webhook_url" id="orick_ferr_webhook_url" value="<?php echo esc_attr( get_option( 'orick_ferr_webhook_url' ) ); ?>" class="regular-text" placeholder="https://...">
              <p class="description">Deixe em branco para desativar o webhook. Os leads continuam sendo salvos no banco normalmente.</p>
            </td>
          </tr>
          <tr>
            <th>Secret (HMAC)</th>
            <td>
              <code style="padding:6px 10px; background:#f5f5f5; display:inline-block; user-select:all;"><?php echo esc_html( $webhook_secret ); ?></code>
              <p class="description">Assinatura enviada no header <code>X-Orick-Signature</code>. O receptor pode validar pra garantir que o POST veio mesmo daqui. Copie e cole no lado do receptor (Marketing Cloud / Zapier / n8n).</p>
            </td>
          </tr>
          <tr>
            <th><label for="orick_ferr_youtube_channel_id">Canal do YouTube (ID)</label></th>
            <td>
              <input type="text" name="orick_ferr_youtube_channel_id" id="orick_ferr_youtube_channel_id" value="<?php echo esc_attr( get_option( 'orick_ferr_youtube_channel_id' ) ); ?>" class="regular-text" placeholder="UCxxxxxxxxxxxxxxxxxxxx">
              <p class="description">ID do canal (começa com <code>UC...</code>). O plugin sincroniza os vídeos mais recentes a cada hora via RSS. Para achar o ID: acesse seu canal, menu ⋮ → Compartilhar → Copiar ID do canal. Última sincronização: <?php echo esc_html( get_option( 'orick_ferr_youtube_last_sync' ) ?: 'nunca' ); ?>. <a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=orick_ferr_sync_youtube' ), 'orick_ferr_sync_youtube' ) ); ?>">Sincronizar agora</a>.</p>
            </td>
          </tr>
          <tr>
            <th>Sessão (dias)</th>
            <td>
              <input type="number" name="orick_ferr_session_days" value="<?php echo esc_attr( get_option( 'orick_ferr_session_days', 365 ) ); ?>" min="1" max="3650" style="width:100px;">
              <p class="description">Quantos dias o login fica válido antes de pedir pra logar de novo. Padrão: 365 dias.</p>
            </td>
          </tr>
        </table>

        <?php submit_button( 'Salvar configurações' ); ?>
      </form>

      <hr style="margin: 30px 0;">

      <h2>Testar webhook</h2>
      <p>Dispara um POST de exemplo pra URL configurada. O payload tem o mesmo formato dos cadastros reais, mas com <code>event=test</code>.</p>
      <form method="post">
        <?php wp_nonce_field( 'orick_ferr_test_webhook' ); ?>
        <input type="hidden" name="orick_ferr_test_webhook" value="1">
        <button class="button button-secondary">Enviar POST de teste</button>
      </form>

      <hr style="margin: 30px 0;">

      <h2>Formato do payload</h2>
      <pre style="background:#1e1e1e; color:#f5f5f5; padding:16px; overflow:auto; font-size:13px;">POST <?php echo esc_html( get_option( 'orick_ferr_webhook_url' ) ?: 'https://seu-endpoint.com' ); ?>

Headers:
  Content-Type: application/json
  X-Orick-Signature: &lt;hmac-sha256 do body com o Secret&gt;
  X-Orick-Event: lead.created

Body:
{
  "lead_id": 123,
  "nome": "João Silva",
  "email": "joao@example.com",
  "telefone": "11999999999",
  "cpf": "12345678900",
  "profissao": "assessor",
  "profissao_outra": "",
  "aum_atendido": 5000000.00,
  "ip": "189.0.0.1",
  "user_agent": "Mozilla/5.0...",
  "created_at": "2026-04-22 14:00:00",
  "origem": "oricksilva.com.br/ferramentas"
}</pre>
    </div>
    <?php
}

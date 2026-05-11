<?php
/**
 * Admin → Ferramentas → Leads (lista + filtros + export CSV + retry webhook).
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ---------- menu ---------- */
add_action( 'admin_menu', function() {
    add_submenu_page(
        'edit.php?post_type=ferramenta',
        'Leads capturados',
        'Leads capturados',
        'manage_options',
        'orick-ferr-leads',
        'orick_ferr_leads_page'
    );
} );

/* ---------- export CSV ---------- */
add_action( 'admin_init', function() {
    if ( empty( $_GET['orick_ferr_export'] ) || $_GET['orick_ferr_export'] !== 'csv' ) return;
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sem permissão.' );
    check_admin_referer( 'orick_ferr_export_csv' );

    global $wpdb;
    $table = $wpdb->prefix . ORICK_FERR_TABLE;
    $where = orick_ferr_leads_where_sql();
    $rows = $wpdb->get_results( "SELECT id, nome, email, telefone, cpf, profissao, profissao_outra, aum_atendido, webhook_status, created_at, last_login_at FROM $table WHERE $where ORDER BY created_at DESC", ARRAY_A );

    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=leads-ferramentas-' . date( 'Y-m-d-His' ) . '.csv' );
    $out = fopen( 'php://output', 'w' );
    fputs( $out, "\xEF\xBB\xBF" ); // BOM UTF-8
    fputcsv( $out, [ 'ID', 'Nome', 'Email', 'Telefone', 'CPF', 'Profissão', 'Profissão outra', 'AuM (R$)', 'Webhook', 'Cadastro', 'Último login' ], ';' );
    foreach ( $rows as $r ) {
        fputcsv( $out, [
            $r['id'],
            $r['nome'],
            $r['email'],
            $r['telefone'],
            $r['cpf'],
            $r['profissao'],
            $r['profissao_outra'],
            $r['aum_atendido'],
            $r['webhook_status'],
            $r['created_at'],
            $r['last_login_at'],
        ], ';' );
    }
    fclose( $out );
    exit;
} );

/* ---------- retry webhook (uma linha) ---------- */
add_action( 'admin_init', function() {
    if ( empty( $_GET['orick_ferr_retry'] ) ) return;
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sem permissão.' );
    check_admin_referer( 'orick_ferr_retry_' . (int) $_GET['orick_ferr_retry'] );
    orick_ferr_retry_webhook( (int) $_GET['orick_ferr_retry'] );
    wp_safe_redirect( add_query_arg( 'retried', '1', remove_query_arg( [ 'orick_ferr_retry', '_wpnonce' ] ) ) );
    exit;
} );

/* ---------- apagar lead (caso precise) ---------- */
add_action( 'admin_init', function() {
    if ( empty( $_GET['orick_ferr_delete'] ) ) return;
    if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Sem permissão.' );
    $id = (int) $_GET['orick_ferr_delete'];
    check_admin_referer( 'orick_ferr_delete_' . $id );
    global $wpdb;
    $wpdb->delete( $wpdb->prefix . ORICK_FERR_TABLE, [ 'id' => $id ] );
    wp_safe_redirect( add_query_arg( 'deleted', '1', remove_query_arg( [ 'orick_ferr_delete', '_wpnonce' ] ) ) );
    exit;
} );

/* ---------- helper: WHERE SQL dinâmico ---------- */
function orick_ferr_leads_where_sql() {
    global $wpdb;
    $where = [ '1=1' ];
    if ( ! empty( $_GET['s'] ) ) {
        $s = '%' . $wpdb->esc_like( sanitize_text_field( $_GET['s'] ) ) . '%';
        $where[] = $wpdb->prepare( '( nome LIKE %s OR email LIKE %s OR cpf LIKE %s OR telefone LIKE %s )', $s, $s, $s, $s );
    }
    if ( ! empty( $_GET['prof'] ) ) {
        $where[] = $wpdb->prepare( 'profissao = %s', sanitize_text_field( $_GET['prof'] ) );
    }
    if ( ! empty( $_GET['aum_min'] ) ) {
        $where[] = $wpdb->prepare( 'aum_atendido >= %f', (float) $_GET['aum_min'] );
    }
    if ( ! empty( $_GET['from'] ) ) {
        $where[] = $wpdb->prepare( 'created_at >= %s', sanitize_text_field( $_GET['from'] ) . ' 00:00:00' );
    }
    if ( ! empty( $_GET['to'] ) ) {
        $where[] = $wpdb->prepare( 'created_at <= %s', sanitize_text_field( $_GET['to'] ) . ' 23:59:59' );
    }
    return implode( ' AND ', $where );
}

/* ---------- tela principal ---------- */
function orick_ferr_leads_page() {
    global $wpdb;
    $table = $wpdb->prefix . ORICK_FERR_TABLE;

    $where = orick_ferr_leads_where_sql();

    // Paginação
    $per_page = 30;
    $paged = max( 1, (int) ( $_GET['paged'] ?? 1 ) );
    $offset = ( $paged - 1 ) * $per_page;

    $total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE $where" );
    $rows = $wpdb->get_results( "SELECT * FROM $table WHERE $where ORDER BY created_at DESC LIMIT $per_page OFFSET $offset" );
    $total_pages = max( 1, ceil( $total / $per_page ) );

    // Totais por profissão
    $totais_prof = $wpdb->get_results( "SELECT profissao, COUNT(*) c FROM $table GROUP BY profissao" );
    $aum_total = (float) $wpdb->get_var( "SELECT SUM(aum_atendido) FROM $table" );

    $profissoes = [
        'assessor'   => 'Assessor de Investimentos',
        'consultor'  => 'Consultor Financeiro',
        'bancario'   => 'Bancário',
        'planejador' => 'Planejador Financeiro',
        'outra'      => 'Outra',
    ];
    ?>
    <div class="wrap">
      <h1>Leads capturados
        <a href="<?php echo wp_nonce_url( add_query_arg( 'orick_ferr_export', 'csv' ), 'orick_ferr_export_csv' ); ?>" class="page-title-action">Exportar CSV</a>
      </h1>

      <?php if ( ! empty( $_GET['retried'] ) ) echo '<div class="notice notice-success is-dismissible"><p>Webhook reenviado.</p></div>'; ?>
      <?php if ( ! empty( $_GET['deleted'] ) ) echo '<div class="notice notice-success is-dismissible"><p>Lead apagado.</p></div>'; ?>

      <div style="display:flex; gap:24px; margin: 18px 0; flex-wrap:wrap;">
        <div style="background:#fff; border:1px solid #ddd; padding:14px 20px; min-width:140px;">
          <div style="font-size:11px; color:#666; text-transform:uppercase; font-weight:600;">Total de leads</div>
          <div style="font-size:28px; font-weight:600; margin-top:4px;"><?php echo number_format( $total, 0, ',', '.' ); ?></div>
        </div>
        <div style="background:#fff; border:1px solid #ddd; padding:14px 20px; min-width:180px;">
          <div style="font-size:11px; color:#666; text-transform:uppercase; font-weight:600;">AuM declarado total</div>
          <div style="font-size:28px; font-weight:600; margin-top:4px;">R$ <?php echo number_format( $aum_total, 0, ',', '.' ); ?></div>
        </div>
        <?php foreach ( $totais_prof as $tp ) : ?>
          <div style="background:#fff; border:1px solid #ddd; padding:14px 20px; min-width:140px;">
            <div style="font-size:11px; color:#666; text-transform:uppercase; font-weight:600;"><?php echo esc_html( $profissoes[ $tp->profissao ] ?? $tp->profissao ); ?></div>
            <div style="font-size:24px; font-weight:600; margin-top:4px;"><?php echo number_format( $tp->c, 0, ',', '.' ); ?></div>
          </div>
        <?php endforeach; ?>
      </div>

      <form method="get" style="background:#fff; border:1px solid #ddd; padding:14px; margin-bottom: 16px;">
        <input type="hidden" name="post_type" value="ferramenta">
        <input type="hidden" name="page" value="orick-ferr-leads">
        <div style="display:flex; gap:10px; flex-wrap:wrap; align-items:end;">
          <div><label style="display:block;font-size:12px;">Busca (nome / email / CPF / telefone)</label><input type="search" name="s" value="<?php echo esc_attr( $_GET['s'] ?? '' ); ?>" style="width:260px;"></div>
          <div><label style="display:block;font-size:12px;">Profissão</label>
            <select name="prof">
              <option value="">Todas</option>
              <?php foreach ( $profissoes as $k => $label ) : ?>
                <option value="<?php echo $k; ?>" <?php selected( $_GET['prof'] ?? '', $k ); ?>><?php echo $label; ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div><label style="display:block;font-size:12px;">AuM mínimo (R$)</label><input type="number" name="aum_min" value="<?php echo esc_attr( $_GET['aum_min'] ?? '' ); ?>" step="10000" style="width:160px;"></div>
          <div><label style="display:block;font-size:12px;">De</label><input type="date" name="from" value="<?php echo esc_attr( $_GET['from'] ?? '' ); ?>"></div>
          <div><label style="display:block;font-size:12px;">Até</label><input type="date" name="to" value="<?php echo esc_attr( $_GET['to'] ?? '' ); ?>"></div>
          <div><button class="button button-primary">Filtrar</button> <a href="?post_type=ferramenta&page=orick-ferr-leads" class="button">Limpar</a></div>
        </div>
      </form>

      <table class="wp-list-table widefat striped">
        <thead>
          <tr>
            <th>#</th><th>Nome</th><th>E-mail</th><th>Telefone</th><th>CPF</th><th>Profissão</th><th>AuM</th><th>Webhook</th><th>Cadastro</th><th>Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php if ( ! $rows ) : ?>
            <tr><td colspan="10" style="text-align:center; padding: 30px; color:#666;">Nenhum lead encontrado.</td></tr>
          <?php else: foreach ( $rows as $r ) : ?>
            <tr>
              <td><?php echo $r->id; ?></td>
              <td><strong><?php echo esc_html( $r->nome ); ?></strong></td>
              <td><a href="mailto:<?php echo esc_attr( $r->email ); ?>"><?php echo esc_html( $r->email ); ?></a></td>
              <td><?php echo esc_html( $r->telefone ); ?></td>
              <td><?php echo esc_html( $r->cpf ); ?></td>
              <td><?php echo esc_html( $profissoes[ $r->profissao ] ?? $r->profissao ); ?><?php echo $r->profissao_outra ? ' <em>(' . esc_html( $r->profissao_outra ) . ')</em>' : ''; ?></td>
              <td><?php echo $r->aum_atendido ? 'R$ ' . number_format( $r->aum_atendido, 0, ',', '.' ) : '—'; ?></td>
              <td>
                <?php
                $icons = [ 'success' => '✅', 'error' => '❌', 'pending' => '⏳' ];
                echo $icons[ $r->webhook_status ] ?? '—';
                echo ' ' . esc_html( $r->webhook_status );
                if ( $r->webhook_response ) echo '<div style="font-size:10px;color:#999;max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="' . esc_attr( $r->webhook_response ) . '">' . esc_html( substr( $r->webhook_response, 0, 40 ) ) . '</div>';
                ?>
              </td>
              <td><?php echo esc_html( mysql2date( 'd/m/Y H:i', $r->created_at ) ); ?></td>
              <td>
                <a href="<?php echo wp_nonce_url( add_query_arg( 'orick_ferr_retry', $r->id ), 'orick_ferr_retry_' . $r->id ); ?>" class="button button-small">↻ Webhook</a>
                <a href="<?php echo wp_nonce_url( add_query_arg( 'orick_ferr_delete', $r->id ), 'orick_ferr_delete_' . $r->id ); ?>" class="button button-small button-link-delete" onclick="return confirm('Apagar este lead permanentemente?');">Apagar</a>
              </td>
            </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>

      <?php if ( $total_pages > 1 ) : ?>
        <div class="tablenav bottom" style="margin-top:12px;">
          <div class="tablenav-pages">
            <?php echo paginate_links( [
                'base'      => add_query_arg( 'paged', '%#%' ),
                'format'    => '',
                'prev_text' => '‹',
                'next_text' => '›',
                'total'     => $total_pages,
                'current'   => $paged,
            ] ); ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
    <?php
}

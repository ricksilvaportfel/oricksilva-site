<?php
/**
 * Install / activation — cria tabela orick_leads e seta opções default.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Orick_Ferr_Install {

    public static function activate() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();
        $table = $wpdb->prefix . ORICK_FERR_TABLE;

        $sql = "CREATE TABLE $table (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            email VARCHAR(190) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            nome VARCHAR(190) NOT NULL,
            telefone VARCHAR(30) NOT NULL,
            cpf VARCHAR(14) NOT NULL,
            profissao VARCHAR(80) NOT NULL,
            profissao_outra VARCHAR(120) DEFAULT '',
            aum_atendido DECIMAL(18,2) DEFAULT NULL,
            ip_cadastro VARCHAR(45) DEFAULT '',
            user_agent VARCHAR(255) DEFAULT '',
            webhook_status VARCHAR(20) DEFAULT 'pending',
            webhook_response TEXT,
            created_at DATETIME NOT NULL,
            last_login_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY email_unique (email),
            KEY cpf_idx (cpf),
            KEY profissao_idx (profissao),
            KEY created_idx (created_at)
        ) $charset;";

        // Tabela de estado das ferramentas por lead (pra salvar configurações do simulador no "perfil" do lead)
        $table_state = $wpdb->prefix . 'orick_lead_tool_state';
        $sql_state = "CREATE TABLE $table_state (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            lead_id BIGINT(20) UNSIGNED NOT NULL,
            tool_slug VARCHAR(80) NOT NULL,
            data LONGTEXT NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY lead_tool (lead_id, tool_slug),
            KEY lead_idx (lead_id)
        ) $charset;";

        // Tabela de downloads de materiais (log por lead)
        $table_dl = $wpdb->prefix . 'orick_lead_downloads';
        $sql_dl = "CREATE TABLE $table_dl (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            lead_id BIGINT(20) UNSIGNED NOT NULL,
            material_id BIGINT(20) UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY lead_idx (lead_id),
            KEY material_idx (material_id)
        ) $charset;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
        dbDelta( $sql_state );
        dbDelta( $sql_dl );

        // Default options
        add_option( 'orick_ferr_webhook_url', '' );
        add_option( 'orick_ferr_webhook_secret', wp_generate_password( 32, false ) );
        add_option( 'orick_ferr_auth_secret', wp_generate_password( 64, true ) );
        add_option( 'orick_ferr_session_days', 365 );

        // Flush rewrite — CPT registrado em init
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }
}

<?php
/**
 * Autenticação custom para leads de Ferramentas.
 * — Não usa wp_users: tabela própria orick_leads.
 * — Senha: wp_hash_password() + wp_check_password() (bcrypt-like).
 * — Sessão: cookie HMAC assinado com orick_ferr_auth_secret.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Orick_Ferr_Auth {

    const COOKIE_NAME = 'orick_ferr_session';

    /* ---------- VALIDAÇÃO CPF (dígito verificador real) ---------- */
    public static function validate_cpf( $cpf ) {
        $cpf = preg_replace( '/\D/', '', $cpf );
        if ( strlen( $cpf ) !== 11 ) return false;
        // Rejeita CPFs com todos dígitos iguais (111.111.111-11, etc)
        if ( preg_match( '/(\d)\1{10}/', $cpf ) ) return false;

        for ( $t = 9; $t < 11; $t++ ) {
            $d = 0;
            for ( $c = 0; $c < $t; $c++ ) {
                $d += $cpf[ $c ] * ( ( $t + 1 ) - $c );
            }
            $d = ( ( 10 * $d ) % 11 ) % 10;
            if ( $cpf[ $c ] != $d ) return false;
        }
        return $cpf;
    }

    /* ---------- REGISTRO ---------- */
    public static function register_lead( $data ) {
        global $wpdb;
        $table = $wpdb->prefix . ORICK_FERR_TABLE;

        $errors = [];

        // Sanitize
        $email = sanitize_email( $data['email'] ?? '' );
        $senha = (string) ( $data['senha'] ?? '' );
        $senha_confirm = (string) ( $data['senha_confirm'] ?? '' );
        $nome = sanitize_text_field( $data['nome'] ?? '' );
        $telefone = preg_replace( '/\D/', '', $data['telefone'] ?? '' );
        $cpf_raw = $data['cpf'] ?? '';
        $profissao = sanitize_text_field( $data['profissao'] ?? '' );
        $profissao_outra = sanitize_text_field( $data['profissao_outra'] ?? '' );
        $aum_raw = $data['aum_atendido'] ?? '';
        $aum = self::parse_money( $aum_raw );

        // Validação
        if ( empty( $nome ) || mb_strlen( $nome ) < 3 ) $errors[] = 'Nome inválido.';
        if ( ! is_email( $email ) ) $errors[] = 'E-mail inválido.';
        if ( strlen( $telefone ) < 10 ) $errors[] = 'Telefone inválido (use DDD + número).';

        $cpf = self::validate_cpf( $cpf_raw );
        if ( ! $cpf ) $errors[] = 'CPF inválido.';

        $profissoes_com_aum = [ 'assessor', 'consultor', 'bancario', 'planejador' ];
        if ( empty( $profissao ) ) $errors[] = 'Selecione a profissão.';
        if ( $profissao === 'outra' && empty( $profissao_outra ) ) $errors[] = 'Especifique a profissão.';
        if ( in_array( $profissao, $profissoes_com_aum, true ) && ! $aum ) $errors[] = 'Informe o AuM atendido atualmente.';

        if ( strlen( $senha ) < 8 ) $errors[] = 'Senha deve ter no mínimo 8 caracteres.';
        if ( $senha !== $senha_confirm ) $errors[] = 'As senhas não conferem.';

        // Email já existe?
        if ( ! $errors ) {
            $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE email = %s LIMIT 1", $email ) );
            if ( $exists ) $errors[] = 'Este e-mail já está cadastrado. <a href="' . esc_url( self::login_url() ) . '">Fazer login</a>.';
        }

        if ( $errors ) return [ 'ok' => false, 'errors' => $errors ];

        // Insere
        $wpdb->insert( $table, [
            'email'           => $email,
            'password_hash'   => wp_hash_password( $senha ),
            'nome'            => $nome,
            'telefone'        => $telefone,
            'cpf'             => $cpf,
            'profissao'       => $profissao,
            'profissao_outra' => $profissao === 'outra' ? $profissao_outra : '',
            'aum_atendido'    => $aum,
            'ip_cadastro'     => self::client_ip(),
            'user_agent'      => substr( $_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255 ),
            'created_at'      => current_time( 'mysql' ),
            'last_login_at'   => current_time( 'mysql' ),
        ] );

        $lead_id = $wpdb->insert_id;
        if ( ! $lead_id ) return [ 'ok' => false, 'errors' => [ 'Erro ao salvar cadastro. Tente novamente.' ] ];

        // Webhook (dispara em background, não bloqueia)
        do_action( 'orick_ferr_after_register', $lead_id );

        // Loga
        self::set_cookie( $lead_id );

        return [ 'ok' => true, 'lead_id' => $lead_id ];
    }

    /* ---------- LOGIN ---------- */
    public static function login( $email, $senha ) {
        global $wpdb;
        $table = $wpdb->prefix . ORICK_FERR_TABLE;

        $email = sanitize_email( $email );
        if ( ! is_email( $email ) ) return [ 'ok' => false, 'errors' => [ 'E-mail inválido.' ] ];

        $lead = $wpdb->get_row( $wpdb->prepare( "SELECT id, password_hash FROM $table WHERE email = %s LIMIT 1", $email ) );
        if ( ! $lead ) return [ 'ok' => false, 'errors' => [ 'E-mail ou senha incorretos.' ] ];

        if ( ! wp_check_password( $senha, $lead->password_hash ) ) {
            return [ 'ok' => false, 'errors' => [ 'E-mail ou senha incorretos.' ] ];
        }

        $wpdb->update( $table, [ 'last_login_at' => current_time( 'mysql' ) ], [ 'id' => $lead->id ] );
        self::set_cookie( $lead->id );
        return [ 'ok' => true, 'lead_id' => $lead->id ];
    }

    /* ---------- LOGOUT ---------- */
    public static function logout() {
        setcookie( self::COOKIE_NAME, '', time() - 3600, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
        unset( $_COOKIE[ self::COOKIE_NAME ] );
    }

    /* ---------- SESSÃO ---------- */
    public static function current_lead_id() {
        if ( empty( $_COOKIE[ self::COOKIE_NAME ] ) ) return 0;
        $parts = explode( '|', $_COOKIE[ self::COOKIE_NAME ] );
        if ( count( $parts ) !== 3 ) return 0;
        [ $lead_id, $expires, $sig ] = $parts;
        if ( time() > (int) $expires ) return 0;
        $expected = hash_hmac( 'sha256', $lead_id . '|' . $expires, get_option( 'orick_ferr_auth_secret' ) );
        if ( ! hash_equals( $expected, $sig ) ) return 0;
        return (int) $lead_id;
    }

    public static function is_logged_in() {
        return self::current_lead_id() > 0;
    }

    public static function current_lead() {
        $id = self::current_lead_id();
        if ( ! $id ) return null;
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}" . ORICK_FERR_TABLE . " WHERE id = %d",
            $id
        ) );
    }

    protected static function set_cookie( $lead_id ) {
        $days = (int) get_option( 'orick_ferr_session_days', 365 );
        $expires = time() + ( $days * DAY_IN_SECONDS );
        $sig = hash_hmac( 'sha256', $lead_id . '|' . $expires, get_option( 'orick_ferr_auth_secret' ) );
        $value = $lead_id . '|' . $expires . '|' . $sig;
        setcookie( self::COOKIE_NAME, $value, [
            'expires'  => $expires,
            'path'     => COOKIEPATH,
            'domain'   => COOKIE_DOMAIN,
            'secure'   => is_ssl(),
            'httponly' => true,
            'samesite' => 'Lax',
        ] );
        $_COOKIE[ self::COOKIE_NAME ] = $value;
    }

    /* ---------- URLs canônicas ---------- */
    public static function login_url( $redirect_to = '' ) {
        $u = add_query_arg( 'orick_action', 'login', home_url( '/ferramentas/' ) );
        if ( $redirect_to ) $u = add_query_arg( 'redirect_to', urlencode( $redirect_to ), $u );
        return $u;
    }

    public static function register_url( $redirect_to = '' ) {
        $u = add_query_arg( 'orick_action', 'cadastro', home_url( '/ferramentas/' ) );
        if ( $redirect_to ) $u = add_query_arg( 'redirect_to', urlencode( $redirect_to ), $u );
        return $u;
    }

    /* ---------- Helpers ---------- */
    public static function client_ip() {
        foreach ( [ 'HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ] as $h ) {
            if ( ! empty( $_SERVER[ $h ] ) ) {
                $ip = explode( ',', $_SERVER[ $h ] )[0];
                return trim( $ip );
            }
        }
        return '';
    }

    public static function parse_money( $val ) {
        if ( empty( $val ) ) return null;
        // Aceita "R$ 1.000.000,00", "1000000.00", "1.000.000"
        $s = preg_replace( '/[^\d,\.]/', '', (string) $val );
        // Se tem vírgula depois de ponto → formato BR: 1.000.000,00
        if ( strrpos( $s, ',' ) > strrpos( $s, '.' ) ) {
            $s = str_replace( '.', '', $s );
            $s = str_replace( ',', '.', $s );
        } else {
            // Formato US: 1,000,000.00
            $s = str_replace( ',', '', $s );
        }
        $f = (float) $s;
        return $f > 0 ? $f : null;
    }
}

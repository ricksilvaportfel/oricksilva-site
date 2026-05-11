<?php
/**
 * Formulários (cadastro/login) + handler de POST.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ---------- POST HANDLER ---------- */
add_action( 'init', function() {
    if ( empty( $_POST['orick_ferr_action'] ) ) return;

    $action = sanitize_key( $_POST['orick_ferr_action'] );
    $nonce  = $_POST['_orick_nonce'] ?? '';

    if ( $action === 'cadastro' ) {
        if ( ! wp_verify_nonce( $nonce, 'orick_ferr_cadastro' ) ) { wp_die( 'Nonce inválido.' ); }
        $result = Orick_Ferr_Auth::register_lead( $_POST );
        if ( $result['ok'] ) {
            $redirect = esc_url_raw( $_POST['redirect_to'] ?? home_url( '/ferramentas/' ) );
            wp_safe_redirect( $redirect );
            exit;
        } else {
            set_transient( 'orick_ferr_errors_' . self_session_key(), $result['errors'], 60 );
            set_transient( 'orick_ferr_old_' . self_session_key(), $_POST, 60 );
            wp_safe_redirect( add_query_arg( 'orick_action', 'cadastro', wp_get_referer() ?: home_url( '/ferramentas/' ) ) );
            exit;
        }
    }

    if ( $action === 'login' ) {
        if ( ! wp_verify_nonce( $nonce, 'orick_ferr_login' ) ) { wp_die( 'Nonce inválido.' ); }
        $result = Orick_Ferr_Auth::login( $_POST['email'] ?? '', $_POST['senha'] ?? '' );
        if ( $result['ok'] ) {
            $redirect = esc_url_raw( $_POST['redirect_to'] ?? home_url( '/ferramentas/' ) );
            wp_safe_redirect( $redirect );
            exit;
        } else {
            set_transient( 'orick_ferr_errors_' . self_session_key(), $result['errors'], 60 );
            wp_safe_redirect( add_query_arg( 'orick_action', 'login', wp_get_referer() ?: home_url( '/ferramentas/' ) ) );
            exit;
        }
    }

    if ( $action === 'logout' ) {
        Orick_Ferr_Auth::logout();
        wp_safe_redirect( home_url( '/ferramentas/' ) );
        exit;
    }
} );

/* cria uma key fraca pra associar transients ao visitante sem sessão */
function self_session_key() {
    $seed = ( $_SERVER['REMOTE_ADDR'] ?? '' ) . ( $_SERVER['HTTP_USER_AGENT'] ?? '' );
    return substr( md5( $seed ), 0, 12 );
}

/* helper pra ler erros transients */
function orick_ferr_get_errors() {
    $key = self_session_key();
    $errs = get_transient( 'orick_ferr_errors_' . $key );
    if ( $errs ) delete_transient( 'orick_ferr_errors_' . $key );
    return $errs ?: [];
}

function orick_ferr_get_old( $field, $default = '' ) {
    $key = self_session_key();
    $old = get_transient( 'orick_ferr_old_' . $key );
    if ( $old && isset( $old[ $field ] ) ) return esc_attr( $old[ $field ] );
    return $default;
}

/* ---------- RENDER: FORM DE CADASTRO ---------- */
function orick_ferr_render_cadastro( $args = [] ) {
    $redirect_to = esc_url( $args['redirect_to'] ?? home_url( '/ferramentas/' ) );
    $errors = orick_ferr_get_errors();
    $context_title = esc_html( $args['context_title'] ?? 'Crie sua conta para acessar as ferramentas' );
    $context_sub = esc_html( $args['context_sub'] ?? 'Preencha os dados abaixo. O acesso é permanente e gratuito.' );

    ob_start(); ?>
    <div class="ofr-auth-shell">
      <div class="ofr-auth-card">
        <span class="ofr-auth-kicker">CADASTRO</span>
        <h2 class="ofr-auth-title"><?php echo $context_title; ?></h2>
        <p class="ofr-auth-sub"><?php echo $context_sub; ?></p>

        <?php if ( $errors ) : ?>
          <div class="ofr-alert ofr-alert-error">
            <?php foreach ( $errors as $e ) echo '<div>• ' . wp_kses_post( $e ) . '</div>'; ?>
          </div>
        <?php endif; ?>

        <form method="post" class="ofr-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
          <?php wp_nonce_field( 'orick_ferr_cadastro', '_orick_nonce' ); ?>
          <input type="hidden" name="orick_ferr_action" value="cadastro">
          <input type="hidden" name="redirect_to" value="<?php echo $redirect_to; ?>">

          <div class="ofr-field">
            <label>Nome completo</label>
            <input type="text" name="nome" required autocomplete="name" value="<?php echo orick_ferr_get_old( 'nome' ); ?>">
          </div>

          <div class="ofr-row">
            <div class="ofr-field">
              <label>E-mail</label>
              <input type="email" name="email" required autocomplete="email" value="<?php echo orick_ferr_get_old( 'email' ); ?>">
            </div>
            <div class="ofr-field">
              <label>Telefone</label>
              <input type="tel" name="telefone" required autocomplete="tel" placeholder="(11) 99999-9999" value="<?php echo orick_ferr_get_old( 'telefone' ); ?>" data-mask="phone">
            </div>
          </div>

          <div class="ofr-row">
            <div class="ofr-field">
              <label>CPF</label>
              <input type="text" name="cpf" required placeholder="000.000.000-00" value="<?php echo orick_ferr_get_old( 'cpf' ); ?>" data-mask="cpf">
            </div>
            <div class="ofr-field">
              <label>Profissão</label>
              <select name="profissao" required id="ofr-profissao">
                <option value="">Selecione...</option>
                <option value="assessor" <?php selected( orick_ferr_get_old( 'profissao' ), 'assessor' ); ?>>Assessor de Investimentos</option>
                <option value="consultor" <?php selected( orick_ferr_get_old( 'profissao' ), 'consultor' ); ?>>Consultor Financeiro</option>
                <option value="bancario" <?php selected( orick_ferr_get_old( 'profissao' ), 'bancario' ); ?>>Bancário</option>
                <option value="planejador" <?php selected( orick_ferr_get_old( 'profissao' ), 'planejador' ); ?>>Planejador Financeiro</option>
                <option value="outra" <?php selected( orick_ferr_get_old( 'profissao' ), 'outra' ); ?>>Outra</option>
              </select>
            </div>
          </div>

          <div class="ofr-field" id="ofr-profissao-outra-wrap" style="display:none;">
            <label>Qual profissão?</label>
            <input type="text" name="profissao_outra" value="<?php echo orick_ferr_get_old( 'profissao_outra' ); ?>">
          </div>

          <div class="ofr-field" id="ofr-aum-wrap" style="display:none;">
            <label>AuM atendido atualmente (R$)</label>
            <input type="text" name="aum_atendido" placeholder="R$ 1.000.000,00" value="<?php echo orick_ferr_get_old( 'aum_atendido' ); ?>" data-mask="money">
            <small class="ofr-hint">Patrimônio sob gestão/consultoria total dos seus clientes atualmente.</small>
          </div>

          <div class="ofr-row">
            <div class="ofr-field">
              <label>Senha</label>
              <input type="password" name="senha" required minlength="8" autocomplete="new-password">
              <small class="ofr-hint">Mínimo 8 caracteres.</small>
            </div>
            <div class="ofr-field">
              <label>Confirmar senha</label>
              <input type="password" name="senha_confirm" required minlength="8" autocomplete="new-password">
            </div>
          </div>

          <button type="submit" class="ofr-btn ofr-btn-primary">Criar conta e acessar</button>

          <p class="ofr-auth-alt">
            Já tem conta? <a href="<?php echo esc_url( Orick_Ferr_Auth::login_url( $redirect_to ) ); ?>">Faça login</a>
          </p>
        </form>
      </div>
    </div>
    <?php
    return ob_get_clean();
}

/* ---------- RENDER: FORM DE LOGIN ---------- */
function orick_ferr_render_login( $args = [] ) {
    $redirect_to = esc_url( $args['redirect_to'] ?? home_url( '/ferramentas/' ) );
    $errors = orick_ferr_get_errors();
    $context_title = esc_html( $args['context_title'] ?? 'Entrar' );
    $context_sub = esc_html( $args['context_sub'] ?? 'Acesse sua conta para usar as ferramentas.' );

    ob_start(); ?>
    <div class="ofr-auth-shell">
      <div class="ofr-auth-card ofr-auth-card-narrow">
        <span class="ofr-auth-kicker">LOGIN</span>
        <h2 class="ofr-auth-title"><?php echo $context_title; ?></h2>
        <p class="ofr-auth-sub"><?php echo $context_sub; ?></p>

        <?php if ( $errors ) : ?>
          <div class="ofr-alert ofr-alert-error">
            <?php foreach ( $errors as $e ) echo '<div>• ' . wp_kses_post( $e ) . '</div>'; ?>
          </div>
        <?php endif; ?>

        <form method="post" class="ofr-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
          <?php wp_nonce_field( 'orick_ferr_login', '_orick_nonce' ); ?>
          <input type="hidden" name="orick_ferr_action" value="login">
          <input type="hidden" name="redirect_to" value="<?php echo $redirect_to; ?>">

          <div class="ofr-field">
            <label>E-mail</label>
            <input type="email" name="email" required autocomplete="email">
          </div>
          <div class="ofr-field">
            <label>Senha</label>
            <input type="password" name="senha" required autocomplete="current-password">
          </div>

          <button type="submit" class="ofr-btn ofr-btn-primary">Entrar</button>

          <p class="ofr-auth-alt">
            Não tem conta? <a href="<?php echo esc_url( Orick_Ferr_Auth::register_url( $redirect_to ) ); ?>">Criar cadastro</a>
          </p>
        </form>
      </div>
    </div>
    <?php
    return ob_get_clean();
}

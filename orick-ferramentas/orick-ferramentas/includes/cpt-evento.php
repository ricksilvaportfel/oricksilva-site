<?php
/**
 * CPT: Evento
 *
 * Campos: data, horário, local (físico ou online), preço/gratuito, link de inscrição, status.
 * Archive separa Próximos vs Passados.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Orick_Ferr_CPT_Evento {

    const POST_TYPE = 'evento';

    public static function init() {
        add_action( 'init', [ __CLASS__, 'register' ] );
        add_action( 'add_meta_boxes', [ __CLASS__, 'metabox' ] );
        add_action( 'save_post_' . self::POST_TYPE, [ __CLASS__, 'save' ] );
        add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', [ __CLASS__, 'columns' ] );
        add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', [ __CLASS__, 'column_content' ], 10, 2 );
    }

    public static function register() {
        register_post_type( self::POST_TYPE, [
            'labels' => [
                'name'          => 'Eventos',
                'singular_name' => 'Evento',
                'add_new'       => 'Adicionar novo',
                'add_new_item'  => 'Adicionar evento',
                'menu_name'     => 'Eventos',
            ],
            'public'        => true,
            'show_ui'       => true,
            'menu_position' => 29,
            'menu_icon'     => 'dashicons-calendar-alt',
            'has_archive'   => 'eventos',
            'rewrite'       => [ 'slug' => 'eventos', 'with_front' => false ],
            'supports'      => [ 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ],
            'taxonomies'    => [ 'post_tag', 'category' ],
            'show_in_rest'  => false,
        ] );
    }

    public static function metabox() {
        add_meta_box( 'orick_evento_box', 'Configurações do evento', [ __CLASS__, 'render' ], self::POST_TYPE, 'normal', 'high' );
    }

    public static function render( $post ) {
        wp_nonce_field( 'orick_evento_save', 'orick_evento_nonce' );
        $data       = get_post_meta( $post->ID, '_orick_ev_data', true );
        $hora_ini   = get_post_meta( $post->ID, '_orick_ev_hora_ini', true );
        $hora_fim   = get_post_meta( $post->ID, '_orick_ev_hora_fim', true );
        $formato    = get_post_meta( $post->ID, '_orick_ev_formato', true ) ?: 'presencial';
        $local      = get_post_meta( $post->ID, '_orick_ev_local', true );
        $cidade     = get_post_meta( $post->ID, '_orick_ev_cidade', true );
        $preco      = get_post_meta( $post->ID, '_orick_ev_preco', true );
        $gratuito   = get_post_meta( $post->ID, '_orick_ev_gratuito', true );
        $link_insc  = get_post_meta( $post->ID, '_orick_ev_link_inscricao', true );
        $status     = get_post_meta( $post->ID, '_orick_ev_status', true ) ?: 'abertas';
        ?>
        <style>.orick-ev .row{margin:12px 0}.orick-ev label{display:block;font-weight:600;margin-bottom:4px}.orick-ev input,.orick-ev select{width:100%}.orick-ev .grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px}</style>
        <div class="orick-ev">
          <div class="grid">
            <div class="row">
              <label>Data do evento *</label>
              <input type="date" name="orick_ev_data" value="<?php echo esc_attr( $data ); ?>" required>
            </div>
            <div class="row">
              <label>Horário início</label>
              <input type="time" name="orick_ev_hora_ini" value="<?php echo esc_attr( $hora_ini ); ?>">
            </div>
            <div class="row">
              <label>Horário fim</label>
              <input type="time" name="orick_ev_hora_fim" value="<?php echo esc_attr( $hora_fim ); ?>">
            </div>
          </div>

          <div class="row">
            <label>Formato</label>
            <select name="orick_ev_formato">
              <option value="presencial" <?php selected( $formato, 'presencial' ); ?>>Presencial</option>
              <option value="online" <?php selected( $formato, 'online' ); ?>>Online</option>
              <option value="hibrido" <?php selected( $formato, 'hibrido' ); ?>>Híbrido</option>
            </select>
          </div>

          <div class="grid" style="grid-template-columns:2fr 1fr">
            <div class="row">
              <label>Local (endereço ou plataforma)</label>
              <input type="text" name="orick_ev_local" value="<?php echo esc_attr( $local ); ?>" placeholder="Ex.: Auditório XP, Zoom, YouTube ao vivo...">
            </div>
            <div class="row">
              <label>Cidade/UF</label>
              <input type="text" name="orick_ev_cidade" value="<?php echo esc_attr( $cidade ); ?>" placeholder="São Paulo/SP">
            </div>
          </div>

          <div class="grid" style="grid-template-columns:auto 1fr">
            <div class="row">
              <label><input type="checkbox" name="orick_ev_gratuito" value="1" <?php checked( $gratuito, '1' ); ?>> Evento gratuito</label>
            </div>
            <div class="row">
              <label>Preço (se pago)</label>
              <input type="text" name="orick_ev_preco" value="<?php echo esc_attr( $preco ); ?>" placeholder="R$ 490,00 ou 'A partir de R$ 290'">
            </div>
          </div>

          <div class="row">
            <label>Link de inscrição *</label>
            <input type="text" name="orick_ev_link_inscricao" value="<?php echo esc_attr( $link_insc ); ?>" placeholder="https://..." required>
          </div>

          <div class="row">
            <label>Status</label>
            <select name="orick_ev_status">
              <option value="em_breve" <?php selected( $status, 'em_breve' ); ?>>Em breve</option>
              <option value="abertas" <?php selected( $status, 'abertas' ); ?>>Inscrições abertas</option>
              <option value="ultimas" <?php selected( $status, 'ultimas' ); ?>>Últimas vagas</option>
              <option value="encerradas" <?php selected( $status, 'encerradas' ); ?>>Inscrições encerradas</option>
              <option value="finalizado" <?php selected( $status, 'finalizado' ); ?>>Finalizado</option>
            </select>
          </div>
        </div>
        <?php
    }

    public static function save( $post_id ) {
        if ( ! isset( $_POST['orick_evento_nonce'] ) ) return;
        if ( ! wp_verify_nonce( $_POST['orick_evento_nonce'], 'orick_evento_save' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        update_post_meta( $post_id, '_orick_ev_data', sanitize_text_field( $_POST['orick_ev_data'] ?? '' ) );
        update_post_meta( $post_id, '_orick_ev_hora_ini', sanitize_text_field( $_POST['orick_ev_hora_ini'] ?? '' ) );
        update_post_meta( $post_id, '_orick_ev_hora_fim', sanitize_text_field( $_POST['orick_ev_hora_fim'] ?? '' ) );
        update_post_meta( $post_id, '_orick_ev_formato', sanitize_key( $_POST['orick_ev_formato'] ?? 'presencial' ) );
        update_post_meta( $post_id, '_orick_ev_local', sanitize_text_field( $_POST['orick_ev_local'] ?? '' ) );
        update_post_meta( $post_id, '_orick_ev_cidade', sanitize_text_field( $_POST['orick_ev_cidade'] ?? '' ) );
        update_post_meta( $post_id, '_orick_ev_preco', sanitize_text_field( $_POST['orick_ev_preco'] ?? '' ) );
        update_post_meta( $post_id, '_orick_ev_gratuito', ! empty( $_POST['orick_ev_gratuito'] ) ? '1' : '0' );
        update_post_meta( $post_id, '_orick_ev_link_inscricao', esc_url_raw( $_POST['orick_ev_link_inscricao'] ?? '' ) );
        update_post_meta( $post_id, '_orick_ev_status', sanitize_key( $_POST['orick_ev_status'] ?? 'abertas' ) );
    }

    public static function columns( $cols ) {
        $new = [];
        foreach ( $cols as $k => $v ) {
            $new[ $k ] = $v;
            if ( $k === 'title' ) {
                $new['ev_data']   = 'Data';
                $new['ev_status'] = 'Status';
            }
        }
        return $new;
    }

    public static function column_content( $col, $post_id ) {
        if ( $col === 'ev_data' ) {
            $d = get_post_meta( $post_id, '_orick_ev_data', true );
            echo $d ? esc_html( date_i18n( 'd/m/Y', strtotime( $d ) ) ) : '—';
        }
        if ( $col === 'ev_status' ) {
            $s = get_post_meta( $post_id, '_orick_ev_status', true );
            $map = [ 'em_breve' => 'Em breve', 'abertas' => 'Abertas', 'ultimas' => 'Últimas vagas', 'encerradas' => 'Encerradas', 'finalizado' => 'Finalizado' ];
            echo esc_html( $map[ $s ] ?? '—' );
        }
    }

    /** Helper: é evento futuro? */
    public static function is_upcoming( $post_id ) {
        $data = get_post_meta( $post_id, '_orick_ev_data', true );
        if ( ! $data ) return false;
        return strtotime( $data ) >= strtotime( 'today' );
    }
}

Orick_Ferr_CPT_Evento::init();

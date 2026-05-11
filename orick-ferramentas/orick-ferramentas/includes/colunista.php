<?php
/**
 * Colunistas — campos extras no perfil do usuário WordPress
 *
 * Adiciona aos perfis: cargo, bio (já existe nativo), redes sociais, periodicidade, tag única.
 * Cria role customizada 'colunista' se não existir.
 * Filtra autores pro archive /colunistas/.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Orick_Ferr_Colunista {

    public static function init() {
        add_action( 'init', [ __CLASS__, 'ensure_role' ] );
        add_action( 'show_user_profile', [ __CLASS__, 'render_fields' ] );
        add_action( 'edit_user_profile', [ __CLASS__, 'render_fields' ] );
        add_action( 'personal_options_update', [ __CLASS__, 'save_fields' ] );
        add_action( 'edit_user_profile_update', [ __CLASS__, 'save_fields' ] );

        // Rewrite custom desativada — o CPT 'colunista' tem has_archive='colunistas'
        // e cria a rewrite oficialmente. Os campos de WP_User abaixo continuam
        // funcionando pra colunistas legados (que aparecem agregados no archive).
    }

    public static function ensure_role() {
        if ( ! get_role( 'colunista' ) ) {
            add_role( 'colunista', 'Colunista', [
                'read'              => true,
                'edit_posts'        => true,
                'delete_posts'      => true,
                'publish_posts'     => true,
                'upload_files'      => true,
                'edit_published_posts'   => true,
                'delete_published_posts' => true,
            ] );
        }
    }

    public static function add_rewrite() {
        add_rewrite_rule( '^colunistas/?$', 'index.php?orick_colunistas=1', 'top' );
    }

    public static function render_fields( $user ) {
        $cargo       = get_user_meta( $user->ID, 'orick_cargo', true );
        $periodicidade = get_user_meta( $user->ID, 'orick_periodicidade', true );
        $tag_unica   = get_user_meta( $user->ID, 'orick_tag_unica', true );
        $linkedin    = get_user_meta( $user->ID, 'orick_linkedin', true );
        $instagram   = get_user_meta( $user->ID, 'orick_instagram', true );
        $twitter     = get_user_meta( $user->ID, 'orick_twitter', true );
        $site_pessoal= get_user_meta( $user->ID, 'orick_site_pessoal', true );
        $is_colunista= get_user_meta( $user->ID, 'orick_is_colunista', true );
        ?>
        <h2>Perfil de Colunista</h2>
        <table class="form-table">
          <tr>
            <th><label for="orick_is_colunista">Aparecer como colunista</label></th>
            <td><label><input type="checkbox" name="orick_is_colunista" id="orick_is_colunista" value="1" <?php checked( $is_colunista, '1' ); ?>> Marcar este usuário como colunista (aparece na página /colunistas/ e na home)</label></td>
          </tr>
          <tr>
            <th><label for="orick_cargo">Cargo/Especialidade</label></th>
            <td><input type="text" name="orick_cargo" id="orick_cargo" value="<?php echo esc_attr( $cargo ); ?>" class="regular-text" placeholder="Planejadora CFP®, Economista, Trader..."></td>
          </tr>
          <tr>
            <th><label for="orick_periodicidade">Periodicidade</label></th>
            <td>
              <select name="orick_periodicidade" id="orick_periodicidade">
                <option value="">—</option>
                <option value="semanal" <?php selected( $periodicidade, 'semanal' ); ?>>Semanal</option>
                <option value="quinzenal" <?php selected( $periodicidade, 'quinzenal' ); ?>>Quinzenal</option>
                <option value="mensal" <?php selected( $periodicidade, 'mensal' ); ?>>Mensal</option>
                <option value="esporadica" <?php selected( $periodicidade, 'esporadica' ); ?>>Esporádica</option>
              </select>
            </td>
          </tr>
          <tr>
            <th><label for="orick_tag_unica">Tag única (sem #)</label></th>
            <td><input type="text" name="orick_tag_unica" id="orick_tag_unica" value="<?php echo esc_attr( $tag_unica ); ?>" class="regular-text" placeholder="colunabruna">
              <p class="description">Slug da tag que filtra só artigos dele(a). Ex: <code>colunabruna</code>.</p></td>
          </tr>
          <tr>
            <th><label for="orick_linkedin">LinkedIn</label></th>
            <td><input type="url" name="orick_linkedin" id="orick_linkedin" value="<?php echo esc_attr( $linkedin ); ?>" class="regular-text"></td>
          </tr>
          <tr>
            <th><label for="orick_instagram">Instagram (@ ou URL)</label></th>
            <td><input type="text" name="orick_instagram" id="orick_instagram" value="<?php echo esc_attr( $instagram ); ?>" class="regular-text"></td>
          </tr>
          <tr>
            <th><label for="orick_twitter">Twitter/X (@ ou URL)</label></th>
            <td><input type="text" name="orick_twitter" id="orick_twitter" value="<?php echo esc_attr( $twitter ); ?>" class="regular-text"></td>
          </tr>
          <tr>
            <th><label for="orick_site_pessoal">Site pessoal</label></th>
            <td><input type="url" name="orick_site_pessoal" id="orick_site_pessoal" value="<?php echo esc_attr( $site_pessoal ); ?>" class="regular-text"></td>
          </tr>
        </table>
        <?php
    }

    public static function save_fields( $user_id ) {
        if ( ! current_user_can( 'edit_user', $user_id ) ) return;
        update_user_meta( $user_id, 'orick_is_colunista', ! empty( $_POST['orick_is_colunista'] ) ? '1' : '0' );
        update_user_meta( $user_id, 'orick_cargo', sanitize_text_field( $_POST['orick_cargo'] ?? '' ) );
        update_user_meta( $user_id, 'orick_periodicidade', sanitize_key( $_POST['orick_periodicidade'] ?? '' ) );
        update_user_meta( $user_id, 'orick_tag_unica', sanitize_title( $_POST['orick_tag_unica'] ?? '' ) );
        update_user_meta( $user_id, 'orick_linkedin', esc_url_raw( $_POST['orick_linkedin'] ?? '' ) );
        update_user_meta( $user_id, 'orick_instagram', sanitize_text_field( $_POST['orick_instagram'] ?? '' ) );
        update_user_meta( $user_id, 'orick_twitter', sanitize_text_field( $_POST['orick_twitter'] ?? '' ) );
        update_user_meta( $user_id, 'orick_site_pessoal', esc_url_raw( $_POST['orick_site_pessoal'] ?? '' ) );
    }

    /** Retorna array com todos os colunistas marcados */
    public static function get_all() {
        return get_users( [
            'meta_key'   => 'orick_is_colunista',
            'meta_value' => '1',
            'orderby'    => 'display_name',
            'order'      => 'ASC',
        ] );
    }
}

Orick_Ferr_Colunista::init();

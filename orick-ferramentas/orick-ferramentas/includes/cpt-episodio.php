<?php
/**
 * CPT: Episódio de Podcast
 *
 * Cada episódio tem: número, duração, links Spotify/Apple/YouTube, transcrição opcional.
 * Player Spotify embedado no single via iframe.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Orick_Ferr_CPT_Episodio {

    const POST_TYPE = 'episodio';

    public static function init() {
        add_action( 'init', [ __CLASS__, 'register' ] );
        add_action( 'add_meta_boxes', [ __CLASS__, 'metabox' ] );
        add_action( 'save_post_' . self::POST_TYPE, [ __CLASS__, 'save' ] );
    }

    public static function register() {
        register_post_type( self::POST_TYPE, [
            'labels' => [
                'name'          => 'Episódios',
                'singular_name' => 'Episódio',
                'add_new'       => 'Adicionar novo',
                'add_new_item'  => 'Adicionar episódio',
                'menu_name'     => 'Podcast',
            ],
            'public'        => true,
            'show_ui'       => true,
            'menu_position' => 28,
            'menu_icon'     => 'dashicons-microphone',
            'has_archive'   => 'podcast',
            'rewrite'       => [ 'slug' => 'podcast', 'with_front' => false ],
            'supports'      => [ 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ],
            'taxonomies'    => [ 'post_tag', 'category' ],
            'show_in_rest'  => false,
        ] );
    }

    public static function metabox() {
        add_meta_box( 'orick_episodio_box', 'Configurações do episódio', [ __CLASS__, 'render' ], self::POST_TYPE, 'normal', 'high' );
    }

    public static function render( $post ) {
        wp_nonce_field( 'orick_episodio_save', 'orick_episodio_nonce' );
        $numero   = get_post_meta( $post->ID, '_orick_ep_numero', true );
        $duracao  = get_post_meta( $post->ID, '_orick_ep_duracao', true );
        $spotify  = get_post_meta( $post->ID, '_orick_ep_spotify', true );
        $apple    = get_post_meta( $post->ID, '_orick_ep_apple', true );
        $youtube  = get_post_meta( $post->ID, '_orick_ep_youtube', true );
        $convidado= get_post_meta( $post->ID, '_orick_ep_convidado', true );
        ?>
        <style>.orick-ep .row{margin:12px 0}.orick-ep label{display:block;font-weight:600;margin-bottom:4px}.orick-ep input{width:100%}</style>
        <div class="orick-ep">
          <div class="row">
            <label>Número do episódio</label>
            <input type="number" name="orick_ep_numero" value="<?php echo esc_attr( $numero ); ?>" min="1">
          </div>
          <div class="row">
            <label>Duração (mm:ss ou h:mm:ss)</label>
            <input type="text" name="orick_ep_duracao" value="<?php echo esc_attr( $duracao ); ?>" placeholder="48:23">
          </div>
          <div class="row">
            <label>Convidado(a) — opcional</label>
            <input type="text" name="orick_ep_convidado" value="<?php echo esc_attr( $convidado ); ?>" placeholder="Nome do convidado">
          </div>
          <div class="row">
            <label>Link Spotify (ou episódio ID)</label>
            <input type="text" name="orick_ep_spotify" value="<?php echo esc_attr( $spotify ); ?>" placeholder="https://open.spotify.com/episode/...">
            <span style="color:#666;font-size:12px;">Cole a URL completa. Player é embedado automaticamente no single.</span>
          </div>
          <div class="row">
            <label>Link Apple Podcasts</label>
            <input type="text" name="orick_ep_apple" value="<?php echo esc_attr( $apple ); ?>" placeholder="https://podcasts.apple.com/...">
          </div>
          <div class="row">
            <label>Link YouTube (opcional)</label>
            <input type="text" name="orick_ep_youtube" value="<?php echo esc_attr( $youtube ); ?>" placeholder="https://youtube.com/watch?v=...">
          </div>
        </div>
        <?php
    }

    public static function save( $post_id ) {
        if ( ! isset( $_POST['orick_episodio_nonce'] ) ) return;
        if ( ! wp_verify_nonce( $_POST['orick_episodio_nonce'], 'orick_episodio_save' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        update_post_meta( $post_id, '_orick_ep_numero', absint( $_POST['orick_ep_numero'] ?? 0 ) );
        update_post_meta( $post_id, '_orick_ep_duracao', sanitize_text_field( $_POST['orick_ep_duracao'] ?? '' ) );
        update_post_meta( $post_id, '_orick_ep_convidado', sanitize_text_field( $_POST['orick_ep_convidado'] ?? '' ) );
        update_post_meta( $post_id, '_orick_ep_spotify', esc_url_raw( $_POST['orick_ep_spotify'] ?? '' ) );
        update_post_meta( $post_id, '_orick_ep_apple', esc_url_raw( $_POST['orick_ep_apple'] ?? '' ) );
        update_post_meta( $post_id, '_orick_ep_youtube', esc_url_raw( $_POST['orick_ep_youtube'] ?? '' ) );
    }

    /** Gera o embed HTML do Spotify dado a URL */
    public static function spotify_embed( $url ) {
        if ( ! $url ) return '';
        if ( preg_match( '%open\.spotify\.com/(episode|show|playlist)/([A-Za-z0-9]+)%', $url, $m ) ) {
            $type = $m[1]; $id = $m[2];
            return sprintf(
                '<iframe style="border-radius:0" src="https://open.spotify.com/embed/%s/%s?utm_source=generator&theme=0" width="100%%" height="232" frameborder="0" allow="autoplay; clipboard-write; encrypted-media; fullscreen; picture-in-picture" loading="lazy"></iframe>',
                esc_attr( $type ), esc_attr( $id )
            );
        }
        return '';
    }
}

Orick_Ferr_CPT_Episodio::init();

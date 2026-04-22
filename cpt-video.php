<?php
/**
 * CPT: Video + sincronização com YouTube via RSS
 *
 * Configuração: URL do canal em Configurações → Orick Ferramentas
 * Sincronização: cron a cada 1h (puxa últimos 15 vídeos do feed)
 * Fallback: criar vídeo manualmente colando a URL do YouTube
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Orick_Ferr_CPT_Video {

    const POST_TYPE = 'video';

    public static function init() {
        add_action( 'init', [ __CLASS__, 'register' ] );
        add_action( 'add_meta_boxes', [ __CLASS__, 'metabox' ] );
        add_action( 'save_post_' . self::POST_TYPE, [ __CLASS__, 'save' ] );

        // Cron pra sincronizar YouTube
        add_action( 'orick_ferr_sync_youtube', [ __CLASS__, 'sync_youtube' ] );
        if ( ! wp_next_scheduled( 'orick_ferr_sync_youtube' ) ) {
            wp_schedule_event( time(), 'hourly', 'orick_ferr_sync_youtube' );
        }

        // Botão manual na lista
        add_action( 'admin_notices', [ __CLASS__, 'maybe_sync_notice' ] );
        add_action( 'admin_post_orick_ferr_sync_youtube', [ __CLASS__, 'manual_sync' ] );
    }

    public static function register() {
        register_post_type( self::POST_TYPE, [
            'labels' => [
                'name'          => 'Vídeos',
                'singular_name' => 'Vídeo',
                'add_new'       => 'Adicionar novo',
                'add_new_item'  => 'Adicionar vídeo',
                'edit_item'     => 'Editar vídeo',
                'menu_name'     => 'Vídeos',
            ],
            'public'        => true,
            'show_ui'       => true,
            'menu_position' => 27,
            'menu_icon'     => 'dashicons-video-alt3',
            'has_archive'   => 'videos',
            'rewrite'       => [ 'slug' => 'videos', 'with_front' => false ],
            'supports'      => [ 'title', 'editor', 'excerpt', 'thumbnail' ],
            'taxonomies'    => [ 'post_tag', 'category' ],
            'show_in_rest'  => false,
        ] );
    }

    public static function metabox() {
        add_meta_box( 'orick_video_box', 'Configurações do vídeo', [ __CLASS__, 'render' ], self::POST_TYPE, 'normal', 'high' );
    }

    public static function render( $post ) {
        wp_nonce_field( 'orick_video_save', 'orick_video_nonce' );
        $youtube_id = get_post_meta( $post->ID, '_orick_youtube_id', true );
        $url        = get_post_meta( $post->ID, '_orick_video_url', true );
        $duracao    = get_post_meta( $post->ID, '_orick_duracao', true );
        $sync       = get_post_meta( $post->ID, '_orick_youtube_sync', true );
        ?>
        <p>
          <label><strong>URL do YouTube</strong></label><br>
          <input type="text" name="orick_video_url" value="<?php echo esc_attr( $url ); ?>" style="width:100%" placeholder="https://www.youtube.com/watch?v=...">
          <span style="color:#666;font-size:12px;">Cole a URL completa do YouTube. O ID é extraído automaticamente.</span>
        </p>
        <?php if ( $youtube_id ) : ?>
          <p><strong>ID do YouTube:</strong> <code><?php echo esc_html( $youtube_id ); ?></code> &middot;
            <a href="https://youtube.com/watch?v=<?php echo esc_attr( $youtube_id ); ?>" target="_blank">ver no YouTube ↗</a>
          </p>
        <?php endif; ?>
        <p>
          <label><strong>Duração (mm:ss)</strong></label><br>
          <input type="text" name="orick_duracao" value="<?php echo esc_attr( $duracao ); ?>" placeholder="12:34">
        </p>
        <?php if ( $sync ) : ?>
          <p style="background:#f6f7f7;padding:10px;border-left:3px solid #2271b1;"><strong>Importado do canal YouTube</strong> em <?php echo esc_html( $sync ); ?>. Você pode editar livremente.</p>
        <?php endif; ?>
        <?php
    }

    public static function save( $post_id ) {
        if ( ! isset( $_POST['orick_video_nonce'] ) ) return;
        if ( ! wp_verify_nonce( $_POST['orick_video_nonce'], 'orick_video_save' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $url = esc_url_raw( $_POST['orick_video_url'] ?? '' );
        update_post_meta( $post_id, '_orick_video_url', $url );
        update_post_meta( $post_id, '_orick_duracao', sanitize_text_field( $_POST['orick_duracao'] ?? '' ) );

        $yt = self::extract_youtube_id( $url );
        if ( $yt ) update_post_meta( $post_id, '_orick_youtube_id', $yt );
    }

    public static function extract_youtube_id( $url ) {
        if ( preg_match( '%(?:youtube\.com/(?:watch\?v=|embed/|v/|shorts/)|youtu\.be/)([A-Za-z0-9_-]{11})%', $url, $m ) ) {
            return $m[1];
        }
        return '';
    }

    /** Cron: puxa feed RSS do canal */
    public static function sync_youtube() {
        $channel_id = get_option( 'orick_ferr_youtube_channel_id' );
        if ( ! $channel_id ) return;

        $feed_url = "https://www.youtube.com/feeds/videos.xml?channel_id=" . urlencode( $channel_id );
        $res = wp_remote_get( $feed_url, [ 'timeout' => 15 ] );
        if ( is_wp_error( $res ) ) return 0;

        $body = wp_remote_retrieve_body( $res );
        if ( ! $body ) return 0;

        // Parse XML simples
        $xml = @simplexml_load_string( $body, 'SimpleXMLElement', LIBXML_NOCDATA );
        if ( ! $xml ) return 0;

        $ns = $xml->getNamespaces( true );
        $count = 0;

        foreach ( $xml->entry as $entry ) {
            $yt = $entry->children( $ns['yt'] ?? 'http://www.youtube.com/xml/schemas/2015' );
            $media = $entry->children( $ns['media'] ?? 'http://search.yahoo.com/mrss/' );

            $video_id = (string) $yt->videoId;
            if ( ! $video_id ) continue;

            // Já existe?
            $existing = get_posts( [
                'post_type'   => self::POST_TYPE,
                'meta_key'    => '_orick_youtube_id',
                'meta_value'  => $video_id,
                'post_status' => 'any',
                'numberposts' => 1,
                'fields'      => 'ids',
            ] );
            if ( $existing ) continue;

            $title = (string) $entry->title;
            $desc  = $media->group ? (string) $media->group->description : '';
            $pub   = (string) $entry->published;

            $post_id = wp_insert_post( [
                'post_type'    => self::POST_TYPE,
                'post_status'  => 'publish',
                'post_title'   => $title,
                'post_content' => $desc,
                'post_date'    => date( 'Y-m-d H:i:s', strtotime( $pub ) ),
            ] );
            if ( is_wp_error( $post_id ) ) continue;

            update_post_meta( $post_id, '_orick_youtube_id', $video_id );
            update_post_meta( $post_id, '_orick_video_url', "https://www.youtube.com/watch?v=$video_id" );
            update_post_meta( $post_id, '_orick_youtube_sync', current_time( 'mysql' ) );

            // Thumb: usa imagem do maxresdefault do YouTube
            self::set_youtube_thumb( $post_id, $video_id );

            $count++;
        }

        update_option( 'orick_ferr_youtube_last_sync', current_time( 'mysql' ) );
        return $count;
    }

    /** Baixa a thumb do YouTube e seta como imagem destacada */
    public static function set_youtube_thumb( $post_id, $video_id ) {
        $url = "https://i.ytimg.com/vi/$video_id/maxresdefault.jpg";
        $res = wp_remote_get( $url, [ 'timeout' => 15 ] );
        if ( is_wp_error( $res ) || wp_remote_retrieve_response_code( $res ) !== 200 ) {
            $url = "https://i.ytimg.com/vi/$video_id/hqdefault.jpg";
            $res = wp_remote_get( $url, [ 'timeout' => 15 ] );
        }
        if ( is_wp_error( $res ) || wp_remote_retrieve_response_code( $res ) !== 200 ) return;

        $body = wp_remote_retrieve_body( $res );
        if ( ! $body ) return;

        $upload = wp_upload_bits( "youtube-$video_id.jpg", null, $body );
        if ( $upload['error'] ) return;

        $wp_filetype = wp_check_filetype( $upload['file'], null );
        $att_id = wp_insert_attachment( [
            'post_mime_type' => $wp_filetype['type'],
            'post_title'     => "YouTube thumb $video_id",
            'post_content'   => '',
            'post_status'    => 'inherit',
        ], $upload['file'], $post_id );

        if ( ! is_wp_error( $att_id ) ) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
            wp_update_attachment_metadata( $att_id, wp_generate_attachment_metadata( $att_id, $upload['file'] ) );
            set_post_thumbnail( $post_id, $att_id );
        }
    }

    public static function manual_sync() {
        if ( ! current_user_can( 'manage_options' ) ) wp_die();
        check_admin_referer( 'orick_ferr_sync_youtube' );
        $count = self::sync_youtube();
        wp_safe_redirect( add_query_arg( 'orick_sync', intval( $count ), wp_get_referer() ?: admin_url( 'edit.php?post_type=video' ) ) );
        exit;
    }

    public static function maybe_sync_notice() {
        if ( isset( $_GET['orick_sync'] ) ) {
            $n = intval( $_GET['orick_sync'] );
            echo '<div class="notice notice-success is-dismissible"><p>Sincronização YouTube: ' . $n . ' vídeos novos importados.</p></div>';
        }
    }
}

Orick_Ferr_CPT_Video::init();

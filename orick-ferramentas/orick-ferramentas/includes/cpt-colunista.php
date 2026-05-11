<?php
/**
 * CPT: Colunista — perfil editorial gerenciável pelo admin.
 *
 * Substitui o fluxo via WP_User (que dependia de Gravatar e da
 * própria pessoa logar pra mudar foto/bio). Aqui é tudo no plugin:
 *   • Imagem destacada → foto do colunista
 *   • Conteúdo do post → bio
 *   • Metabox próprio  → cargo, periodicidade, tag única, redes sociais
 *
 * Os colunistas antigos (WP_User com orick_is_colunista=1) continuam
 * aparecendo no archive — o template /colunistas/ agrega os dois.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Orick_Ferr_CPT_Colunista {

    const POST_TYPE = 'colunista';

    public static function init() {
        add_action( 'init', [ __CLASS__, 'register' ] );
        add_action( 'add_meta_boxes', [ __CLASS__, 'metabox' ] );
        add_action( 'save_post_' . self::POST_TYPE, [ __CLASS__, 'save' ] );
    }

    public static function register() {
        register_post_type( self::POST_TYPE, [
            'labels' => [
                'name'          => 'Colunistas',
                'singular_name' => 'Colunista',
                'add_new'       => 'Adicionar novo',
                'add_new_item'  => 'Adicionar colunista',
                'edit_item'     => 'Editar colunista',
                'new_item'      => 'Novo colunista',
                'view_item'     => 'Ver colunista',
                'search_items'  => 'Buscar colunistas',
                'menu_name'     => 'Colunistas',
            ],
            'public'        => true,
            'show_ui'       => true,
            'menu_position' => 30,
            'menu_icon'     => 'dashicons-id-alt',
            'has_archive'   => 'colunistas',
            'rewrite'       => [ 'slug' => 'colunistas', 'with_front' => false ],
            'supports'      => [ 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ],
            'taxonomies'    => [ 'post_tag', 'category' ],
            'show_in_rest'  => false,
        ] );
    }

    public static function metabox() {
        add_meta_box(
            'orick_colunista_box',
            'Perfil do colunista',
            [ __CLASS__, 'render' ],
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    public static function render( $post ) {
        wp_nonce_field( 'orick_colunista_save', 'orick_colunista_nonce' );
        $cargo     = get_post_meta( $post->ID, 'orick_cargo', true );
        $period    = get_post_meta( $post->ID, 'orick_periodicidade', true );
        $tag       = get_post_meta( $post->ID, 'orick_tag_unica', true );
        $linkedin  = get_post_meta( $post->ID, 'orick_linkedin', true );
        $instagram = get_post_meta( $post->ID, 'orick_instagram', true );
        $twitter   = get_post_meta( $post->ID, 'orick_twitter', true );
        $site      = get_post_meta( $post->ID, 'orick_site_pessoal', true );
        $user_id   = (int) get_post_meta( $post->ID, 'orick_user_id', true );
        ?>
        <style>
          .orick-cn .row { margin: 14px 0; }
          .orick-cn label { display: block; font-weight: 600; margin-bottom: 4px; font-size: 13px; }
          .orick-cn input, .orick-cn select { width: 100%; max-width: 520px; }
          .orick-cn .desc { color: #666; font-size: 12px; margin-top: 4px; }
        </style>
        <div class="orick-cn">

          <div class="row">
            <label for="orick_cargo">Cargo / Especialidade</label>
            <input type="text" name="orick_cargo" id="orick_cargo" value="<?php echo esc_attr( $cargo ); ?>" placeholder="Ex.: Planejadora CFP®, Economista, Trader…">
          </div>

          <div class="row">
            <label for="orick_periodicidade">Periodicidade</label>
            <select name="orick_periodicidade" id="orick_periodicidade">
              <option value="">—</option>
              <option value="semanal"    <?php selected( $period, 'semanal' ); ?>>Semanal</option>
              <option value="quinzenal"  <?php selected( $period, 'quinzenal' ); ?>>Quinzenal</option>
              <option value="mensal"     <?php selected( $period, 'mensal' ); ?>>Mensal</option>
              <option value="esporadica" <?php selected( $period, 'esporadica' ); ?>>Esporádica</option>
            </select>
          </div>

          <div class="row">
            <label for="orick_tag_unica">Tag única (sem #)</label>
            <input type="text" name="orick_tag_unica" id="orick_tag_unica" value="<?php echo esc_attr( $tag ); ?>" placeholder="ex: colunabruna">
            <div class="desc">Slug da tag que filtra os artigos só dele(a). Ex: <code>colunabruna</code> → <code>/tag/colunabruna/</code>.</div>
          </div>

          <div class="row">
            <label for="orick_user_id">Usuário WordPress associado <span style="color:#999;font-weight:400;">(opcional, mas recomendado)</span></label>
            <select name="orick_user_id" id="orick_user_id">
              <option value="">— Nenhum —</option>
              <?php
              $authors = get_users( [ 'fields' => [ 'ID', 'display_name', 'user_email' ], 'orderby' => 'display_name', 'number' => 200 ] );
              foreach ( $authors as $u ) {
                  printf( '<option value="%d" %s>%s — %s</option>',
                      (int) $u->ID,
                      selected( $user_id, (int) $u->ID, false ),
                      esc_html( $u->display_name ),
                      esc_html( $u->user_email )
                  );
              }
              ?>
            </select>
            <div class="desc">Quando o colunista publica artigos pelo WordPress, vincule aqui. A foto que está na "Imagem destacada" deste perfil vai aparecer automaticamente em qualquer artigo que ele(a) assinar — substitui o Gravatar.</div>
          </div>

          <div class="row">
            <label for="orick_linkedin">LinkedIn (URL completa)</label>
            <input type="url" name="orick_linkedin" id="orick_linkedin" value="<?php echo esc_attr( $linkedin ); ?>" placeholder="https://www.linkedin.com/in/…">
          </div>

          <div class="row">
            <label for="orick_instagram">Instagram (URL ou @user)</label>
            <input type="text" name="orick_instagram" id="orick_instagram" value="<?php echo esc_attr( $instagram ); ?>" placeholder="@orickslv ou https://instagram.com/orickslv">
          </div>

          <div class="row">
            <label for="orick_twitter">Twitter / X (URL ou @user)</label>
            <input type="text" name="orick_twitter" id="orick_twitter" value="<?php echo esc_attr( $twitter ); ?>" placeholder="@user ou URL">
          </div>

          <div class="row">
            <label for="orick_site_pessoal">Site pessoal (URL)</label>
            <input type="url" name="orick_site_pessoal" id="orick_site_pessoal" value="<?php echo esc_attr( $site ); ?>" placeholder="https://">
          </div>

          <div class="row">
            <p class="desc"><strong>Foto:</strong> use o campo <strong>"Imagem destacada"</strong> ali na lateral direita. <strong>Bio:</strong> escreva no editor principal. <strong>Ordem na página /colunistas/:</strong> use o campo "Ordem" do metabox "Atributos do post" (mais alto = aparece primeiro).</p>
          </div>

        </div>
        <?php
    }

    public static function save( $post_id ) {
        if ( ! isset( $_POST['orick_colunista_nonce'] ) ) return;
        if ( ! wp_verify_nonce( $_POST['orick_colunista_nonce'], 'orick_colunista_save' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        update_post_meta( $post_id, 'orick_cargo',         sanitize_text_field( $_POST['orick_cargo'] ?? '' ) );
        update_post_meta( $post_id, 'orick_periodicidade', sanitize_key( $_POST['orick_periodicidade'] ?? '' ) );
        update_post_meta( $post_id, 'orick_tag_unica',     sanitize_title( $_POST['orick_tag_unica'] ?? '' ) );
        update_post_meta( $post_id, 'orick_linkedin',      esc_url_raw( $_POST['orick_linkedin'] ?? '' ) );
        update_post_meta( $post_id, 'orick_instagram',     sanitize_text_field( $_POST['orick_instagram'] ?? '' ) );
        update_post_meta( $post_id, 'orick_twitter',       sanitize_text_field( $_POST['orick_twitter'] ?? '' ) );
        update_post_meta( $post_id, 'orick_site_pessoal',  esc_url_raw( $_POST['orick_site_pessoal'] ?? '' ) );
        update_post_meta( $post_id, 'orick_user_id',       absint( $_POST['orick_user_id'] ?? 0 ) );
    }
}

Orick_Ferr_CPT_Colunista::init();

/**
 * Helper: dados normalizados do colunista (CPT ou WP_User legado).
 */
if ( ! function_exists( 'orick_colunista_data' ) ) {
    function orick_colunista_data( $id, $kind = 'cpt' ) {
        if ( $kind === 'user' ) {
            $u = get_user_by( 'id', (int) $id );
            if ( ! $u ) return null;
            $ig = get_user_meta( $u->ID, 'orick_instagram', true );
            $tw = get_user_meta( $u->ID, 'orick_twitter', true );
            return [
                'id'            => $u->ID,
                'kind'          => 'user',
                'name'          => $u->display_name,
                'cargo'         => get_user_meta( $u->ID, 'orick_cargo', true ),
                'periodicidade' => get_user_meta( $u->ID, 'orick_periodicidade', true ),
                'tag_unica'     => get_user_meta( $u->ID, 'orick_tag_unica', true ),
                'linkedin'      => get_user_meta( $u->ID, 'orick_linkedin', true ),
                'instagram'     => $ig,
                'twitter'       => $tw,
                'site'          => get_user_meta( $u->ID, 'orick_site_pessoal', true ),
                'bio'           => get_user_meta( $u->ID, 'description', true ),
                'avatar'        => get_avatar_url( $u->ID, [ 'size' => 400 ] ),
                'link'          => get_author_posts_url( $u->ID ),
            ];
        }
        $pid = (int) $id;
        if ( ! $pid ) return null;
        return [
            'id'            => $pid,
            'kind'          => 'cpt',
            'name'          => get_the_title( $pid ),
            'cargo'         => get_post_meta( $pid, 'orick_cargo', true ),
            'periodicidade' => get_post_meta( $pid, 'orick_periodicidade', true ),
            'tag_unica'     => get_post_meta( $pid, 'orick_tag_unica', true ),
            'linkedin'      => get_post_meta( $pid, 'orick_linkedin', true ),
            'instagram'     => get_post_meta( $pid, 'orick_instagram', true ),
            'twitter'       => get_post_meta( $pid, 'orick_twitter', true ),
            'site'          => get_post_meta( $pid, 'orick_site_pessoal', true ),
            'bio'           => apply_filters( 'the_content', get_post_field( 'post_content', $pid ) ),
            'avatar'        => has_post_thumbnail( $pid ) ? get_the_post_thumbnail_url( $pid, 'large' ) : '',
            'link'          => get_permalink( $pid ),
        ];
    }
}

/**
 * Normaliza Instagram/Twitter (aceita @user ou URL completa) → URL final.
 */
if ( ! function_exists( 'orick_social_url' ) ) {
    function orick_social_url( $val, $base ) {
        $val = trim( (string) $val );
        if ( ! $val ) return '';
        if ( strpos( $val, 'http' ) === 0 ) return esc_url( $val );
        $handle = ltrim( $val, '@' );
        return esc_url( $base . $handle );
    }
}

/**
 * Avatar bridge — substitui Gravatar pela foto do CPT colunista
 * vinculado ao WP_User.
 *
 * Funciona em qualquer chamada de get_avatar() / the_author_avatar() /
 * get_avatar_url() do tema — sem mudar uma linha do tema.
 */
add_filter( 'get_avatar_url', function( $url, $id_or_email, $args ) {
    // Resolve user_id a partir do que veio (id, email, comentário, user obj)
    $user_id = 0;
    if ( is_numeric( $id_or_email ) ) {
        $user_id = (int) $id_or_email;
    } elseif ( is_string( $id_or_email ) && is_email( $id_or_email ) ) {
        $u = get_user_by( 'email', $id_or_email );
        if ( $u ) $user_id = (int) $u->ID;
    } elseif ( is_object( $id_or_email ) ) {
        if ( ! empty( $id_or_email->user_id ) )      $user_id = (int) $id_or_email->user_id;
        elseif ( ! empty( $id_or_email->ID ) )       $user_id = (int) $id_or_email->ID;
        elseif ( ! empty( $id_or_email->comment_author_email ) ) {
            $u = get_user_by( 'email', $id_or_email->comment_author_email );
            if ( $u ) $user_id = (int) $u->ID;
        }
    }
    if ( ! $user_id ) return $url;

    // Cache estático por request pra não consultar várias vezes pro mesmo user.
    static $cache = [];
    if ( array_key_exists( $user_id, $cache ) ) {
        return $cache[ $user_id ] ?: $url;
    }

    // Procura CPT colunista vinculado a esse user_id
    $cols = get_posts( [
        'post_type'      => 'colunista',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'meta_key'       => 'orick_user_id',
        'meta_value'     => $user_id,
        'no_found_rows'  => true,
        'fields'         => 'ids',
    ] );

    if ( ! $cols || ! has_post_thumbnail( $cols[0] ) ) {
        $cache[ $user_id ] = '';
        return $url;
    }

    $size  = isset( $args['size'] ) ? (int) $args['size'] : 96;
    $thumb = get_the_post_thumbnail_url( $cols[0], [ $size, $size ] );

    $cache[ $user_id ] = $thumb ?: '';
    return $thumb ?: $url;
}, 10, 3 );

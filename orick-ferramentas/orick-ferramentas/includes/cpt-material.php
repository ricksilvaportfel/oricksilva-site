<?php
/**
 * CPT: Material (e-books, planilhas, templates, checklists, guias)
 *
 * Fluxo:
 *  - Cada material tem tipo, arquivo, imagem de capa, descrição, e flag "_requer_cadastro"
 *  - Se requer cadastro: botão "Baixar" leva pra /baixar/<slug>/ (landing de cadastro)
 *  - Se não requer: botão "Baixar" leva direto pro arquivo
 *  - A landing verifica se o usuário já é lead logado → libera download; senão mostra formulário
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

class Orick_Ferr_CPT_Material {

    const POST_TYPE = 'material';
    const TAX       = 'material_tipo';

    public static function init() {
        add_action( 'init', [ __CLASS__, 'register' ] );
        add_action( 'add_meta_boxes', [ __CLASS__, 'metabox' ] );
        add_action( 'save_post_' . self::POST_TYPE, [ __CLASS__, 'save' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'admin_enqueue' ] );
    }

    public static function register() {
        register_post_type( self::POST_TYPE, [
            'labels' => [
                'name'               => 'Materiais',
                'singular_name'      => 'Material',
                'add_new'            => 'Adicionar novo',
                'add_new_item'       => 'Adicionar material',
                'edit_item'          => 'Editar material',
                'new_item'           => 'Novo material',
                'view_item'          => 'Ver material',
                'search_items'       => 'Buscar materiais',
                'not_found'          => 'Nenhum material encontrado',
                'menu_name'          => 'Materiais',
            ],
            'public'        => true,
            'show_ui'       => true,
            'menu_position' => 26,
            'menu_icon'     => 'dashicons-download',
            'has_archive'   => 'materiais',
            'rewrite'       => [ 'slug' => 'materiais', 'with_front' => false ],
            'supports'      => [ 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes' ],
            'taxonomies'    => [ 'post_tag', 'category' ],
            'show_in_rest'  => false,
        ] );

        register_taxonomy( self::TAX, self::POST_TYPE, [
            'labels' => [
                'name'          => 'Tipos de material',
                'singular_name' => 'Tipo',
                'menu_name'     => 'Tipos',
                'all_items'     => 'Todos os tipos',
                'edit_item'     => 'Editar tipo',
                'add_new_item'  => 'Adicionar tipo',
            ],
            'public'            => true,
            'hierarchical'      => true,
            'show_admin_column' => true,
            'rewrite'           => [ 'slug' => 'materiais/tipo' ],
        ] );

        // Semeia tipos padrão na 1ª ativação
        foreach ( [ 'ebook' => 'E-book', 'planilha' => 'Planilha', 'template' => 'Template', 'checklist' => 'Checklist', 'guia' => 'Guia' ] as $slug => $name ) {
            if ( ! term_exists( $slug, self::TAX ) ) {
                wp_insert_term( $name, self::TAX, [ 'slug' => $slug ] );
            }
        }
    }

    public static function admin_enqueue( $hook ) {
        global $post;
        if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) return;
        if ( ! $post || $post->post_type !== self::POST_TYPE ) return;
        wp_enqueue_media();
    }

    public static function metabox() {
        add_meta_box(
            'orick_material_box',
            'Configurações do material',
            [ __CLASS__, 'render' ],
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    public static function render( $post ) {
        wp_nonce_field( 'orick_material_save', 'orick_material_nonce' );
        $arquivo_id  = get_post_meta( $post->ID, '_orick_arquivo_id', true );
        $arquivo_ext = get_post_meta( $post->ID, '_orick_arquivo_ext_url', true );
        $requer      = get_post_meta( $post->ID, '_orick_requer_cadastro', true );
        $paginas     = get_post_meta( $post->ID, '_orick_paginas', true );
        $destaque    = get_post_meta( $post->ID, '_orick_destaque', true );

        $arquivo_url = $arquivo_id ? wp_get_attachment_url( $arquivo_id ) : '';
        ?>
        <style>
          .orick-material-metabox .field { margin: 14px 0; }
          .orick-material-metabox label { display:block; font-weight:600; margin-bottom:6px; }
          .orick-material-metabox .hint { color:#666; font-size:12px; margin-top:4px; }
          .orick-material-metabox input[type="text"], .orick-material-metabox input[type="number"] { width:100%; }
          .orick-material-metabox .file-row { display:flex; gap:10px; align-items:center; }
          .orick-material-metabox .file-row input { flex:1; }
        </style>
        <div class="orick-material-metabox">

          <div class="field">
            <label>Arquivo do material (upload)</label>
            <div class="file-row">
              <input type="text" id="orick_arquivo_url_display" value="<?php echo esc_attr( $arquivo_url ); ?>" readonly>
              <input type="hidden" name="orick_arquivo_id" id="orick_arquivo_id" value="<?php echo esc_attr( $arquivo_id ); ?>">
              <button type="button" class="button" id="orick_arquivo_pick">Selecionar arquivo</button>
              <button type="button" class="button" id="orick_arquivo_clear">Limpar</button>
            </div>
            <p class="hint">Upload direto via Biblioteca de Mídia. PDF, XLSX, DOCX, ZIP, etc.</p>
          </div>

          <div class="field">
            <label>OU link externo (Google Drive, Notion, Figma…)</label>
            <input type="text" name="orick_arquivo_ext_url" value="<?php echo esc_attr( $arquivo_ext ); ?>" placeholder="https://...">
            <p class="hint">Se preenchido, tem prioridade sobre o upload acima. Use pra templates Notion/Figma etc.</p>
          </div>

          <div class="field">
            <label><input type="checkbox" name="orick_requer_cadastro" value="1" <?php checked( $requer, '1' ); ?>> Exige cadastro pra baixar</label>
            <p class="hint">Se marcado, o botão "Baixar" leva pra landing de cadastro (<code>/baixar/&lt;slug&gt;/</code>). Senão, baixa direto.</p>
          </div>

          <div class="field">
            <label>Páginas (opcional)</label>
            <input type="number" name="orick_paginas" value="<?php echo esc_attr( $paginas ); ?>" min="0" step="1">
            <p class="hint">Ex: 42 — aparece como metadado no card.</p>
          </div>

          <div class="field">
            <label><input type="checkbox" name="orick_destaque" value="1" <?php checked( $destaque, '1' ); ?>> Destacar este material na home</label>
          </div>

        </div>

        <script>
        (function($){
          $(document).ready(function(){
            var frame;
            $('#orick_arquivo_pick').on('click', function(e){
              e.preventDefault();
              if (frame) { frame.open(); return; }
              frame = wp.media({ title: 'Selecione o arquivo do material', button: { text: 'Usar este arquivo' }, multiple: false });
              frame.on('select', function(){
                var att = frame.state().get('selection').first().toJSON();
                $('#orick_arquivo_id').val(att.id);
                $('#orick_arquivo_url_display').val(att.url);
              });
              frame.open();
            });
            $('#orick_arquivo_clear').on('click', function(e){
              e.preventDefault();
              $('#orick_arquivo_id').val('');
              $('#orick_arquivo_url_display').val('');
            });
          });
        })(jQuery);
        </script>
        <?php
    }

    public static function save( $post_id ) {
        if ( ! isset( $_POST['orick_material_nonce'] ) ) return;
        if ( ! wp_verify_nonce( $_POST['orick_material_nonce'], 'orick_material_save' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        update_post_meta( $post_id, '_orick_arquivo_id', absint( $_POST['orick_arquivo_id'] ?? 0 ) );
        update_post_meta( $post_id, '_orick_arquivo_ext_url', esc_url_raw( $_POST['orick_arquivo_ext_url'] ?? '' ) );
        update_post_meta( $post_id, '_orick_requer_cadastro', ! empty( $_POST['orick_requer_cadastro'] ) ? '1' : '0' );
        update_post_meta( $post_id, '_orick_paginas', absint( $_POST['orick_paginas'] ?? 0 ) );
        update_post_meta( $post_id, '_orick_destaque', ! empty( $_POST['orick_destaque'] ) ? '1' : '0' );
    }

    /** Resolve URL do download final (upload ou link externo) */
    public static function download_url( $post_id ) {
        $ext = get_post_meta( $post_id, '_orick_arquivo_ext_url', true );
        if ( $ext ) return esc_url( $ext );
        $att = get_post_meta( $post_id, '_orick_arquivo_id', true );
        if ( $att ) return wp_get_attachment_url( $att );
        return '';
    }
}

Orick_Ferr_CPT_Material::init();

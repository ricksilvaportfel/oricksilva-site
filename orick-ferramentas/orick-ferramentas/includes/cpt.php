<?php
/**
 * CPT "Ferramenta" (simulador) com metabox de configurações.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* ---------- register CPT ---------- */
add_action( 'init', function() {
    register_post_type( 'ferramenta', [
        'labels' => [
            'name'                  => 'Ferramentas',
            'singular_name'         => 'Ferramenta',
            'menu_name'             => 'Ferramentas',
            'add_new'               => 'Adicionar nova',
            'add_new_item'          => 'Adicionar ferramenta',
            'edit_item'             => 'Editar ferramenta',
            'new_item'              => 'Nova ferramenta',
            'view_item'             => 'Ver ferramenta',
            'search_items'          => 'Buscar ferramentas',
            'all_items'             => 'Todas ferramentas',
        ],
        'public'              => true,
        'show_in_menu'        => true,
        'menu_position'       => 25,
        'menu_icon'           => 'dashicons-admin-tools',
        'has_archive'         => 'ferramentas',
        'rewrite'             => [ 'slug' => 'ferramentas', 'with_front' => false ],
        'supports'            => [ 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ],
        'taxonomies'          => [ 'post_tag', 'category' ],
        'show_in_rest'        => false,
    ] );

    // Taxonomia Categoria de Ferramenta
    register_taxonomy( 'ferramenta_cat', 'ferramenta', [
        'labels' => [
            'name'          => 'Categorias',
            'singular_name' => 'Categoria',
            'menu_name'     => 'Categorias',
        ],
        'public'       => true,
        'hierarchical' => true,
        'rewrite'      => [ 'slug' => 'ferramentas/categoria' ],
        'show_admin_column' => true,
    ] );
} );

/* ---------- metabox ---------- */
add_action( 'add_meta_boxes', function() {
    add_meta_box(
        'orick_ferramenta_config',
        'Configurações da ferramenta',
        'orick_ferramenta_metabox_render',
        'ferramenta',
        'normal',
        'high'
    );
} );

function orick_ferramenta_metabox_render( $post ) {
    wp_nonce_field( 'orick_ferramenta_save', 'orick_ferramenta_nonce' );

    $simulator_slug = get_post_meta( $post->ID, '_orick_simulator_slug', true );
    $preco          = get_post_meta( $post->ID, '_orick_preco', true );
    $requer_login   = get_post_meta( $post->ID, '_orick_requer_login', true );
    $destaque_home  = get_post_meta( $post->ID, '_orick_destaque_home', true );
    $link_externo   = get_post_meta( $post->ID, '_orick_link_externo', true );
    $como_usar      = get_post_meta( $post->ID, '_orick_como_usar', true );
    $tool_type      = get_post_meta( $post->ID, '_orick_tool_type', true );
    $tool_duration  = get_post_meta( $post->ID, '_orick_tool_duration', true );
    $tool_icon_svg  = get_post_meta( $post->ID, '_orick_tool_icon_svg', true );

    ?>
    <style>
      .orick-metabox-field { margin: 18px 0; }
      .orick-metabox-field label { display:block; font-weight:600; margin-bottom:6px; font-size: 13px; }
      .orick-metabox-field .desc { color:#666; font-size:12px; margin-top:4px; }
      .orick-metabox-field input[type="text"],
      .orick-metabox-field input[type="url"],
      .orick-metabox-field select,
      .orick-metabox-field textarea { width:100%; max-width:500px; }
      .orick-metabox-field textarea { min-height: 80px; }
    </style>

    <div class="orick-metabox-field">
      <label for="orick_simulator_slug">Simulador (código embutido)</label>
      <select name="orick_simulator_slug" id="orick_simulator_slug">
        <option value="">— Nenhum (ferramenta externa / sem simulador) —</option>
        <option value="goal-based" <?php selected( $simulator_slug, 'goal-based' ); ?>>Goal Based Investing</option>
        <option value="planejamento-comercial" <?php selected( $simulator_slug, 'planejamento-comercial' ); ?>>Planejamento Comercial</option>
        <option value="fee-commission" <?php selected( $simulator_slug, 'fee-commission' ); ?>>Fee x Commission</option>
        <option value="planejamento-if" <?php selected( $simulator_slug, 'planejamento-if' ); ?>>Planejamento de Independência Financeira</option>
      </select>
      <div class="desc">Escolha qual simulador roda nesta página. Se "Nenhum", configure o link externo abaixo.</div>
    </div>

    <div class="orick-metabox-field">
      <label for="orick_preco">Preço</label>
      <select name="orick_preco" id="orick_preco">
        <option value="gratuito" <?php selected( $preco, 'gratuito' ); ?>>Gratuito</option>
        <option value="freemium" <?php selected( $preco, 'freemium' ); ?>>Freemium</option>
        <option value="pago" <?php selected( $preco, 'pago' ); ?>>Pago</option>
      </select>
    </div>

    <div class="orick-metabox-field">
      <label for="orick_tool_type">Tipo (eyebrow do card)</label>
      <select name="orick_tool_type" id="orick_tool_type">
        <option value="simulador"    <?php selected( $tool_type, 'simulador' ); ?>>Simulador</option>
        <option value="calculadora"  <?php selected( $tool_type, 'calculadora' ); ?>>Calculadora</option>
        <option value="planejamento" <?php selected( $tool_type, 'planejamento' ); ?>>Planejamento</option>
      </select>
      <div class="desc">Aparece acima do título no card tipográfico (ex: "SIMULADOR · 3 MIN").</div>
    </div>

    <div class="orick-metabox-field">
      <label for="orick_tool_duration">Tempo estimado (opcional)</label>
      <input type="text" name="orick_tool_duration" id="orick_tool_duration" value="<?php echo esc_attr( $tool_duration ); ?>" placeholder="3 min">
      <div class="desc">Ex: "3 min". Aparece após o tipo no eyebrow do card.</div>
    </div>

    <div class="orick-metabox-field">
      <label for="orick_tool_icon_svg">Ícone SVG (opcional — sobrescreve o padrão)</label>
      <textarea name="orick_tool_icon_svg" id="orick_tool_icon_svg" rows="4" style="font-family:monospace;font-size:12px;" placeholder='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">...</svg>'><?php echo esc_textarea( $tool_icon_svg ); ?></textarea>
      <div class="desc">Cole um SVG inline 24×24 com <code>stroke="currentColor"</code>. Deixe vazio pra usar o ícone padrão do tipo (simulador = linha crescente; calculadora = linha com eixos; planejamento = barras).</div>
    </div>

    <div class="orick-metabox-field">
      <label>
        <input type="checkbox" name="orick_requer_login" value="1" <?php checked( $requer_login, '1' ); ?>>
        Exige cadastro/login pra acessar
      </label>
      <div class="desc">Se marcado, o usuário precisa fazer cadastro (com nome, email, telefone, CPF, profissão) e login antes de usar a ferramenta.</div>
    </div>

    <div class="orick-metabox-field">
      <label>
        <input type="checkbox" name="orick_destaque_home" value="1" <?php checked( $destaque_home, '1' ); ?>>
        Aparecer na home (seção "Ferramentas")
      </label>
      <div class="desc">Marque até 3 ferramentas pra aparecer na home. Se marcar mais, só as 3 mais recentes aparecem.</div>
    </div>

    <div class="orick-metabox-field">
      <label for="orick_link_externo">Link externo (opcional)</label>
      <input type="url" name="orick_link_externo" id="orick_link_externo" value="<?php echo esc_attr( $link_externo ); ?>" placeholder="https://">
      <div class="desc">Use se a ferramenta for externa (Notion, planilha, outro site). Só preencha se "Simulador" estiver como "Nenhum".</div>
    </div>

    <div class="orick-metabox-field">
      <label for="orick_como_usar">"Como usar" (instruções curtas)</label>
      <textarea name="orick_como_usar" id="orick_como_usar"><?php echo esc_textarea( $como_usar ); ?></textarea>
      <div class="desc">Texto curto que aparece antes do simulador, explicando pra quem serve e como usar. Use parágrafos separados por linha em branco.</div>
    </div>

    <?php
}

/* ---------- save metabox ---------- */
add_action( 'save_post_ferramenta', function( $post_id ) {
    if ( ! isset( $_POST['orick_ferramenta_nonce'] ) ) return;
    if ( ! wp_verify_nonce( $_POST['orick_ferramenta_nonce'], 'orick_ferramenta_save' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    $fields = [
        '_orick_simulator_slug' => 'orick_simulator_slug',
        '_orick_preco'          => 'orick_preco',
        '_orick_link_externo'   => 'orick_link_externo',
        '_orick_como_usar'      => 'orick_como_usar',
        '_orick_tool_type'      => 'orick_tool_type',
        '_orick_tool_duration'  => 'orick_tool_duration',
    ];
    foreach ( $fields as $meta_key => $post_key ) {
        if ( $meta_key === '_orick_como_usar' ) {
            update_post_meta( $post_id, $meta_key, sanitize_textarea_field( $_POST[ $post_key ] ?? '' ) );
        } elseif ( $meta_key === '_orick_link_externo' ) {
            update_post_meta( $post_id, $meta_key, esc_url_raw( $_POST[ $post_key ] ?? '' ) );
        } else {
            update_post_meta( $post_id, $meta_key, sanitize_text_field( $_POST[ $post_key ] ?? '' ) );
        }
    }
    // SVG inline: permite tags <svg>/<path>/<g>/<circle>/... via wp_kses
    $allowed_svg = [
        'svg'  => [ 'xmlns' => [], 'viewbox' => [], 'width' => [], 'height' => [], 'fill' => [], 'stroke' => [], 'stroke-width' => [], 'stroke-linecap' => [], 'stroke-linejoin' => [], 'class' => [], 'aria-hidden' => [] ],
        'path' => [ 'd' => [], 'fill' => [], 'stroke' => [], 'stroke-width' => [], 'stroke-linecap' => [], 'stroke-linejoin' => [], 'opacity' => [] ],
        'g'    => [ 'fill' => [], 'stroke' => [], 'stroke-width' => [], 'opacity' => [], 'transform' => [] ],
        'circle' => [ 'cx' => [], 'cy' => [], 'r' => [], 'fill' => [], 'stroke' => [], 'stroke-width' => [] ],
        'rect'   => [ 'x' => [], 'y' => [], 'width' => [], 'height' => [], 'rx' => [], 'fill' => [], 'stroke' => [], 'stroke-width' => [] ],
        'line'   => [ 'x1' => [], 'y1' => [], 'x2' => [], 'y2' => [], 'stroke' => [], 'stroke-width' => [] ],
        'polyline' => [ 'points' => [], 'fill' => [], 'stroke' => [], 'stroke-width' => [], 'stroke-linecap' => [], 'stroke-linejoin' => [] ],
        'polygon'  => [ 'points' => [], 'fill' => [], 'stroke' => [] ],
    ];
    update_post_meta( $post_id, '_orick_tool_icon_svg', wp_kses( (string) ( $_POST['orick_tool_icon_svg'] ?? '' ), $allowed_svg ) );
    update_post_meta( $post_id, '_orick_requer_login', ! empty( $_POST['orick_requer_login'] ) ? '1' : '0' );
    update_post_meta( $post_id, '_orick_destaque_home', ! empty( $_POST['orick_destaque_home'] ) ? '1' : '0' );
} );

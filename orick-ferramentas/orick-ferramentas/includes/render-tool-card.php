<?php
/**
 * Renderizador de cards tipográficos de Ferramentas.
 *
 * Usado na home (front-page.php do tema) e no archive /ferramentas/.
 * Card SEM thumb de imagem — ícone SVG linear + eyebrow + título + descrição + footer.
 *
 * Variantes:
 *   'default' — card padrão (grid 4-col na home, auto-fit no archive)
 *   'hero'    — card grande do topo do archive /ferramentas/
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * SVG padrão por tipo. Usado quando _orick_tool_icon_svg está vazio.
 * Todos stroke="currentColor" 1.5px, viewBox 24×24.
 */
function oricksilva_default_tool_icon( $type ) {
    switch ( $type ) {
        case 'simulador':
            // Linha crescente com eixos
            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M3 20h18"/>
                <path d="M3 20V4"/>
                <polyline points="4,16 9,11 13,13 20,5"/>
                <circle cx="20" cy="5" r="1.2" fill="currentColor"/>
            </svg>';

        case 'calculadora':
            // Linha pura ascendente (juros compostos)
            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M3 19h18"/>
                <path d="M3 19V5"/>
                <path d="M4 17 C 9 16, 12 13, 20 5"/>
                <circle cx="20" cy="5" r="1.2" fill="currentColor"/>
            </svg>';

        case 'planejamento':
            // Barras verticais
            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M3 20h18"/>
                <line x1="7"  y1="16" x2="7"  y2="10"/>
                <line x1="12" y1="16" x2="12" y2="6"/>
                <line x1="17" y1="16" x2="17" y2="12"/>
            </svg>';
    }

    // Fallback: pie chart
    return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="12" cy="12" r="8"/>
        <path d="M12 4v8l5.7 5.7"/>
    </svg>';
}

/**
 * Split do título pra destacar a última palavra em italic laranja.
 * "Goal Based Investing" → ["Goal Based", "Investing"]
 * "Ferramenta" → ["", "Ferramenta"]
 */
function oricksilva_tool_title_parts( $title ) {
    $title = trim( $title );
    $pos   = strrpos( $title, ' ' );
    if ( $pos === false ) return [ '', $title ];
    return [ substr( $title, 0, $pos ), substr( $title, $pos + 1 ) ];
}

/**
 * Renderiza um card de ferramenta.
 *
 * @param int    $post_id
 * @param string $variant 'default' | 'hero'
 */
function oricksilva_render_tool_card( $post_id, $variant = 'default' ) {
    $title    = get_the_title( $post_id );
    $excerpt  = has_excerpt( $post_id ) ? get_the_excerpt( $post_id ) : wp_trim_words( get_post_field( 'post_content', $post_id ), 22, '…' );
    $link     = get_permalink( $post_id );
    $ext_link = get_post_meta( $post_id, '_orick_link_externo', true );
    if ( $ext_link ) $link = $ext_link;

    $preco     = get_post_meta( $post_id, '_orick_preco', true ) ?: 'gratuito';
    $requer    = get_post_meta( $post_id, '_orick_requer_login', true ) === '1';
    $tool_type = get_post_meta( $post_id, '_orick_tool_type', true ) ?: 'simulador';
    $duration  = get_post_meta( $post_id, '_orick_tool_duration', true );
    $icon_svg  = get_post_meta( $post_id, '_orick_tool_icon_svg', true );
    if ( ! $icon_svg ) $icon_svg = oricksilva_default_tool_icon( $tool_type );

    $eyebrow_parts = [ strtoupper( $tool_type ) ];
    if ( $duration ) $eyebrow_parts[] = strtoupper( $duration );
    $eyebrow = implode( ' · ', $eyebrow_parts );

    $footer_left_parts = [];
    if ( $preco === 'gratuito' )      $footer_left_parts[] = 'GRÁTIS';
    elseif ( $preco === 'freemium' )  $footer_left_parts[] = 'FREEMIUM';
    elseif ( $preco === 'pago' )      $footer_left_parts[] = 'PAGO';
    $footer_left_parts[] = $requer ? 'LOGIN' : 'SEM LOGIN';
    $footer_left = implode( ' · ', $footer_left_parts );

    [ $title_first, $title_last ] = oricksilva_tool_title_parts( $title );

    $is_external = (bool) $ext_link;
    $attrs = $is_external ? ' target="_blank" rel="noopener"' : '';

    if ( $variant === 'hero' ) :
        // Coluna esquerda: ícone 72×72 grande, título 44px, descrição, CTA
        // Coluna direita: 4 "metrics" (Tipo, Tempo, Preço, Acesso)
        $metrics = [
            [ 'label' => 'Tipo',   'value' => ucfirst( $tool_type ) ],
            [ 'label' => 'Tempo',  'value' => $duration ?: '—' ],
            [ 'label' => 'Preço',  'value' => ucfirst( $preco ) ],
            [ 'label' => 'Acesso', 'value' => $requer ? 'Cadastro' : 'Aberto' ],
        ];
        ?>
        <article class="tool-card tool-card-hero">
          <div class="tool-card-hero-body">
            <div class="tool-icon tool-icon-lg"><?php echo $icon_svg; // SVG sanitizado no save ?></div>
            <div class="tool-card-hero-main">
              <span class="tool-eyebrow"><?php echo esc_html( $eyebrow ); ?></span>
              <h2 class="tool-title tool-title-hero">
                <?php if ( $title_first ) : ?><?php echo esc_html( $title_first ); ?> <?php endif; ?><em><?php echo esc_html( $title_last ); ?></em>
              </h2>
              <p class="tool-desc tool-desc-hero"><?php echo esc_html( $excerpt ); ?></p>
              <div class="tool-cta-row">
                <a class="tool-btn" href="<?php echo esc_url( $link ); ?>"<?php echo $attrs; ?>>Abrir ferramenta →</a>
                <span class="tool-footer-note"><?php echo esc_html( $footer_left ); ?></span>
              </div>
            </div>
          </div>
          <div class="tool-card-hero-metrics">
            <?php foreach ( $metrics as $m ) : ?>
              <div class="tool-metric">
                <span class="tool-metric-label"><?php echo esc_html( $m['label'] ); ?></span>
                <span class="tool-metric-value"><?php echo esc_html( $m['value'] ); ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </article>
        <?php
        return;
    endif;

    // default
    ?>
    <a class="tool-card" href="<?php echo esc_url( $link ); ?>"<?php echo $attrs; ?>>
      <div class="tool-card-top">
        <div class="tool-icon"><?php echo $icon_svg; // SVG sanitizado no save ?></div>
        <span class="tool-eyebrow"><?php echo esc_html( $eyebrow ); ?></span>
        <h3 class="tool-title">
          <?php if ( $title_first ) : ?><?php echo esc_html( $title_first ); ?> <?php endif; ?><em><?php echo esc_html( $title_last ); ?></em>
        </h3>
        <p class="tool-desc"><?php echo esc_html( $excerpt ); ?></p>
      </div>
      <div class="tool-card-footer">
        <span class="tool-footer-left"><?php echo esc_html( $footer_left ); ?></span>
        <span class="tool-footer-cta">ABRIR →</span>
      </div>
    </a>
    <?php
}

<?php
/**
 * Renderizador de cards tipográficos de Eventos.
 * Mesmo espírito do render-tool-card: sem thumb, ícone SVG linear + meta estruturado.
 *
 * Variantes:
 *   'default' — card de grid (archive /eventos/)
 *   'hero'    — card grande horizontal da home (ícone 64 + data/local + CTA)
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** SVG padrão por formato do evento. */
function oricksilva_default_event_icon( $formato ) {
    switch ( $formato ) {
        case 'online':
            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="3" y="4" width="18" height="13" rx="1"/>
                <path d="M8 20h8"/>
                <path d="M12 17v3"/>
                <polyline points="10,9 14,11 10,13" fill="currentColor" stroke="none"/>
            </svg>';

        case 'hibrido':
            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="8"  cy="12" r="4"/>
                <circle cx="16" cy="12" r="4"/>
            </svg>';

        case 'presencial':
        default:
            return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 21s-7-6.5-7-12a7 7 0 1 1 14 0c0 5.5-7 12-7 12z"/>
                <circle cx="12" cy="9" r="2.3"/>
            </svg>';
    }
}

/** Título com última palavra em italic laranja (igual tool-card). */
function oricksilva_event_title_parts( $title ) {
    $title = trim( $title );
    $pos   = strrpos( $title, ' ' );
    if ( $pos === false ) return [ '', $title ];
    return [ substr( $title, 0, $pos ), substr( $title, $pos + 1 ) ];
}

/**
 * Query dos próximos eventos (data ≥ hoje, ordenados ASC). Fallback pra passados se vazio.
 */
function oricksilva_next_events_query( $limit = 4 ) {
    $today = current_time( 'Y-m-d' );
    $q = new WP_Query( [
        'post_type'      => 'evento',
        'post_status'    => 'publish',
        'posts_per_page' => (int) $limit,
        'meta_key'       => '_orick_ev_data',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
        'meta_query'     => [ [
            'key'     => '_orick_ev_data',
            'value'   => $today,
            'compare' => '>=',
            'type'    => 'DATE',
        ] ],
    ] );
    return $q;
}

/**
 * Renderiza um card de evento.
 *
 * @param int    $post_id
 * @param string $variant 'default' | 'hero'
 */
function oricksilva_render_event_card( $post_id, $variant = 'default' ) {
    $title   = get_the_title( $post_id );
    $excerpt = has_excerpt( $post_id ) ? get_the_excerpt( $post_id ) : wp_trim_words( get_post_field( 'post_content', $post_id ), 30, '…' );
    $link    = get_permalink( $post_id );

    $data      = get_post_meta( $post_id, '_orick_ev_data', true );
    $hora_ini  = get_post_meta( $post_id, '_orick_ev_hora_ini', true );
    $formato   = get_post_meta( $post_id, '_orick_ev_formato', true ) ?: 'presencial';
    $local     = get_post_meta( $post_id, '_orick_ev_local', true );
    $cidade    = get_post_meta( $post_id, '_orick_ev_cidade', true );
    $gratuito  = get_post_meta( $post_id, '_orick_ev_gratuito', true ) === '1';
    $preco     = get_post_meta( $post_id, '_orick_ev_preco', true );
    $status    = get_post_meta( $post_id, '_orick_ev_status', true );
    $link_insc = get_post_meta( $post_id, '_orick_ev_link_inscricao', true );

    $icon_svg = oricksilva_default_event_icon( $formato );

    // Eyebrow: "EVENTO · PRESENCIAL" ou "EVENTO · ONLINE" etc
    $eyebrow = 'EVENTO · ' . strtoupper( $formato );
    if ( $status === 'ultimas' )   $eyebrow .= ' · ÚLTIMAS VAGAS';
    if ( $status === 'em_breve' )  $eyebrow .= ' · EM BREVE';

    // Data formatada
    $data_fmt = $data ? date_i18n( 'd \d\e F', strtotime( $data ) ) : '';
    if ( $hora_ini ) $data_fmt .= ' · ' . $hora_ini;

    // Local formatado
    $local_bits = [];
    if ( $formato === 'online' ) {
        $local_bits[] = 'Online';
        if ( $local ) $local_bits[] = $local;
    } else {
        if ( $local )  $local_bits[] = $local;
        if ( $cidade ) $local_bits[] = $cidade;
    }
    $local_fmt = implode( ' · ', $local_bits );

    $preco_fmt = $gratuito ? 'Gratuito' : ( $preco ?: 'Pago' );

    [ $title_first, $title_last ] = oricksilva_event_title_parts( $title );

    $cta_disponivel = in_array( $status, [ 'abertas', 'ultimas' ], true ) && $link_insc;

    if ( $variant === 'hero' ) :
        ?>
        <article class="event-card event-card-hero">
          <div class="event-card-hero-main">
            <div class="event-icon event-icon-lg"><?php echo $icon_svg; // sanitizado em oricksilva_default_event_icon ?></div>
            <div class="event-card-hero-body">
              <span class="event-eyebrow"><?php echo esc_html( $eyebrow ); ?></span>
              <h2 class="event-title event-title-hero">
                <?php if ( $title_first ) : ?><?php echo esc_html( $title_first ); ?> <?php endif; ?><em><?php echo esc_html( $title_last ); ?></em>
              </h2>
              <?php if ( $excerpt ) : ?>
                <p class="event-desc event-desc-hero"><?php echo esc_html( $excerpt ); ?></p>
              <?php endif; ?>
              <div class="event-cta-row">
                <?php if ( $cta_disponivel ) : ?>
                  <a class="tool-btn" href="<?php echo esc_url( $link_insc ); ?>" target="_blank" rel="noopener">Quero me inscrever →</a>
                <?php else : ?>
                  <a class="tool-btn" href="<?php echo esc_url( $link ); ?>">Saber mais →</a>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <div class="event-card-hero-metrics">
            <?php if ( $data_fmt ) : ?>
              <div class="tool-metric">
                <span class="tool-metric-label">Data</span>
                <span class="tool-metric-value"><?php echo esc_html( $data_fmt ); ?></span>
              </div>
            <?php endif; ?>
            <div class="tool-metric">
              <span class="tool-metric-label">Formato</span>
              <span class="tool-metric-value"><?php echo esc_html( ucfirst( $formato ) ); ?></span>
            </div>
            <?php if ( $local_fmt ) : ?>
              <div class="tool-metric">
                <span class="tool-metric-label">Local</span>
                <span class="tool-metric-value"><?php echo esc_html( $local_fmt ); ?></span>
              </div>
            <?php endif; ?>
            <div class="tool-metric">
              <span class="tool-metric-label">Preço</span>
              <span class="tool-metric-value"><?php echo esc_html( $preco_fmt ); ?></span>
            </div>
          </div>
        </article>
        <?php
        return;
    endif;

    // default — card de grid
    ?>
    <a class="event-card" href="<?php echo esc_url( $link ); ?>">
      <div class="event-card-top">
        <div class="event-icon"><?php echo $icon_svg; ?></div>
        <span class="event-eyebrow"><?php echo esc_html( $eyebrow ); ?></span>
        <h3 class="event-title">
          <?php if ( $title_first ) : ?><?php echo esc_html( $title_first ); ?> <?php endif; ?><em><?php echo esc_html( $title_last ); ?></em>
        </h3>
        <?php if ( $excerpt ) : ?>
          <p class="event-desc"><?php echo esc_html( $excerpt ); ?></p>
        <?php endif; ?>
      </div>
      <div class="event-card-footer">
        <span class="event-footer-left">
          <?php echo esc_html( $data_fmt ); ?>
          <?php if ( $local_fmt ) echo ' · ' . esc_html( $local_fmt ); ?>
          <?php echo ' · ' . esc_html( $preco_fmt ); ?>
        </span>
        <span class="tool-footer-cta">VER →</span>
      </div>
    </a>
    <?php
}

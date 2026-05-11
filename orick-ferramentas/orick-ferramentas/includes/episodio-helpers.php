<?php
/**
 * Helpers de render do CPT "episodio" (podcast).
 * Compartilhados entre home (tema), archive-episodio.php e single-episodio.php.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Normaliza os metadados de um episódio.
 * Usa as meta keys atuais do plugin (_orick_ep_numero/duracao/convidado/spotify/apple/youtube)
 * e tolera chaves alternativas da spec (_url / _embed) quando presentes.
 */
if ( ! function_exists( 'orick_episodio_data' ) ) {
    function orick_episodio_data( $pid = null ) {
        $pid = $pid ?: get_the_ID();
        $spot  = get_post_meta( $pid, '_orick_ep_spotify_url', true ) ?: get_post_meta( $pid, '_orick_ep_spotify', true );
        $apple = get_post_meta( $pid, '_orick_ep_apple_url', true )   ?: get_post_meta( $pid, '_orick_ep_apple', true );
        $yt    = get_post_meta( $pid, '_orick_ep_youtube_url', true ) ?: get_post_meta( $pid, '_orick_ep_youtube', true );
        $embed = get_post_meta( $pid, '_orick_ep_spotify_embed', true );

        return [
            'id'              => $pid,
            'numero'          => (int) get_post_meta( $pid, '_orick_ep_numero', true ),
            'duracao'         => get_post_meta( $pid, '_orick_ep_duracao', true ),
            'convidado'       => get_post_meta( $pid, '_orick_ep_convidado', true ),
            'convidado_cargo' => get_post_meta( $pid, '_orick_ep_convidado_cargo', true ),
            'spotify'         => $spot,
            'apple'           => $apple,
            'youtube'         => $yt,
            'spotify_embed'   => $embed,
            'thumb'           => has_post_thumbnail( $pid ) ? get_the_post_thumbnail_url( $pid, 'large' ) : '',
            'permalink'       => get_permalink( $pid ),
            'title'           => get_the_title( $pid ),
        ];
    }
}

/**
 * Formato humano da duração.
 * "1:12:03" → "1H 12MIN"; "48:23" → "48MIN"; qualquer outra string → retorna como veio.
 */
if ( ! function_exists( 'orick_ep_dur_human' ) ) {
    function orick_ep_dur_human( $d ) {
        if ( ! $d ) return '';
        $parts = explode( ':', trim( $d ) );
        if ( count( $parts ) === 3 ) {
            $h = intval( $parts[0] );
            $m = intval( $parts[1] );
            return $h . 'H' . ( $m ? ' ' . $m . 'MIN' : '' );
        }
        if ( count( $parts ) === 2 ) {
            return intval( $parts[0] ) . 'MIN';
        }
        return $d;
    }
}

/**
 * Retorna total de episódios publicados (usado pra numerar decrescente
 * quando o post não tem _orick_ep_numero salvo).
 */
if ( ! function_exists( 'orick_ep_total' ) ) {
    function orick_ep_total() {
        static $cache = null;
        if ( $cache !== null ) return $cache;
        if ( ! post_type_exists( 'episodio' ) ) return $cache = 0;
        $counts = wp_count_posts( 'episodio' );
        return $cache = (int) ( $counts->publish ?? 0 );
    }
}

/**
 * Lista plataformas com separador " · ". Vazio se nenhuma configurada.
 */
if ( ! function_exists( 'orick_ep_platforms_line' ) ) {
    function orick_ep_platforms_line( $data ) {
        $p = [];
        if ( ! empty( $data['spotify'] ) ) $p[] = 'Spotify';
        if ( ! empty( $data['apple'] ) )   $p[] = 'Apple';
        if ( ! empty( $data['youtube'] ) ) $p[] = 'YouTube';
        return implode( ' · ', $p );
    }
}

/**
 * Renderiza o card quadrado do episódio destacado.
 * Usado tanto na home quanto no archive /podcast/.
 */
if ( ! function_exists( 'orick_ep_render_featured' ) ) {
    function orick_ep_render_featured( $data ) {
        if ( ! $data ) return;
        $dur_human = orick_ep_dur_human( $data['duracao'] );
        $kicker = 'EP ' . max( 1, $data['numero'] );
        if ( $dur_human ) $kicker .= ' · ' . $dur_human;
        ?>
        <a class="os-podcast-featured" href="<?php echo esc_url( $data['permalink'] ); ?>">
          <div class="os-podcast-body">
            <div>
              <div class="os-podcast-ep"><?php echo esc_html( $kicker ); ?></div>
              <h3 class="os-podcast-title"><?php echo esc_html( $data['title'] ); ?></h3>
              <?php if ( $data['convidado'] ) : ?>
                <div class="os-podcast-guest">com <?php echo esc_html( $data['convidado'] ); ?></div>
              <?php endif; ?>
            </div>
            <div class="os-podcast-player" aria-hidden="true">
              <span class="os-podcast-play">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
              </span>
              <div class="os-podcast-progress"><div class="os-podcast-progress-fill" style="width:<?php echo esc_attr( orick_ep_mock_progress( $data['id'] ) ); ?>%"></div></div>
              <div class="os-podcast-time"><?php echo esc_html( orick_ep_mock_time( $data['duracao'], $data['id'] ) ); ?></div>
            </div>
          </div>
        </a>
        <?php
    }
}

/** Porcentagem mock, determinística por ID (20–70%). */
if ( ! function_exists( 'orick_ep_mock_progress' ) ) {
    function orick_ep_mock_progress( $pid ) {
        return 20 + ( intval( $pid ) * 7 ) % 51;
    }
}

/** Tempo "28:14 / 1:12:03" a partir da duração total + pct mock. */
if ( ! function_exists( 'orick_ep_mock_time' ) ) {
    function orick_ep_mock_time( $dur, $pid ) {
        if ( ! $dur ) return '';
        $parts = array_reverse( explode( ':', trim( $dur ) ) ); // [s, m, h]
        $total_sec = ( $parts[0] ?? 0 ) + ( ( $parts[1] ?? 0 ) * 60 ) + ( ( $parts[2] ?? 0 ) * 3600 );
        if ( ! $total_sec ) return $dur;
        $pct = orick_ep_mock_progress( $pid );
        $cur = (int) ( $total_sec * $pct / 100 );
        return orick_ep_fmt_seconds( $cur ) . ' / ' . $dur;
    }
}

/** Formata segundos pra mm:ss ou h:mm:ss. */
if ( ! function_exists( 'orick_ep_fmt_seconds' ) ) {
    function orick_ep_fmt_seconds( $s ) {
        $s = max( 0, (int) $s );
        $h = intdiv( $s, 3600 );
        $m = intdiv( $s % 3600, 60 );
        $sec = $s % 60;
        if ( $h > 0 ) return sprintf( '%d:%02d:%02d', $h, $m, $sec );
        return sprintf( '%d:%02d', $m, $sec );
    }
}

/**
 * Renderiza um .os-podcast-item para a lista (home/archive).
 * $idx = número a exibir (#127, #126…). Passa 0 pra calcular pelo meta/total.
 */
if ( ! function_exists( 'orick_ep_render_item' ) ) {
    function orick_ep_render_item( $data, $idx = 0 ) {
        if ( ! $data ) return;
        $num = $data['numero'] ?: $idx;
        $platforms = orick_ep_platforms_line( $data );
        ?>
        <a class="os-podcast-item" href="<?php echo esc_url( $data['permalink'] ); ?>">
          <?php if ( $num ) : ?>
            <span class="os-podcast-idx">#<?php echo esc_html( $num ); ?></span>
          <?php else : ?>
            <span class="os-podcast-idx"></span>
          <?php endif; ?>
          <div>
            <div class="os-podcast-item-title"><?php echo esc_html( $data['title'] ); ?></div>
            <?php if ( $platforms ) : ?>
              <div class="os-podcast-item-platforms"><?php echo esc_html( $platforms ); ?></div>
            <?php endif; ?>
          </div>
          <span class="os-podcast-item-dur"><?php echo esc_html( $data['duracao'] ); ?></span>
        </a>
        <?php
    }
}

<?php
/**
 * Template Name: Home — O Rick Silva
 * Front page: layout editorial denso com seções dinâmicas por tag/categoria.
 */
get_header(); ?>

<?php
/* ---------- HERO ---------- */
$lead_q  = orick_get_posts_by_tag( 'destaque', 1 );
$subs_q  = orick_get_posts_by_tag( 'destaque-secundario', 3 );
$live_q  = orick_get_posts_by_tag( 'ao-vivo', 4 );
$right_q = orick_get_posts_by_tag( 'lateral-hero', 4 );
?>
<section class="os-hero">
  <div class="os-wrap os-hero-grid">

    <!-- LEAD -->
    <div class="os-lead">
      <?php if ( $lead_q->have_posts() ) : $lead_q->the_post(); ?>
        <a href="<?php the_permalink(); ?>" class="os-lead-img">
          <?php if ( has_post_thumbnail() ) the_post_thumbnail( 'large' );
          else echo '<div class="os-fallback"></div>'; ?>
          <div class="os-overlay">
            <div class="os-cat-label">Em destaque · <?php echo esc_html( orick_first_category_name() ); ?> · <?php the_author(); ?></div>
            <h1 class="os-h-lead" style="color:#fff;"><?php the_title(); ?></h1>
          </div>
        </a>
      <?php else : ?>
        <div class="os-lead-img"><div class="os-fallback"></div>
          <div class="os-overlay">
            <div class="os-cat-label">Em destaque</div>
            <h1 class="os-h-lead" style="color:#fff;">Marque um post com a tag <code>destaque</code> para aparecer aqui.</h1>
          </div>
        </div>
      <?php endif; wp_reset_postdata(); ?>

      <div class="os-lead-sublist">
        <?php if ( $subs_q->have_posts() ) : while ( $subs_q->have_posts() ) : $subs_q->the_post(); ?>
          <a class="os-lead-sub" href="<?php the_permalink(); ?>">
            <div class="os-lead-sub-cat"><?php echo esc_html( orick_first_category_name() ); ?></div>
            <div class="os-lead-sub-title"><?php the_title(); ?></div>
          </a>
        <?php endwhile; endif; wp_reset_postdata(); ?>
      </div>
    </div>

    <!-- MID — AO VIVO -->
    <div class="os-mid-col">
      <div class="os-mid-head">
        <span class="os-live-dot"></span>
        <span class="os-mid-head-title">AO VIVO</span>
        <span class="os-mid-head-time">Última atualização · <?php echo wp_date('H:i'); ?></span>
      </div>
      <?php if ( $live_q->have_posts() ) : while ( $live_q->have_posts() ) : $live_q->the_post(); ?>
        <a class="os-hl-item" href="<?php the_permalink(); ?>">
          <div class="os-kicker"><?php echo esc_html( mb_strtoupper( orick_first_category_name() ) ); ?></div>
          <div class="os-hl-title"><?php the_title(); ?></div>
          <div class="os-hl-meta"><?php echo esc_html( human_time_diff( get_the_time('U'), current_time('timestamp') ) ); ?> atrás · <?php echo esc_html( orick_reading_time() ); ?></div>
        </a>
      <?php endwhile; else : ?>
        <p style="color:var(--text-mute);font-size:12px;padding:20px 0;">Marque posts com a tag <code>ao-vivo</code> para aparecerem aqui.</p>
      <?php endif; wp_reset_postdata(); ?>
    </div>

    <!-- RIGHT -->
    <div class="os-right-col">
      <?php if ( $right_q->have_posts() ) : while ( $right_q->have_posts() ) : $right_q->the_post();
        $is_sponsored = has_tag( 'conteudo-marca' ); ?>
        <a class="os-small-card" href="<?php the_permalink(); ?>">
          <div class="os-small-thumb">
            <?php if ( has_post_thumbnail() ) the_post_thumbnail( 'thumbnail' );
            else echo '<div class="os-fallback"></div>'; ?>
          </div>
          <div class="os-small-card-body">
            <div class="os-small-card-cat <?php echo $is_sponsored ? 'sponsored' : ''; ?>">
              <?php echo $is_sponsored ? '● ' : ''; echo esc_html( orick_first_category_name() ); ?>
            </div>
            <div class="os-small-card-title"><?php the_title(); ?></div>
          </div>
        </a>
      <?php endwhile; endif; wp_reset_postdata(); ?>
    </div>
  </div>
</section>

<?php /* ---------- PAINEL DE MERCADO (brapi.dev — 3 painéis no estilo do mockup) ---------- */
$orick_quotes = orick_fetch_quotes();
$orick_ibov   = $orick_quotes['ibov']       ?? null;
$orick_stocks = $orick_quotes['stocks']     ?? [];
$orick_curr   = $orick_quotes['currencies'] ?? [];

$orick_has_data = $orick_ibov || ! empty( $orick_stocks ) || ! empty( $orick_curr );
?>
<section class="os-market">
  <div class="os-wrap">
    <div class="os-sec-head" style="padding-top:0;">
      <h2 class="os-sec-title">Mercado agora</h2>
      <span class="os-sec-link" style="pointer-events:none;opacity:.7;">Atualizado a cada 5 min · fonte: brapi.dev</span>
    </div>

    <?php if ( ! $orick_has_data ) : ?>
      <div class="os-panel" style="padding:32px;text-align:center;color:var(--text-mute);font-family:'JetBrains Mono',monospace;font-size:12px;">
        Cotações indisponíveis no momento.
      </div>
    <?php else : ?>

    <div class="os-market-layout">

      <?php /* PAINEL 1 — Ibovespa + sparkline */ ?>
      <div class="os-mkt-panel os-mkt-ibov">
        <div class="os-mkt-head">
          <span class="os-mkt-title">Ibovespa</span>
          <span class="os-mkt-badge">INTRADAY · DELAY 15MIN</span>
        </div>
        <?php if ( $orick_ibov ) :
          $ibov_up = ( $orick_ibov['chg'] ?? 0 ) >= 0;
        ?>
          <div class="os-ibov-price-row">
            <span class="os-ibov-label">IBOV</span>
            <span class="os-ibov-price"><?php echo esc_html( orick_fmt_price( $orick_ibov['price'] ) ); ?></span>
            <span class="os-ibov-chg <?php echo $ibov_up ? 'is-up' : 'is-down'; ?>">
              <?php echo $ibov_up ? '▲' : '▼'; ?> <?php echo esc_html( orick_fmt_chg( $orick_ibov['chg'] ) ); ?>
            </span>
          </div>
          <div class="os-ibov-spark">
            <?php echo orick_sparkline_svg( $orick_ibov['history'], 560, 110, $ibov_up ? '#3fa66a' : '#c94a3a' ); ?>
          </div>
          <div class="os-ibov-stats">
            <div><span>Abertura</span><strong><?php echo esc_html( orick_fmt_price( $orick_ibov['open'] ) ); ?></strong></div>
            <div><span>Mínima</span><strong><?php echo esc_html( orick_fmt_price( $orick_ibov['low'] ) ); ?></strong></div>
            <div><span>Máxima</span><strong><?php echo esc_html( orick_fmt_price( $orick_ibov['high'] ) ); ?></strong></div>
          </div>
        <?php else : ?>
          <div class="os-mkt-empty">Ibovespa indisponível</div>
        <?php endif; ?>
      </div>

      <?php /* PAINEL 2 — Índices & ETFs */ ?>
      <div class="os-mkt-panel">
        <div class="os-mkt-head">
          <span class="os-mkt-title">Índices &amp; ETFs</span>
          <span class="os-mkt-badge">B3</span>
        </div>
        <?php if ( ! empty( $orick_stocks ) ) : ?>
          <ul class="os-mkt-list">
            <?php foreach ( $orick_stocks as $s ) :
              $up = ( $s['chg'] ?? 0 ) >= 0;
            ?>
              <li>
                <span class="os-mkt-list-sym"><?php echo esc_html( $s['symbol'] ); ?></span>
                <span class="os-mkt-list-name"><?php echo esc_html( $s['name'] ); ?></span>
                <span class="os-mkt-list-price">R$ <?php echo esc_html( orick_fmt_price( $s['price'] ) ); ?></span>
                <span class="os-mkt-list-chg <?php echo $up ? 'is-up' : 'is-down'; ?>"><?php echo esc_html( orick_fmt_chg( $s['chg'] ) ); ?></span>
              </li>
            <?php endforeach; ?>
          </ul>
          <div class="os-mkt-foot">
            <span><?php echo count( $orick_stocks ); ?> ativos</span>
            <span>Atualizado <?php echo esc_html( date_i18n( 'H:i', current_time( 'timestamp' ) ) ); ?></span>
          </div>
        <?php else : ?>
          <div class="os-mkt-empty">Lista indisponível</div>
        <?php endif; ?>
      </div>

      <?php /* PAINEL 3 — Moedas (buscadas no lado do cliente via AwesomeAPI) */ ?>
      <div class="os-mkt-panel" id="os-currencies-panel">
        <div class="os-mkt-head">
          <span class="os-mkt-title">Moedas</span>
          <span class="os-mkt-badge">SPOT</span>
        </div>
        <ul class="os-mkt-list os-mkt-list-curr" id="os-currencies-list">
          <li data-pair="USDBRL"><span class="os-mkt-list-sym">DÓLAR</span><span class="os-mkt-list-price">—</span><span class="os-mkt-list-chg">—</span></li>
          <li data-pair="EURBRL"><span class="os-mkt-list-sym">EURO</span><span class="os-mkt-list-price">—</span><span class="os-mkt-list-chg">—</span></li>
          <li data-pair="GBPBRL"><span class="os-mkt-list-sym">LIBRA</span><span class="os-mkt-list-price">—</span><span class="os-mkt-list-chg">—</span></li>
          <li data-pair="JPYBRL"><span class="os-mkt-list-sym">IENE</span><span class="os-mkt-list-price">—</span><span class="os-mkt-list-chg">—</span></li>
        </ul>
        <div class="os-mkt-foot">
          <span id="os-curr-count">4 moedas</span>
          <span>vs. Real</span>
        </div>
      </div>
      <script>
      (function() {
        var CACHE_KEY = 'osCurrV1';
        var CACHE_TTL = 30 * 60 * 1000; // 30 min
        var fmt = function(n) {
          if (n === null || isNaN(n)) return '—';
          return 'R$ ' + n.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        };
        var fmtChg = function(n) {
          if (n === null || isNaN(n)) return '—';
          var s = (n >= 0 ? '+' : '') + n.toFixed(2) + '%';
          return s;
        };
        var render = function(data) {
          if (!data) return;
          var list = document.getElementById('os-currencies-list');
          if (!list) return;
          list.querySelectorAll('li').forEach(function(li) {
            var pair = li.getAttribute('data-pair');
            var row = data[pair];
            if (!row) return;
            var bid = parseFloat(row.bid);
            var chg = parseFloat(row.pctChange);
            li.querySelector('.os-mkt-list-price').textContent = fmt(bid);
            var chgEl = li.querySelector('.os-mkt-list-chg');
            chgEl.textContent = fmtChg(chg);
            chgEl.classList.remove('is-up', 'is-down');
            chgEl.classList.add(chg >= 0 ? 'is-up' : 'is-down');
          });
        };
        // 1) tenta cache
        try {
          var cached = JSON.parse(localStorage.getItem(CACHE_KEY) || 'null');
          if (cached && cached.ts && (Date.now() - cached.ts) < CACHE_TTL && cached.data) {
            render(cached.data);
          }
        } catch (e) {}
        // 2) busca fresh
        fetch('https://economia.awesomeapi.com.br/json/last/USD-BRL,EUR-BRL,GBP-BRL,JPY-BRL')
          .then(function(r) { return r.ok ? r.json() : null; })
          .then(function(data) {
            if (!data) return;
            render(data);
            try { localStorage.setItem(CACHE_KEY, JSON.stringify({ ts: Date.now(), data: data })); } catch (e) {}
          })
          .catch(function() {});
      })();
      </script>

    </div>

    <?php endif; ?>
  </div>
</section>

<?php
/* ---------- EM ALTA ---------- */
$em_alta = orick_get_posts_by_tag( 'em-alta', 4 );
?>
<section class="os-wrap os-cat-row">
  <div class="os-sec-head" style="padding-top:16px;">
    <h2 class="os-sec-title">Em alta</h2>
    <a href="<?php echo esc_url( home_url('/tag/em-alta/') ); ?>" class="os-sec-link">Ver todos →</a>
  </div>
  <div class="os-cards-grid">
    <?php if ( $em_alta->have_posts() ) : while ( $em_alta->have_posts() ) : $em_alta->the_post(); ?>
      <a class="os-card" href="<?php the_permalink(); ?>">
        <div class="os-card-img">
          <?php if ( has_post_thumbnail() ) the_post_thumbnail( 'medium_large' );
          else echo '<div class="os-fallback"></div>'; ?>
        </div>
        <div class="os-card-cat"><?php echo esc_html( orick_first_category_name() ); ?></div>
        <div class="os-card-title"><?php the_title(); ?></div>
        <div class="os-card-meta">
          <span><?php the_author(); ?></span>
          <span class="os-dot"></span>
          <span><?php echo esc_html( orick_reading_time() ); ?></span>
        </div>
      </a>
    <?php endwhile; endif; wp_reset_postdata(); ?>
  </div>
</section>

<?php
/* ---------- CATEGORIA ROWS ---------- */
$cat_rows = [
  [ 'slug' => 'materiais',   'title' => 'Materiais',   'link' => 'Todos os materiais' ],
  [ 'slug' => 'ferramentas', 'title' => 'Ferramentas', 'link' => 'Ver ferramentas' ],
];
foreach ( $cat_rows as $row ) :
  // Se for "ferramentas" e o plugin Orick Ferramentas estiver ativo, usa o CPT próprio
  if ( $row['slug'] === 'ferramentas' && post_type_exists( 'ferramenta' ) ) {
      $q = new WP_Query( [
          'post_type'      => 'ferramenta',
          'posts_per_page' => 4,
          'post_status'    => 'publish',
          'meta_query'     => [ [
              'key'     => '_orick_destaque_home',
              'value'   => '1',
              'compare' => '=',
          ] ],
      ] );
      // Fallback: se nenhuma marcada, pega as 4 mais recentes
      if ( ! $q->have_posts() ) {
          wp_reset_postdata();
          $q = new WP_Query( [ 'post_type' => 'ferramenta', 'posts_per_page' => 4, 'post_status' => 'publish' ] );
      }
      $row['link_url'] = get_post_type_archive_link( 'ferramenta' );
  } else {
      $q = orick_get_posts_by_cat( $row['slug'], 4 );
      $row['link_url'] = home_url( '/categoria/' . $row['slug'] . '/' );
  }
?>
  <?php $is_tools = ( $row['slug'] === 'ferramentas' && function_exists( 'oricksilva_render_tool_card' ) ); ?>
  <section class="os-wrap os-cat-row">
    <div class="os-sec-head" style="padding-top:0;">
      <h2 class="os-sec-title"><?php echo esc_html( $row['title'] ); ?></h2>
      <a href="<?php echo esc_url( $row['link_url'] ); ?>" class="os-sec-link"><?php echo esc_html( $row['link'] ); ?> →</a>
    </div>
    <div class="<?php echo $is_tools ? 'tool-grid tool-grid--home' : 'os-cards-grid'; ?>">
      <?php if ( $q->have_posts() ) : while ( $q->have_posts() ) : $q->the_post(); ?>
        <?php if ( $is_tools ) : ?>
          <?php oricksilva_render_tool_card( get_the_ID() ); ?>
        <?php else : ?>
          <a class="os-card" href="<?php the_permalink(); ?>">
            <div class="os-card-img">
              <?php if ( has_post_thumbnail() ) the_post_thumbnail( 'medium_large' );
              else echo '<div class="os-fallback"></div>'; ?>
            </div>
            <div class="os-card-cat"><?php echo esc_html( $row['title'] ); ?></div>
            <div class="os-card-title"><?php the_title(); ?></div>
            <div class="os-card-meta">
              <span><?php the_author(); ?></span>
              <span class="os-dot"></span>
              <span><?php echo esc_html( orick_reading_time() ); ?></span>
            </div>
          </a>
        <?php endif; ?>
      <?php endwhile; else : ?>
        <p style="color:var(--text-mute);font-size:12px;grid-column:1/-1;">
          <?php if ( $is_tools ) : ?>
            Cadastre uma ferramenta em <strong>Ferramentas → Adicionar nova</strong>.
          <?php else : ?>
            Publique posts na categoria <code><?php echo esc_html( $row['slug'] ); ?></code>.
          <?php endif; ?>
        </p>
      <?php endif; wp_reset_postdata(); ?>
    </div>
  </section>
<?php endforeach; ?>

<?php
/* ---------- COLUNISTAS (usuários com role "columnist") ---------- */
$columnists = get_users( [ 'role' => 'columnist', 'number' => 4 ] );
?>
<section class="os-colunistas">
  <div class="os-wrap">
    <div class="os-sec-head">
      <h2 class="os-sec-title">Colunistas fixos</h2>
      <a href="#" class="os-sec-link">Ver todos →</a>
    </div>
    <div class="os-col-grid">
      <?php if ( $columnists ) : foreach ( $columnists as $u ) :
        $last = get_posts( [ 'author' => $u->ID, 'numberposts' => 1 ] );
        $bio  = orick_columnist_bio( $u->ID );
      ?>
        <div class="os-col-card">
          <div class="os-col-head">
            <div class="os-col-avatar">
              <?php echo get_avatar( $u->ID, 56 ) ?: '<span>' . esc_html( orick_initials( $u->display_name ) ) . '</span>'; ?>
            </div>
            <div>
              <div class="os-col-name"><?php echo esc_html( $u->display_name ); ?></div>
              <div class="os-col-role"><?php echo esc_html( $bio ? wp_trim_words( $bio, 4, '' ) : 'Colunista' ); ?></div>
            </div>
          </div>
          <div class="os-col-quote">
            <?php if ( $last ) echo '"' . esc_html( wp_trim_words( $last[0]->post_title, 18, '...' ) ) . '"';
            else echo '"Aguardando primeira coluna publicada."'; ?>
          </div>
          <a href="<?php echo esc_url( get_author_posts_url( $u->ID ) ); ?>" class="os-col-link">Ler coluna →</a>
        </div>
      <?php endforeach; else : ?>
        <p style="color:var(--text-mute);font-size:12px;grid-column:1/-1;">
          Vá em <strong>Usuários → Adicionar novo</strong> e atribua a role <code>Colunista</code> para aparecerem aqui.
        </p>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php
/* ---------- VÍDEOS + PODCAST ---------- */
$videos_q = new WP_Query( [
    'post_type'      => post_type_exists( 'video' ) ? 'video' : 'post',
    'post_status'    => 'publish',
    'posts_per_page' => 4,
    'orderby'        => 'date',
    'order'          => 'DESC',
] );
$podcast_q     = new WP_Query( [
    'post_type'      => post_type_exists( 'episodio' ) ? 'episodio' : 'post',
    'posts_per_page' => 4,
    'orderby'        => 'date',
    'order'          => 'DESC',
] );
?>
<section class="os-wrap os-media-split">
  <!-- Vídeos -->
  <div>
    <div class="os-sec-head" style="padding-top:0;">
      <h2 class="os-sec-title">Vídeos</h2>
      <a href="<?php echo esc_url( post_type_exists( 'video' ) ? get_post_type_archive_link( 'video' ) : home_url('/categoria/videos/') ); ?>" class="os-sec-link">Canal completo →</a>
    </div>
    <?php if ( $videos_q->have_posts() ) :
      $videos_q->the_post();
      $v = orick_video_data( get_the_ID() ); ?>
      <a class="os-video-big" href="<?php echo esc_url( $v['watch_url'] ); ?>" target="_blank" rel="noopener">
        <?php if ( $v['thumb'] ) : ?>
          <img src="<?php echo esc_url( $v['thumb'] ); ?>" alt="" loading="lazy">
        <?php else : ?>
          <div class="os-fallback"></div>
        <?php endif; ?>
        <div class="os-play-circle"><svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></div>
        <?php if ( $v['duration'] ) : ?>
          <span class="os-video-dur"><?php echo esc_html( $v['duration'] ); ?></span>
        <?php endif; ?>
        <div class="os-caption">
          <div class="os-card-cat"><?php echo esc_html( $v['kicker'] ?: 'Vídeo' ); ?></div>
          <div class="os-card-title" style="color:#fff;font-size:22px;margin-top:6px;"><?php the_title(); ?></div>
        </div>
      </a>
      <div class="os-video-list">
        <?php while ( $videos_q->have_posts() ) : $videos_q->the_post();
          $vi = orick_video_data( get_the_ID() ); ?>
          <a class="os-video-item" href="<?php echo esc_url( $vi['watch_url'] ); ?>" target="_blank" rel="noopener">
            <div class="os-video-thumb">
              <?php if ( $vi['thumb'] ) : ?>
                <img src="<?php echo esc_url( $vi['thumb'] ); ?>" alt="" loading="lazy">
              <?php else : ?>
                <div class="os-fallback"></div>
              <?php endif; ?>
              <div class="os-mini-play"><svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></div>
              <?php if ( $vi['duration'] ) : ?>
                <span class="os-video-dur-mini"><?php echo esc_html( $vi['duration'] ); ?></span>
              <?php endif; ?>
            </div>
            <div>
              <div class="os-card-cat" style="font-size:10px;"><?php echo esc_html( $vi['kicker'] ?: 'Episódio' ); ?></div>
              <div class="os-card-title" style="font-size:15px;margin-top:3px;"><?php the_title(); ?></div>
            </div>
          </a>
        <?php endwhile; ?>
      </div>
    <?php else : ?>
      <p style="color:var(--text-mute);font-size:12px;">Publique na categoria <code>videos</code> e preencha "URL do YouTube".</p>
    <?php endif; wp_reset_postdata(); ?>
  </div>

  <!-- Podcast -->
  <div>
    <div class="os-sec-head" style="padding-top:0;">
      <h2 class="os-sec-title">Podcast</h2>
      <a href="<?php echo esc_url( home_url( '/podcast/' ) ); ?>" class="os-sec-link">Todos episódios →</a>
    </div>
    <?php
    $podcast_ids = [];
    if ( $podcast_q->have_posts() ) {
        while ( $podcast_q->have_posts() ) { $podcast_q->the_post(); $podcast_ids[] = get_the_ID(); }
        wp_reset_postdata();
    }

    if ( $podcast_ids && function_exists( 'orick_episodio_data' ) ) :
        $hero_ep = orick_episodio_data( $podcast_ids[0] );
        $rest    = array_slice( $podcast_ids, 1 );
        $total   = function_exists( 'orick_ep_total' ) ? orick_ep_total() : count( $podcast_ids );
        $i       = max( 1, $total - 1 );
        orick_ep_render_featured( $hero_ep );
        if ( $rest ) : ?>
          <div class="os-podcast-list">
            <?php foreach ( $rest as $pid ) : orick_ep_render_item( orick_episodio_data( $pid ), $i-- ); endforeach; ?>
          </div>
        <?php endif;
    else : ?>
      <p style="color:var(--text-mute);font-size:12px;">Publique um episódio em <strong>Podcast → Adicionar novo</strong> (menu do WP). O plugin Orick Ferramentas precisa estar ativo.</p>
    <?php endif; ?>
  </div>
</section>

<?php
/* ---------- EVENTOS ---------- */
$eventos_q = orick_get_posts_by_cat( 'eventos', 1 );
?>
<section class="os-eventos">
  <div class="os-wrap">
    <div class="os-sec-head" style="padding-top:0;">
      <h2 class="os-sec-title">Eventos</h2>
      <a href="<?php echo esc_url( home_url('/categoria/eventos/') ); ?>" class="os-sec-link">Calendário completo →</a>
    </div>
    <?php if ( $eventos_q->have_posts() ) : $eventos_q->the_post(); ?>
      <div class="os-evento-card">
        <div class="os-evento-art">
          <?php if ( has_post_thumbnail() ) the_post_thumbnail( 'large' ); ?>
        </div>
        <div class="os-evento-body">
          <div class="os-evento-date"><?php echo esc_html( get_the_date( 'd \d\e F, Y' ) ); ?></div>
          <h3 class="os-evento-title"><?php the_title(); ?></h3>
          <div style="color:var(--text-dim);font-size:15px;line-height:1.55;"><?php echo wp_trim_words( get_the_excerpt(), 40 ); ?></div>
          <div style="display:flex;gap:10px;margin-top:8px;">
            <a href="<?php the_permalink(); ?>" class="os-btn">Saber mais</a>
          </div>
        </div>
      </div>
    <?php else : ?>
      <p style="color:var(--text-mute);font-size:12px;">Publique na categoria <code>eventos</code>.</p>
    <?php endif; wp_reset_postdata(); ?>
  </div>
</section>

<?php get_footer(); ?>

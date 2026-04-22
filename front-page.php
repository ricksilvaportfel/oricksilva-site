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

<?php /* ---------- PAINEL DE MERCADO (brapi.dev — dados reais, visual do mockup) ---------- */
$orick_quotes = orick_fetch_quotes();
?>
<section class="os-market">
  <div class="os-wrap">
    <div class="os-sec-head" style="padding-top:0;">
      <h2 class="os-sec-title">Mercado agora</h2>
      <span class="os-sec-link" style="pointer-events:none;opacity:.7;">Atualizado a cada 5 min · fonte: brapi.dev</span>
    </div>

    <?php if ( empty( $orick_quotes ) ) : ?>
      <div class="os-panel" style="padding:32px;text-align:center;color:var(--text-mute);font-family:'JetBrains Mono',monospace;font-size:12px;">
        Cotações indisponíveis no momento. Tente recarregar em instantes.
      </div>
    <?php else : ?>
      <div class="os-quote-grid">
        <?php foreach ( $orick_quotes as $q ) :
          $is_up   = isset( $q['chg'] ) && $q['chg'] >= 0;
          $chg_cls = $q['chg'] === null ? 'is-flat' : ( $is_up ? 'is-up' : 'is-down' );
        ?>
          <div class="os-quote">
            <div class="os-quote-top">
              <span class="os-quote-label"><?php echo esc_html( $q['label'] ); ?></span>
              <span class="os-quote-sub"><?php echo esc_html( $q['sub'] ); ?></span>
            </div>
            <div class="os-quote-price">
              <span class="os-quote-currency"><?php echo esc_html( $q['currency'] ); ?></span>
              <?php echo esc_html( orick_fmt_price( $q['price'] ) ); ?>
            </div>
            <div class="os-quote-chg <?php echo esc_attr( $chg_cls ); ?>">
              <?php echo esc_html( orick_fmt_chg( $q['chg'] ) ); ?>
              <span class="os-quote-spark" aria-hidden="true">
                <?php if ( $chg_cls === 'is-up' ) echo '▲'; elseif ( $chg_cls === 'is-down' ) echo '▼'; else echo '◆'; ?>
              </span>
            </div>
          </div>
        <?php endforeach; ?>
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
  $q = orick_get_posts_by_cat( $row['slug'], 4 );
?>
  <section class="os-wrap os-cat-row">
    <div class="os-sec-head" style="padding-top:0;">
      <h2 class="os-sec-title"><?php echo esc_html( $row['title'] ); ?></h2>
      <a href="<?php echo esc_url( home_url( '/categoria/' . $row['slug'] . '/' ) ); ?>" class="os-sec-link"><?php echo esc_html( $row['link'] ); ?> →</a>
    </div>
    <div class="os-cards-grid">
      <?php if ( $q->have_posts() ) : while ( $q->have_posts() ) : $q->the_post(); ?>
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
      <?php endwhile; else : ?>
        <p style="color:var(--text-mute);font-size:12px;grid-column:1/-1;">Publique posts na categoria <code><?php echo esc_html( $row['slug'] ); ?></code>.</p>
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
$videos_q      = orick_get_posts_by_cat( 'videos', 4 );
$podcast_q     = orick_get_posts_by_cat( 'podcast', 5 );
?>
<section class="os-wrap os-media-split">
  <!-- Vídeos -->
  <div>
    <div class="os-sec-head" style="padding-top:0;">
      <h2 class="os-sec-title">Vídeos</h2>
      <a href="<?php echo esc_url( home_url('/categoria/videos/') ); ?>" class="os-sec-link">Canal completo →</a>
    </div>
    <?php if ( $videos_q->have_posts() ) :
      $videos_q->the_post(); ?>
      <a class="os-video-big" href="<?php the_permalink(); ?>">
        <?php if ( has_post_thumbnail() ) the_post_thumbnail( 'large' ); else echo '<div class="os-fallback"></div>'; ?>
        <div class="os-play-circle"><svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></div>
        <div class="os-caption">
          <div class="os-card-cat">Vídeo</div>
          <div class="os-card-title" style="color:#fff;font-size:22px;margin-top:6px;"><?php the_title(); ?></div>
        </div>
      </a>
      <div class="os-video-list">
        <?php while ( $videos_q->have_posts() ) : $videos_q->the_post(); ?>
          <a class="os-video-item" href="<?php the_permalink(); ?>">
            <div class="os-video-thumb">
              <?php if ( has_post_thumbnail() ) the_post_thumbnail( 'thumbnail' ); else echo '<div class="os-fallback"></div>'; ?>
              <div class="os-mini-play"><svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg></div>
            </div>
            <div>
              <div class="os-card-cat" style="font-size:10px;">Episódio</div>
              <div class="os-card-title" style="font-size:15px;margin-top:3px;"><?php the_title(); ?></div>
            </div>
          </a>
        <?php endwhile; ?>
      </div>
    <?php else : ?>
      <p style="color:var(--text-mute);font-size:12px;">Publique na categoria <code>videos</code>.</p>
    <?php endif; wp_reset_postdata(); ?>
  </div>

  <!-- Podcast -->
  <div>
    <div class="os-sec-head" style="padding-top:0;">
      <h2 class="os-sec-title">Podcast</h2>
      <a href="<?php echo esc_url( home_url('/categoria/podcast/') ); ?>" class="os-sec-link">Todos episódios →</a>
    </div>
    <?php if ( $podcast_q->have_posts() ) :
      $podcast_q->the_post(); ?>
      <a class="os-podcast-featured" href="<?php the_permalink(); ?>">
        <div class="os-podcast-body">
          <div>
            <div class="os-podcast-ep">Episódio destacado</div>
            <h3 class="os-podcast-title"><?php the_title(); ?></h3>
            <div style="font-size:12px;color:var(--text-dim);">Por <?php the_author(); ?></div>
          </div>
        </div>
      </a>
      <div class="os-podcast-list">
        <?php $i = 1; while ( $podcast_q->have_posts() ) : $podcast_q->the_post(); ?>
          <a class="os-podcast-item" href="<?php the_permalink(); ?>">
            <span class="os-podcast-idx">#<?php echo str_pad( $i, 3, '0', STR_PAD_LEFT ); $i++; ?></span>
            <div>
              <div class="os-podcast-item-title"><?php the_title(); ?></div>
              <div style="font-family:'JetBrains Mono',monospace;font-size:10.5px;color:var(--text-mute);margin-top:2px;">Spotify · Apple · YouTube</div>
            </div>
            <span style="font-family:'JetBrains Mono',monospace;font-size:11px;color:var(--text-dim);">▶</span>
          </a>
        <?php endwhile; ?>
      </div>
    <?php else : ?>
      <p style="color:var(--text-mute);font-size:12px;">Publique na categoria <code>podcast</code>.</p>
    <?php endif; wp_reset_postdata(); ?>
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

<footer class="os-site-footer" id="newsletter">
  <div class="os-wrap">

    <!-- Newsletter CTA antes do footer -->
    <div class="os-newsletter-cta" style="border:none;padding:0 0 48px;">
      <div class="os-label">Newsletter gratuita · Toda segunda, 07:00</div>
      <h2>Uma leitura de <em>12 minutos</em> que substitui metade das suas reuniões da semana.</h2>
      <p>Análise, táticas de prospecção, e os bastidores de quem fechou muito na semana. Sem lorota, sem vender curso no meio.</p>
      <form class="os-newsletter-form" method="post">
        <?php wp_nonce_field( 'os_newsletter_sub', '_os_nonce' ); ?>
        <input type="hidden" name="os_action" value="newsletter_subscribe">
        <input type="email" name="email" placeholder="seu@email.com" required>
        <button type="submit">Assinar grátis →</button>
      </form>
      <?php if ( ! empty( $_GET['ns'] ) ) : ?>
        <p class="os-newsletter-msg os-newsletter-<?php echo $_GET['ns'] === 'ok' ? 'ok' : 'err'; ?>">
          <?php
          $ns_msgs = [
            'ok'    => 'Pronto! Você está na lista. 🎯',
            'exist' => 'Esse e-mail já está cadastrado.',
            'fail'  => 'Algo deu errado. Tente novamente.',
            'empty' => 'Preencha seu e-mail.',
          ];
          echo esc_html( $ns_msgs[ $_GET['ns'] ] ?? $ns_msgs['fail'] );
          ?>
        </p>
      <?php endif; ?>
    </div>

    <div class="os-footer-grid">
      <div class="os-footer-brand">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" style="display:flex;align-items:center;gap:10px;text-decoration:none;color:inherit;">
          <?php
          $os_logo_url = get_theme_mod( 'os_logo_image', '' );
          $os_logo_h   = intval( get_theme_mod( 'os_logo_height', 40 ) );
          if ( $os_logo_url ) : ?>
            <img src="<?php echo esc_url( $os_logo_url ); ?>" alt="<?php bloginfo( 'name' ); ?>" style="max-height:<?php echo esc_attr( $os_logo_h ); ?>px;">
          <?php else : ?>
            <div class="os-brand-mark">RS</div>
            <div class="os-brand-name">O <em>Rick</em> Silva</div>
          <?php endif; ?>
        </a>
        <p>Conteúdo editorial para assessores de investimentos e quem quer pensar sobre dinheiro com a cabeça mais fria.</p>
        <?php $os_profiles = function_exists( 'os_social_profiles' ) ? os_social_profiles() : []; ?>
        <?php if ( $os_profiles ) : ?>
          <div class="os-footer-socials">
            <?php foreach ( $os_profiles as $key => $p ) : ?>
              <a href="<?php echo esc_url( $p['url'] ); ?>" target="_blank" rel="noopener me" aria-label="<?php echo esc_attr( $p['label'] ); ?>" title="<?php echo esc_attr( $p['label'] ); ?>">
                <?php echo os_social_icon_svg( $key ); ?>
              </a>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <?php
      $footer_cols = [
        'footer_1' => [ 'O Rick Silva', [ 'Sobre', 'Contato', 'Imprensa', 'Anuncie', 'Fale conosco' ] ],
        'footer_2' => [ 'Conteúdo', [ 'Artigos', 'Colunistas', 'Podcast', 'Vídeos', 'Newsletter' ] ],
        'footer_3' => [ 'Aprenda', [ 'Materiais', 'Ferramentas', 'E-books', 'Planilhas', 'Cursos' ] ],
        'footer_4' => [ 'Legal', [ 'Política de Privacidade', 'Política de Cookies', 'Preferências', 'Termos de uso', 'LGPD' ] ],
      ];
      foreach ( $footer_cols as $loc => $cfg ) :
      ?>
        <div class="os-footer-col">
          <h4><?php echo esc_html( $cfg[0] ); ?></h4>
          <?php if ( has_nav_menu( $loc ) ) {
              wp_nav_menu( [ 'theme_location' => $loc, 'container' => false, 'menu_class' => '', 'depth' => 1 ] );
          } else { ?>
            <ul>
              <?php foreach ( $cfg[1] as $item ) : ?>
                <li><a href="#"><?php echo esc_html( $item ); ?></a></li>
              <?php endforeach; ?>
            </ul>
          <?php } ?>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="os-footer-bottom">
      <span>© <?php echo date('Y'); ?> oricksilva.com.br · Todos os direitos reservados</span>
      <span>feito com cuidado editorial</span>
    </div>
    <p class="os-footer-legal">O conteúdo deste site tem caráter informativo e educacional. Não constitui recomendação de investimento. Antes de tomar qualquer decisão, consulte um profissional credenciado. O Rick Silva não se responsabiliza por perdas, danos ou lucros cessantes decorrentes do uso das informações aqui disponibilizadas.</p>
  </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>

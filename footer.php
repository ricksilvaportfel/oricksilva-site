<footer class="os-site-footer" id="newsletter">
  <div class="os-wrap">

    <!-- Newsletter CTA antes do footer -->
    <div class="os-newsletter-cta" style="border:none;padding:0 0 48px;">
      <div class="os-label">Newsletter gratuita · Toda segunda, 07:00</div>
      <h2>Uma leitura de <em>12 minutos</em> que substitui metade das suas reuniões da semana.</h2>
      <p>Análise, táticas de prospecção, e os bastidores de quem fechou muito na semana. Sem lorota, sem vender curso no meio.</p>
      <form class="os-newsletter-form" action="#" method="post">
        <input type="email" name="email" placeholder="seu@email.com" required>
        <button type="submit">Assinar grátis →</button>
      </form>
    </div>

    <div class="os-footer-grid">
      <div class="os-footer-brand">
        <div style="display:flex;align-items:center;gap:10px;">
          <div class="os-brand-mark">RS</div>
          <div class="os-brand-name">O <em>Rick</em> Silva</div>
        </div>
        <p>Conteúdo editorial para assessores de investimentos e quem quer pensar sobre dinheiro com a cabeça mais fria.</p>
        <div class="os-footer-socials">
          <a href="#">IG</a><a href="#">YT</a><a href="#">IN</a><a href="#">X</a><a href="#">TG</a><a href="#">SP</a>
        </div>
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

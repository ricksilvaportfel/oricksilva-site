# O Rick Silva — Tema filho WordPress

Tema filho de **Hello Elementor** com home editorial customizada que puxa artigos automaticamente por tags/categorias. Design: paleta terracota/creme sobre fundo escuro, tipografia Fraunces + Inter.

---

## 📦 O que vai dentro

```
oricksilva-child/
├── style.css          ← Design completo (cores, tipografia, grids, responsivo)
├── functions.php      ← Registra fontes, cria role Colunista, cria tags/categorias
├── header.php         ← Topbar + subnav + busca + CTA Newsletter
├── footer.php         ← Newsletter CTA + rodapé multi-coluna
└── front-page.php     ← Home dinâmica (hero, mercado, em alta, colunistas, etc)
```

---

## 🚀 Instalação (HostGator + cPanel)

### Passo 1 — Instale o tema pai
1. Entre em `oricksilva.com.br/wp-admin`
2. **Aparência → Temas → Adicionar novo**
3. Busque **"Hello Elementor"**, instale e **ATIVE**

### Passo 2 — Suba o tema filho
**Opção A — Painel WordPress:**
1. Compacte a pasta `oricksilva-child` em um `.zip`
2. **Aparência → Temas → Adicionar novo → Enviar tema**
3. Escolha o `.zip` e instale
4. **Ative** o tema "O Rick Silva — Child"

**Opção B — FTP/cPanel (recomendado):**
1. Acesse **cPanel do HostGator → Gerenciador de Arquivos**
2. Vá até `public_html/wp-content/themes/`
3. Envie a pasta `oricksilva-child` inteira (não zipada)
4. No WP: **Aparência → Temas → Ativar "O Rick Silva — Child"**

### Passo 3 — Configure permalinks
- **Configurações → Links Permanentes → Nome do post** → Salvar
- Isso garante URLs limpas tipo `oricksilva.com.br/meu-artigo/`

### Passo 4 — Defina a home como front-page
- **Configurações → Leitura**
- "Sua homepage exibe" → **Uma página estática**
- Crie uma página "Home" vazia → selecione ela como Homepage

---

## 🏷️ Como publicar um artigo e fazer ele aparecer no lugar certo

Tudo é controlado por **tags** e **categorias**. O tema já cria elas automaticamente ao ativar.

### Categorias (menu principal)
| Categoria   | URL                          |
|-------------|------------------------------|
| Artigos     | `/categoria/artigos/`        |
| Colunistas  | `/categoria/colunistas/`     |
| Materiais   | `/categoria/materiais/`      |
| Ferramentas | `/categoria/ferramentas/`    |
| Vídeos      | `/categoria/videos/`         |
| Podcast     | `/categoria/podcast/`        |
| Eventos     | `/categoria/eventos/`        |

### Tags (controlam onde aparece na HOME)
| Tag                    | Onde aparece na home                            | Limite |
|------------------------|------------------------------------------------|--------|
| `destaque`             | Hero principal (manchete grande à esquerda)    | 1 post |
| `destaque-secundario`  | 3 subchamadas abaixo do hero                   | 3 posts|
| `ao-vivo`              | Coluna do meio "AO VIVO"                       | 4 posts|
| `lateral-hero`         | 4 cards pequenos à direita do hero             | 4 posts|
| `em-alta`              | Grid "Em alta" (4 cards grandes)               | 4 posts|
| `conteudo-marca`       | Marca um post como patrocinado (ponto laranja) | -      |

### Exemplo prático

**Quero que um artigo apareça como manchete principal:**
1. **Posts → Adicionar novo**
2. Título, conteúdo, imagem destacada
3. **Categoria:** Artigos
4. **Tags:** `destaque`
5. Publicar → aparece no hero automaticamente

**Quero que apareça na seção "Em alta":**
- Categoria: Artigos · Tag: `em-alta`

**Quero um material de download na seção Materiais:**
- Categoria: Materiais · (opcional) Tag: `destaque-secundario` se quiser destacar

> 💡 Um post pode ter várias tags. Se marcar `destaque` + `em-alta`, aparece nos dois lugares.

---

## 👥 Cadastrando colunistas

1. **Usuários → Adicionar novo**
2. Preenche nome, email, senha
3. **Função:** selecione **"Colunista"** (role criada pelo tema)
4. No perfil do usuário: **adicione descrição/bio** (aparece abaixo do nome na home)
5. **Foto de perfil:** configure via **Gravatar.com** com o mesmo email
6. Salvar

A seção "Colunistas fixos" da home mostra os 4 primeiros usuários com role Colunista, junto do título do último post de cada um.

---

## 💹 Painel de mercado (cotações)

Como WordPress não tem cotações nativas, instale um destes plugins gratuitos:

### Opção 1 — TradingView Widgets (recomendado)
1. **Plugins → Adicionar novo** → busque "TradingView"
2. Instale **"TradingView Widgets for WordPress"**
3. Configure um widget tipo "Ticker Tape" ou "Mini Chart"
4. Abra `front-page.php` linha ~100 e troque:
   ```php
   echo '<div>...placeholder...</div>';
   ```
   por:
   ```php
   echo do_shortcode('[tradingview_widget]');
   ```

### Opção 2 — Stock Ticker
Plugin "Stock Ticker" do Urosevic — mais simples, ticker rolante.

---

## 📧 Newsletter

O formulário no footer e no CTA aponta pra `#newsletter` por padrão. Pra integrar:

- **Mailchimp for WordPress** (grátis) → cola o shortcode dentro de `footer.php`
- **RD Station** → tem plugin oficial
- **ConvertKit** → embed direto via shortcode

Procure por `<form class="os-newsletter-form">` no `footer.php` e substitua pelo shortcode do plugin.

---

## ✏️ Personalizando

### Trocar o logo
Abra `header.php` e `footer.php`. Procure por:
```html
<div class="os-brand-mark">RS</div>
<div class="os-brand-name">O <em>Rick</em> Silva</div>
```
Troque por `<img src="<?php echo get_stylesheet_directory_uri(); ?>/logo.svg">` e coloque seu `logo.svg` dentro da pasta do tema.

### Mudar cores
Abra `style.css` no topo, seção `:root`. Edite as variáveis `--accent`, `--bg`, etc.

### Mudar fontes
Em `functions.php`, linha 14 tem a URL do Google Fonts. Edite se quiser outras famílias.

---

## 🛠️ Troubleshooting

**"Página em branco após ativar"**
→ Provavelmente o tema pai Hello Elementor não está instalado. Instale primeiro.

**"Tags/categorias não apareceram"**
→ Desative e reative o tema. A criação roda no hook `after_switch_theme`.

**"URLs aparecem como `?p=123`"**
→ Configurações → Links Permanentes → "Nome do post" → Salvar.

**"Home mostra posts genéricos em vez do layout"**
→ Configurações → Leitura → "Uma página estática" → selecione a página "Home".

---

Qualquer dúvida, me chama.

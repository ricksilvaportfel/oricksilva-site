# Orick Ferramentas — Plugin WordPress

Plugin editorial completo: **Ferramentas** (simuladores com gate de cadastro), **Materiais** (e-books/planilhas com download), **Vídeos**, **Podcast**, **Eventos** e **Colunistas**. Inclui captura de leads, webhook assinado e admin próprio.

## Pré-requisitos

- WordPress 6.0+
- PHP 7.4+
- Tema ativo compatível (funciona com qualquer tema — feito pra casar visualmente com o tema `oricksilva-child`)

## Instalação

1. No painel do WordPress, vá em **Plugins → Adicionar novo → Enviar plugin**
2. Selecione o arquivo `orick-ferramentas.zip`
3. Clique em **Instalar agora** e depois em **Ativar**
4. Na ativação, o plugin cria automaticamente:
   - Tabela `wp_orick_leads`
   - CPT **Ferramenta** (menu lateral "Ferramentas")
   - Taxonomia **Categorias de Ferramenta**
   - Role customizada pra cadastrados (via cookie próprio — não usa `wp_users`)
   - Opções default (webhook vazio, secret HMAC gerado, sessão de 365 dias)

## Uso

### 1. Criar uma ferramenta

**Ferramentas → Adicionar nova**

Campos do metabox "Configurações da ferramenta":

| Campo | O que faz |
|---|---|
| **Simulador** | Goal Based / Planejamento Comercial / Fee vs Commission / Nenhum |
| **Preço** | Gratuito / Freemium / Pago |
| **Exige cadastro** | Se marcado, só acessa após cadastro+login |
| **Destaque na home** | Aparece na seção "Ferramentas" da home |
| **Link externo** | Pra ferramentas fora do site (abre em nova aba) |
| **Como usar** | Texto curto antes do simulador |

### 2. Fluxo do lead

1. Lead acessa `/ferramentas/nome-da-ferramenta/`
2. Se `Exige cadastro=sim` e não logado → vê abas **Criar cadastro** / **Já tenho conta**
3. Cadastra (nome, email, telefone, CPF validado, profissão, AuM se for advisor, senha 8+)
4. Webhook dispara pro endpoint configurado
5. Lead vai pro simulador automaticamente

### 3. Admin de leads

**Ferramentas → Leads capturados**

- Lista com filtros (busca, profissão, AuM mínimo, período)
- Cards de totais no topo (total, AuM declarado, por profissão)
- Export CSV (com BOM UTF-8, separador `;` — abre direto no Excel BR)
- Retry de webhook individual (botão ↻)
- Status do webhook (✅ ❌ ⏳) + resposta HTTP

### 4. Webhook

**Ferramentas → Configurações**

- **URL do Webhook:** URL pra receber os leads (deixe vazio pra desativar)
- **Secret:** HMAC-SHA256 gerado automaticamente. Copie pra validar no lado do receptor.
- **Sessão (dias):** Validade do cookie de login (padrão 365).
- **Botão "Enviar POST de teste":** dispara payload de exemplo

**Formato do POST:**

```http
POST https://seu-endpoint.com
Content-Type: application/json
X-Orick-Signature: <hmac-sha256 do body com o Secret>
X-Orick-Event: lead.created

{
  "lead_id": 123,
  "nome": "João Silva",
  "email": "joao@example.com",
  "telefone": "11999999999",
  "cpf": "12345678900",
  "profissao": "assessor",
  "profissao_outra": "",
  "aum_atendido": 5000000.00,
  "ip": "189.0.0.1",
  "user_agent": "Mozilla/5.0...",
  "created_at": "2026-04-22 14:00:00",
  "origem": "oricksilva.com.br/ferramentas"
}
```

**Validar assinatura no receptor (exemplo Node.js/n8n):**

```js
const crypto = require('crypto');
const sig = req.headers['x-orick-signature'];
const expected = crypto.createHmac('sha256', SECRET).update(req.rawBody).digest('hex');
if (sig !== expected) return res.status(403).send('invalid signature');
```

### 5. Shortcodes

Você pode usar em qualquer página do WP:

| Shortcode | Uso |
|---|---|
| `[orick_ferr_grid count="6"]` | Grid das 6 ferramentas mais recentes |
| `[orick_ferr_grid destaque="1" count="3"]` | Só as marcadas com "Destaque na home" |
| `[orick_ferr_cadastro]` | Form de cadastro standalone |
| `[orick_ferr_login]` | Form de login standalone |

### 5.1. Materiais (e-books, planilhas, templates)

**Menu:** Materiais → Adicionar novo

Campos do metabox:
- **Tipo** (taxonomia): E-book, Planilha, Template, Checklist, Guia
- **Arquivo (Media Library)** OU **URL externa** (Google Drive, Notion)
- **Nº de páginas** (opcional, aparece no card)
- **Requer cadastro** — se marcado, clicar em "Baixar" leva pra `/baixar/{slug}/` (landing com gate); senão, vai direto pro arquivo

URLs automáticas:
- `/materiais/` — arquivo geral com filtros por tipo
- `/materiais/{slug}/` — página individual do material
- `/baixar/{slug}/` — landing de cadastro (só aparece se "Requer cadastro" marcado)

Log de downloads por lead fica em `wp_orick_lead_downloads`.

### 5.2. Vídeos

**Menu:** Vídeos → Adicionar novo

Campos do metabox:
- **YouTube ID** (o código depois de `v=`, ex: `dQw4w9WgXcQ`)
- **Duração** (texto livre, ex: `12:34`)

URLs: `/videos/` (archive), `/videos/{slug}/` (single com embed).

Se não definir imagem destacada, o plugin usa automaticamente a thumbnail do YouTube (`i.ytimg.com/vi/{ID}/hqdefault.jpg`).

### 5.3. Podcast

**Menu:** Podcast → Adicionar novo

Campos:
- **Número do episódio** (ex: 12)
- **Duração** (ex: `48:12`)
- **Convidado** (texto livre)
- **Link Spotify** (URL completa do episódio)
- **Link Apple Podcasts**
- **Link YouTube**

URLs: `/podcast/` (archive), `/podcast/{slug}/` (single com player Spotify embutido se o link for do Spotify).

### 5.4. Eventos

**Menu:** Eventos → Adicionar novo

Campos:
- **Data** (YYYY-MM-DD) e **hora início/fim**
- **Formato**: presencial / online / híbrido
- **Local** + **cidade**
- **Gratuito** (checkbox) OU **Preço** (texto livre)
- **Link de inscrição** (URL externa — Sympla, Eventbrite, etc)
- **Status das inscrições**: Em breve / Abertas / Últimas vagas / Encerradas / Finalizado

URLs:
- `/eventos/` — archive divide automaticamente em **Próximos** (data ≥ hoje) e **Passados**
- `/eventos/{slug}/` — single com card de data/formato/preço + CTA inteligente (só mostra "Quero me inscrever" se status for Abertas/Últimas)

### 5.5. Colunistas

**Menu:** Usuários → Editar usuário (não é CPT — são usuários WP reais com campos extras)

Campos adicionados no perfil:
- **Aparecer como colunista** (checkbox) — sem isso, não aparece na página `/colunistas/`
- **Cargo/Especialidade** (ex: "Planejadora CFP®")
- **Periodicidade**: semanal / quinzenal / mensal / esporádica
- **Tag única** (slug de uma tag normal do WP, ex: `colunabruna`) — usada pra filtrar artigos só desse autor
- **LinkedIn / Instagram / Twitter / Site pessoal**

Role customizada **"Colunista"** criada automaticamente na ativação (pode publicar/editar próprios posts, upload de arquivos).

URL: `/colunistas/` (archive listando todos com `Aparecer como colunista = sim`).

### 6. Simuladores embutidos

Em `plugin/orick-ferramentas/simulators/`:

- `goal-based.php` — calcula aporte mensal pra atingir objetivo financeiro
- `planejamento-comercial.php` — projeta receita com base em AuM e taxa média

Pra adicionar novos simuladores:

1. Crie `simulators/meu-novo.php`
2. Adicione a opção no select do metabox em `includes/cpt.php` (linha ~82)
3. Use a paleta CSS do plugin (`var(--ofr-fg)`, `var(--ofr-accent)`, etc)

## Segurança

- Senhas: `wp_hash_password()` (bcrypt-like, compatível com `wp_check_password`)
- CPF: validação com dígito verificador real, rejeita sequências iguais
- Cookie de sessão: HMAC-SHA256 com `auth_secret` próprio (64 chars, gerado na ativação)
- Nonces: em todos os forms POST
- Webhook: assinatura HMAC-SHA256 enviada no header `X-Orick-Signature`
- Sem exposição em REST: CPT `show_in_rest = false`

## Estrutura

```
orick-ferramentas/
├── orick-ferramentas.php          Bootstrap
├── includes/
│   ├── install.php                Ativação: cria 3 tabelas + options
│   ├── cpt.php                    CPT Ferramenta + metabox
│   ├── cpt-material.php           CPT Material + metabox
│   ├── cpt-video.php              CPT Vídeo + metabox YouTube
│   ├── cpt-episodio.php           CPT Episódio + player Spotify
│   ├── cpt-evento.php             CPT Evento + metabox data/local/preço
│   ├── colunista.php              Campos extras de colunista no perfil WP
│   ├── material-download.php      Landing /baixar/ + gate de cadastro
│   ├── auth.php                   Validação CPF, senha, sessão
│   ├── state.php                  Salva config do simulador por lead
│   ├── forms.php                  Render + POST handler de cadastro/login
│   ├── webhook.php                Dispara POST HMAC, salva status
│   ├── admin-leads.php            Lista + filtros + CSV + retry
│   ├── admin-settings.php         URL webhook + teste
│   ├── templates.php              Roteamento (archive/single de todos CPTs)
│   └── shortcodes.php             [orick_ferr_grid/cadastro/login]
├── templates/
│   ├── archive-ferramenta.php     /ferramentas/ (listagem + forms)
│   ├── single-ferramenta.php      /ferramentas/slug/ (single + gate)
│   ├── archive-material.php       /materiais/
│   ├── single-material.php
│   ├── archive-video.php          /videos/
│   ├── single-video.php           (embed YouTube)
│   ├── archive-episodio.php       /podcast/
│   ├── single-episodio.php        (player Spotify)
│   ├── archive-evento.php         /eventos/ (Próximos + Passados)
│   ├── single-evento.php
│   └── archive-colunista.php      /colunistas/
├── simulators/
│   ├── goal-based.php
│   ├── planejamento-comercial.php
│   └── planejamento-if.php
└── assets/
    ├── css/ferramentas.css
    └── js/forms.js                Máscaras CPF/telefone/dinheiro
```

## Desinstalação

Desativar o plugin preserva leads e ferramentas. Pra apagar TUDO:

```sql
DROP TABLE wp_orick_leads;
DELETE FROM wp_options WHERE option_name LIKE 'orick_ferr_%';
```

E apague os posts de CPT `ferramenta` pelo admin antes de desativar.

## Suporte

Code ownership: O Rick Silva. Pra evoluções, contate o desenvolvedor responsável.

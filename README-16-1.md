# Desafio 16.1: O Tema Noite Assombrada

Documentação técnica, justificativa arquitetural, mapeamento de variáveis LESS e guia de evidências de sucesso do **Desafio 16.1** (Sprint 8 | Semana 16 — A Camada Visual) no tema [`Webjump/noite-assombrada`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada).

---

## 1. Critérios de Aceite Atendidos

| Critério de Aceite | Status | Detalhamento da Implementação |
|---|:---:|---|
| **O tema aparece no admin com preview e está aplicado na store view** | [x] Atendido | Tema registrado com [`registration.php`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada/registration.php) e [`theme.xml`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada/theme.xml) herdando de `Magento/luma`. Miniatura gerada em [`media/preview.jpg`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada/media/preview.jpg) visível no Admin em *Content > Design > Themes*. Aplicado na Store View principal (`design/theme/theme_id = 4`). |
| **A paleta e a tipografia mudaram em toda a loja, não só na home** | [x] Atendido | Redefinição global de variáveis em [`_theme.less`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada/web/css/source/_theme.less) e regras customizadas em [`_extend.less`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada/web/css/source/_extend.less). **Home:** Cabeçalho, menu e vitrine (`.webjump-samuel-home-block`) integrados ao fundo roxo escuro `#21103A` com títulos em Creepster e botões pílula. **Catálogo:** Cards de produto com fundo escuro contínuo no hover, eliminando barras brancas, e botões de ação secundária ampliados (42px) com ícones dourados nítidos. **PDP:** Página de produto com seletor interativo de Qty contendo botões funcionais de `+` e `-`. |
| **Nenhum arquivo em `vendor/` ou no tema Luma foi alterado** | [x] Atendido | Toda a implementação foi feita exclusivamente sob `app/design/frontend/Webjump/noite-assombrada/`. O script oficial [`check-vendor-changes.sh`](file:///home/samuel/Sites/magento/.agents/skills/magento-engineer/scripts/check-vendor-changes.sh) foi executado com validação estrita (`--working`) retornando `OK: no changes under vendor/.`. |
| **A fonte própria carrega pelo caminho do tema, e o corpo do texto continua legível** | [x] Atendido | Fonte decorativa `Creepster` carregada via `@font-face` utilizando o caminho relativo oficial `@{baseDir}fonts/Creepster/creepster-regular.woff2` (com status HTTP 200). Restrita aos títulos e chamadas (`h1` a `h6`, logo, títulos de bloco), enquanto o corpo do texto corrido permanece em `Open Sans` em tom lavanda suave (`#EDE7F6`), assegurando contraste WCAG e conforto de leitura. |
| **README explica quais variáveis da biblioteca foram sobrescritas e por quê** | [x] Atendido | Seção 3 traz a matriz completa com todas as variáveis da biblioteca UI do Magento sobrescritas em `_theme.less`, seus valores e a justificativa arquitetural de cada redefinição. A Seção 5 apresenta o guia exato de prints para a seção de evidências. |

---

## 2. Decisão Arquitetural e Direção de Arte

### 2.1. Inspiração Visual (`inspiracao-1.jpeg`)
O tema foi estruturado com base na identidade visual fornecida na referência gráfica:
* **Fundo Imersivo Noturno:** Roxo profundo e violeta escuro (`#1A0B2E` a `#120722`), simulando o céu noturno assombrado.
* **Containers e Cards:** Violeta fechado (`#21103A`), com bordas sutis iluminadas (`#3D1C68`) e sombras de elevação ao passar o mouse.
* **Pontos de Ação e Destaque:** Dourado místico (`#FFD369`) e Laranja Abóbora vibrante (`#FF6B1A`), aplicados nos preços, botões em formato pílula e chamadas.
* **Estados de Interação:** Verde bruxa (`#7CFF6B`) para hovers luminosos e feedback visual.

### 2.2. A Mecânica do Fallback e Extensibilidade Limpa
No Magento 2, o frontend opera por **camadas de herança**. Ao declarar `<parent>Magento/luma</parent>` em `theme.xml`:
1. Todos os templates (`.phtml`), layouts XML, JavaScripts e estilos do Luma são herdados sem duplicação de arquivos.
2. Apenas os arquivos estritamente necessários para modificar a camada visual e comportamental foram criados:
   * `_theme.less`: processado antes dos componentes da biblioteca UI do Magento, permitindo a substituição de valores de variáveis nativas (cores, tipografia, `@product-item__hover__background-color`) sem gerar seletores duplicados no CSS compilado.
   * `_extend.less`: processado após toda a biblioteca, utilizado para introduzir a regra `@font-face`, estilizações de refinamento dos componentes (cards arredondados, botões pílula, botões secundários ampliados, estilização da vitrine da home e do stepper de quantidade) e mixins responsivos `.media-width`.
   * `Magento_Catalog/templates/product/view/addtocart.phtml`: sobrescrita limpa do template nativo de adicionar ao carrinho para injetar os botões de controle de quantidade `+` e `-`, com script jQuery não obstrusivo e validações seguras.

---

## 3. Matriz de Variáveis Sobrescritas da Biblioteca Magento (`_theme.less`)

Em conformidade estrita com o Critério de Aceite 5, a tabela abaixo detalha todas as variáveis nativas da biblioteca UI do Magento/Luma redefinidas no tema, o valor aplicado e a fundamentação técnica:

| Variável da Biblioteca Magento | Valor Atribuído | Justificativa Arquitetural e Propósito Visual |
|---|---|---|
| `@primary__color` | `#FF6B1A` (Abóbora) | Define a cor primária global da loja, herdada por links, alertas, ícones de destaque e botões nativos. |
| `@page__background-color` | `#1A0B2E` (Roxo Noite) | Substitui o fundo branco nativo do Luma pelo roxo noturno imersivo de Halloween em toda a loja. |
| `@text__color` | `#EDE7F6` (Lavanda Claro) | Garante legibilidade nítida e contraste sobre o fundo escuro (WCAG AAA), evitando fadiga visual no texto corrido. |
| `@text__color__muted` | `#B8B8C4` (Cinza Neblina) | Utilizado para informações secundárias, SKUs, datas de comentários e migalhas de pão. |
| `@heading__font-family__base` | `'Creepster', cursive, sans-serif` | Injeta a tipografia temática assombrosa automaticamente em todos os títulos (`h1`, `h2`, `h3`, `h4`, `h5`, `h6`) e títulos de blocos. |
| `@heading__color__base` | `#FFD369` (Dourado) | Harmoniza os títulos com o estilo gráfico da imagem de referência, transmitindo a atmosfera festiva da campanha. |
| `@font-family__base` | `'Open Sans', 'Helvetica Neue', Arial, sans-serif` | Mantém o corpo do texto em fonte sem serifa limpa e legível, cumprindo a diretriz de conversão do e-commerce. |
| `@link__color` | `#FF7A00` (Laranja Vivo) | Estabelece links visíveis e convidativos ao clique sobre o fundo violeta. |
| `@link__hover__color` | `#7CFF6B` (Verde Bruxa) | Aplica um efeito luminoso de "ectoplasma" nos estados de foco e passagem do cursor. |
| `@border-color__base` | `#3D1C68` (Roxo Borda) | Substitui os tons acinzentados de borda do Luma por linhas escuras integradas ao fundo noturno. |
| `@button-primary__background` | `#FFD369` (Dourado) | Transforma o botão de ação principal (ex: Adicionar ao Carrinho) em um elemento dourado destacado, alinhado à referência. |
| `@button-primary__color` | `#120722` (Roxo Quase Preto) | Assegura contraste máximo de leitura (texto escuro sobre fundo dourado vibrante). |
| `@button-primary__hover__background` | `#FF6B1A` (Abóbora) | Transiciona o botão para a tonalidade abóbora ao passar o mouse, gerando feedback tátil. |
| `@button-primary__hover__color` | `#FFFFFF` (Branco) | Garante legibilidade do texto no estado de hover alaranjado. |
| `@button__background` | `#21103A` (Roxo Card) | Botões secundários e neutros integrados visualmente aos cards da loja. |
| `@button__color` | `#EDE7F6` (Lavanda Claro) | Texto claro de fácil leitura nos botões secundários. |
| `@header__background-color` | `#120722` (Roxo Escuro) | Define o topo da página com o tom mais fechado da noite, delimitando a área de navegação. |
| `@header-panel__background-color` | `#0E0518` (Profundo) | Barra superior de login e boas-vindas com tonalidade escura contrastante. |
| `@navigation__background` | `#21103A` (Violeta Card) | Barra de navegação horizontal com cor própria para destacar as categorias do catálogo. |
| `@submenu-desktop__background` | `#21103A` (Roxo Card) | Fundo do menu dropdown e submenus em roxo escuro, integrando à identidade noturna. |
| `@submenu-desktop__border-color` | `#3D1C68` (Roxo Borda) | Contorno dos submenus com a borda temática luminosa. |
| `@submenu-desktop-item__color` | `#EDE7F6` (Lavanda Claro) | Cor dos links dos submenus com alto contraste sobre o fundo roxo escuro (elimina textos escuros ilegíveis). |
| `@submenu-desktop-item__hover__background` | `#381b62` (Roxo Destaque) | Fundo do item do submenu ao passar o mouse, eliminando o retângulo cinza/branco nativo do Luma. |
| `@submenu-desktop-item__hover__color` | `#FFD369` (Dourado) | Texto do item do submenu iluminado em dourado ao passar o cursor ou ganhar foco via teclado. |
| `@footer__background-color` | `#120722` (Roxo Escuro) | Rodapé noturno fechado com separação visual nítida da área de conteúdo. |
| `@copyright__background-color` | `#0E0518` (Profundo) | Faixa inferior de direitos reservados. |
| `@price-color` | `#FFD369` (Dourado) | Destaca os valores monetários em dourado âmbar brilhante, idêntico à seção de preços da referência. |
| `@product-item__hover__background-color` | `#21103A` (Roxo Card) | Elimina a caixa branca nativa do Luma que aparecia no container inferior do card ao passar o mouse. |
| `@form-element-input__background` | `#1B0B2E` (Escuro) | Campos de formulário com fundo escuro, eliminando caixas brancas ofuscantes. |
| `@form-element-input__color` | `#EDE7F6` (Claro) | Texto digitado pelo cliente com alto contraste e nitidez. |
| `@form-element-input__border-color` | `#3D1C68` (Borda) | Contorno sutil que se ilumina com foco dourado via `_extend.less`. |

---

## 4. Estrutura de Arquivos Criados

```text
src/app/design/frontend/Webjump/noite-assombrada/
├── registration.php              # Registro oficial do tema no Magento 2
├── theme.xml                     # Metadados do tema, herança de Magento/luma e imagem de preview
├── etc/
│   └── view.xml                  # Configurações de proporção e renderização de imagens de catálogo
├── media/
│   └── preview.jpg               # Miniatura (800x600) extraída e composta a partir da referência visual
├── Magento_Catalog/
│   └── templates/
│       └── product/
│           └── view/
│               └── addtocart.phtml # Template customizado da PDP com botões funcionais de + e -
└── web/
    ├── fonts/
    │   └── Creepster/
    │       ├── creepster-regular.woff2  # Fonte temática moderna otimizada
    │       ├── creepster-regular.woff   # Fallback de compatibilidade
    │       └── creepster-regular.ttf    # Fonte base TrueType
    └── css/
        └── source/
            ├── _theme.less       # Sobrescrita das variáveis canônicas da biblioteca UI
            └── _extend.less      # @font-face, botões pílula, botões secundários, stepper PDP e vitrine Home
```

---

## 5. Evidências de Sucesso

Esta seção reúne os prints comprobatórios de cada critério de aceite do desafio **16.1 - O tema Noite Assombrada**.

---

### 5.1. Critério 1: Tema Cadastrado no Admin com Preview e Aplicado na Store View

#### Print 1.1 — No Painel Admin (Tema Registrado com Preview em Content > Design > Themes)
- **O que comprova:** Tema **Noite Assombrada** listado com sucesso, indicando o tema pai `Magento Luma`, caminho de arquivos `frontend/Webjump/noite-assombrada` e a miniatura de preview (castelo assombrado e lua cheia).

> <img width="1800" height="700" alt="image" src="https://github.com/user-attachments/assets/coloque-o-link-aqui" />

#### Print 1.2 — No Painel Admin (Tema Ativo na Store View em Content > Design > Configuration)
- **O que comprova:** Configuração da *Default Store View* com o campo **Applied Theme** selecionado como **Noite Assombrada** e status de configuração salvo.

> <img width="1800" height="700" alt="image" src="https://github.com/user-attachments/assets/coloque-o-link-aqui" />

---

### 5.2. Critério 2: Paleta e Tipografia Aplicadas Globalmente (Home, Catálogo e PDP)

#### Print 2.1 — No Frontend (Home Page: Paleta Noturna, Submenus e Vitrine)
- **O que comprova:** Página inicial com fundo noturno `#1A0B2E`, topo escuro `#120722`, submenus suspensos abertos com texto em lavanda `#EDE7F6` de alto contraste e hover dourado `#FFD369`, vitrine de produtos integrada ao layout e botões em formato pílula sem caixas brancas.

> <img width="1800" height="900" alt="image" src="https://github.com/user-attachments/assets/coloque-o-link-aqui" />

#### Print 2.2 — No Frontend (Catálogo: Cards Proporcionais e Botões Secundários Ampliados)
- **O que comprova:** Grade de produtos da categoria com cards proporcionais (aspect ratio 1:1, sem espaços vazios sob a imagem), botão principal pílula e botões secundários de Wishlist e Compare circulares (42px) com ícones visíveis e fundo escuro contínuo.

> <img width="1800" height="900" alt="image" src="https://github.com/user-attachments/assets/coloque-o-link-aqui" />

#### Print 2.3 — No Frontend (Página de Produto / PDP: Galeria Centralizada e Stepper de Quantidade)
- **O que comprova:** Página do produto com galeria de imagem centralizada e ampla (sem miniaturas sobrepostas ou duplicadas), título em `Creepster` dourado e o seletor de quantidade com botões interativos de `+` e `-`.

> <img width="1800" height="900" alt="image" src="https://github.com/user-attachments/assets/coloque-o-link-aqui" />

---

### 5.3. Critério 3: Regra de Ouro — Integridade do Core (`vendor/` e Luma Intocados)

#### Print 3.1 — No Terminal (Script de Validação do Core e Git Status)
- **Arquivo:** [`check-vendor-changes.sh`](file:///home/samuel/Sites/magento/.agents/skills/magento-engineer/scripts/check-vendor-changes.sh)
- **O que comprova:** Execução de `./.agents/skills/magento-engineer/scripts/check-vendor-changes.sh --working` exibindo `OK: no changes under vendor/.` e `git status` comprovando modificações restritas exclusivamente ao diretório do tema `app/design/frontend/Webjump/noite-assombrada/` e documentação.

> <img width="1800" height="600" alt="image" src="https://github.com/user-attachments/assets/coloque-o-link-aqui" />

---

### 5.4. Critério 4: Fonte Própria Carregando pelo Tema e Legibilidade Preservada

#### Print 4.1 — No Navegador / DevTools (Aba Network: Carregamento HTTP 200 da Fonte `Creepster`)
- **O que comprova:** Requisição HTTP com status `200 OK` para o arquivo de fonte `creepster-regular.woff2` originada a partir do caminho estático do tema (`pub/static/frontend/Webjump/noite-assombrada/...`).

> <img width="1800" height="600" alt="image" src="https://github.com/user-attachments/assets/coloque-o-link-aqui" />

#### Print 4.2 — No Frontend (Legibilidade do Texto Corrido e Hierarquia Tipográfica)
- **O que comprova:** Corpo do texto corrido (abas de detalhes/descrição) renderizado em `Open Sans` lavanda claro (`#EDE7F6`) sobre fundo escuro com alto conforto visual e contraste WCAG, evidenciando que a fonte decorativa `Creepster` ficou estritamente restrita a títulos.

> <img width="1800" height="600" alt="image" src="https://github.com/user-attachments/assets/coloque-o-link-aqui" />

---

### 5.5. Critério 5: Justificativa Técnica das Variáveis Sobrescritas

#### Print 5.1 — No Código / Documentação (Matriz de Variáveis no README e `_theme.less`)
- **Arquivos:** [`README-16-1.md`](file:///home/samuel/Sites/magento/README-16-1.md) e [`web/css/source/_theme.less`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada/web/css/source/_theme.less)
- **O que comprova:** Seção 3 do `README-16-1.md` apresentando a matriz completa das variáveis nativas da biblioteca UI do Magento/Luma redefinidas no tema, detalhando o valor aplicado e a justificativa arquitetural de cada alteração.

> <img width="1800" height="800" alt="image" src="https://github.com/user-attachments/assets/coloque-o-link-aqui" />

---

## 6. Comandos para Reprodução e Validação

```bash
# 1. Registrar o tema
bin/magento setup:upgrade

# 2. Ativar o tema na store view
bin/magento config:set design/theme/theme_id 4

# 3. Compilar o LESS e publicar os estáticos
bin/magento setup:static-content:deploy -f pt_BR en_US

# 4. Limpar o cache do Magento
bin/magento cache:flush

# 5. Validar integridade do core (Regra de Ouro)
./.agents/skills/magento-engineer/scripts/check-vendor-changes.sh --working
```

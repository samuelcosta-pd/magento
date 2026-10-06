# Desafio 17.1: Contagem Regressiva e Selo Assombrado (Tema Noite Assombrada)

Documentação técnica, justificativa arquitetural, fluxo de inicialização Knockout.js e guia cirúrgico de evidências de sucesso do **Desafio 17.1** (Sprint 8 | Semana 17 — Comportamento e Autonomia) no tema [`Webjump/noite-assombrada`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada) e módulo [`Webjump_ProductBadge`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductBadge).

---

## 1. Critérios de Aceite Atendidos

| Critério de Aceite | Status | Detalhamento da Implementação |
|---|:---:|---|
| **O contador atualiza sozinho, sem recarregar a página** | [x] Atendido | Componente Knockout.js (`uiComponent`) com `setInterval` atualizando observables (`days`, `hours`, `minutes`, `seconds`) a cada segundo (1000ms), refletindo a contagem regressiva em tempo real no DOM sem qualquer refresh de página. |
| **Depois da data final, ele mostra a mensagem de encerramento em vez de números negativos** | [x] Atendido | Lógica reativa com `ko.computed`: quando a diferença entre a data alvo (`2026-10-31 23:59:59`) e a data corrente for menor ou igual a zero, ativa `isExpired(true)`, interrompe o timer e renderiza mensagem amigável de encerramento sem exibir nenhum valor negativo. |
| **O componente foi inicializado por `x-magento-init` ou `data-mage-init`** | [x] Atendido | Inicialização declarativa padrão Magento 2 via tag `<script type="text/x-magento-init">` com `Magento_Ui/js/core/app`, instanciando o componente `Magento_Theme/js/countdown` e template `Magento_Theme/countdown`. |
| **O selo aparece nos produtos marcados, na listagem e no detalhe** | [x] Atendido | O atributo `product_badge` (criado por Data Patch na Sprint 7) é recuperado pelo `Badge` ViewModel. O selo visual assombrado é renderizado nos cards de listagem do catálogo (`list.phtml`) e na página de produto (`catalog_product_view.xml`). |
| **Produto sem o atributo preenchido não gera erro nem espaço vazio** | [x] Atendido | Cláusula de guarda rigorosa (`hasBadge($product)`): caso o produto não possua o atributo preenchido, o template aborta a renderização sem injetar tags HTML, containers ou espaçamentos em branco no layout, mantendo status HTTP 200. |
| **Os textos do contador e do selo passam pelo CSV de tradução** | [x] Atendido | Todos os textos do Knockout usam o binding `i18n` ou `$t(...)`, e as saídas PHP passam por `__($badgeLabel)`. Todos os termos e rótulos estão mapeados em [`i18n/pt_BR.csv`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada/i18n/pt_BR.csv). |

---

## 2. Decisões Arquiteturais e Padrões Magento 2

### 2.1. Componente Reativo Knockout.js (`Magento_Ui/js/core/app`)
Para a contagem regressiva, evitamos qualquer manipulação direta e imperativa do DOM via jQuery ou scripts inline legados. Adotamos o padrão canônico do Magento 2 baseado em **MVVM (Model-View-ViewModel)** com **Knockout.js** e **UI Components**:
- **View (Template):** [`Magento_Theme/web/template/countdown.html`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada/Magento_Theme/web/template/countdown.html) — utiliza bindings declarativos do Knockout (`text`, `visible`, `css`, `i18n`).
- **ViewModel (JS):** [`Magento_Theme/web/js/countdown.js`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada/Magento_Theme/web/js/countdown.js) — estende `uiComponent`, definindo `ko.observable` para as frações de tempo e um `ko.computed` para a mensagem formatada.
- **Bootstrapper:** Injeção declarativa segura no arquivo [`Magento_Theme/templates/html/countdown.phtml`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada/Magento_Theme/templates/html/countdown.phtml) via `x-magento-init`, permitindo múltiplos blocos com escopos isolados através de identificadores gerados dinamicamente via `uniqid()`.

### 2.2. Reutilização do Atributo EAV da Sprint 7 (`product_badge`)
Em conformidade estrita com o enunciado:
1. Reaproveitamos o atributo de produto `product_badge` registrado no módulo [`Webjump_ProductBadge`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductBadge).
2. Adicionamos a opção temática `"Assombrado"` via Data Patch ([`AddHauntedBadgeOption.php`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductBadge/Setup/Patch/Data/AddHauntedBadgeOption.php)) sem alterar a tabela de schema nem o core do Magento.
3. Garantimos que `used_in_product_listing = true`, permitindo que coleções de catálogo carreguem o valor do atributo sem realizar consultas adicionais (*N+1 problem*).

### 2.3. Injeção na Listagem (PLP) e na Página de Produto (PDP)
- **Na PDP:** O módulo `Webjump_ProductBadge` já injeta o bloco via layout XML antes do título. O template foi refinado para garantir escaping e internacionalização via `__()`.
- **Na PLP:** O tema sobrescreve de forma limpa [`Magento_Catalog/templates/product/list.phtml`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada/Magento_Catalog/templates/product/list.phtml), copiando o template integral do core e inserindo o selo com posicionamento absoluto destacado sobre a imagem do card do produto. O ViewModel `Webjump\ProductBadge\ViewModel\Badge` é injetado via layout XML (`catalog_category_view.xml` e `catalogsearch_result_index.xml`).

---

## 3. Matriz de Arquivos do Desafio

```text
src/
├── app/code/Webjump/ProductBadge/
│   ├── Setup/Patch/Data/
│   │   └── AddHauntedBadgeOption.php                 # Patch adicionando opção "Assombrado" ao atributo
│   └── view/frontend/templates/product/view/
│       └── badge.phtml                               # Template da PDP com escaping e __($badgeLabel)
└── app/design/frontend/Webjump/noite-assombrada/
    ├── i18n/
    │   └── pt_BR.csv                                 # Traduções temáticas do contador e do selo
    ├── Magento_Theme/
    │   ├── layout/
    │   │   └── cms_index_index.xml                   # Injeção do contador regressivo na Home
    │   ├── templates/html/
    │   │   └── countdown.phtml                       # Wrapper PHTML com x-magento-init
    │   └── web/
    │       ├── js/
    │       │   └── countdown.js                      # Componente Knockout com observable e computed
    │       └── template/
    │           └── countdown.html                    # Template Knockout reativo
    ├── Magento_Catalog/
    │   ├── layout/
    │   │   ├── catalog_category_view.xml             # Injeção do ViewModel Badge na listagem de categorias
    │   │   ├── catalogsearch_result_index.xml        # Injeção do ViewModel Badge na listagem de busca
    │   │   └── catalog_product_view.xml              # Injeção do bloco de contagem regressiva na PDP
    │   └── templates/product/
    │       └── list.phtml                            # Sobrescrita limpa do card de produto com selo
    └── web/css/source/
        └── _extend.less                              # Estilização LESS temática do contador e do selo
```

---

## 4. Dicionário de Tradução (`pt_BR.csv`)

| Texto Original (Chave) | Tradução Aplicada (Tema Noite Assombrada) |
|---|---|
| `"Halloween Campaign Ends In:"` | `"Ofertas da Noite Assombrada encerram em:"` |
| `"Halloween Campaign Expired!"` | `"Os feitiços acabaram! Campanha Noite Assombrada encerrada."` |
| `"Days"` | `"Dias"` |
| `"Hours"` | `"Horas"` |
| `"Minutes"` | `"Minutos"` |
| `"Seconds"` | `"Segundos"` |
| `"Selo do Produto"` | `"Selo Assombrado"` |
| `"Assombrado"` | `"Selo Assombrado 🎃"` |
| `"Sustentável"` | `"Sustentável Assombrado 🌿👻"` |

---

## 5. Evidências de Sucesso

Esta seção reúne os prints comprobatórios de cada critério de aceite do desafio **17.1 - Contagem regressiva e selo assombrado**.

---

### 5.1. Critério 1: O contador atualiza sozinho, sem recarregar a página

#### Print 1.1 — Na Loja / Storefront (Contador ao Vivo na Home e PDP)
- **Onde acessar:** Página Inicial (`/`) ou Página do Produto (`/camisa-basica-de-algod-o.html`).
- **O que comprova:** Bloco promocional da contagem regressiva renderizado com a estética do tema Noite Assombrada, exibindo os blocos de Dias, Horas, Minutos e Segundos mudando a cada segundo em tempo real sem recarregar a página (verificável pelo console ou vídeo).

> *[Adicionar Print 1.1 aqui: Storefront com o contador em funcionamento]*

#### Print 1.2 — No Código / IDE (Componente Knockout com Observables e Intervalo)
- **Arquivo:** [`src/app/design/frontend/Webjump/noite-assombrada/Magento_Theme/web/js/countdown.js`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada/Magento_Theme/web/js/countdown.js)
- **O que comprova:** Definição dos observables Knockout (`days = ko.observable()`, `seconds = ko.observable()`) e a chamada de `setInterval(..., 1000)` atualizando os dados reativamente.

> *[Adicionar Print 1.2 aqui: Trecho de código com observables e setInterval]*

---

### 5.2. Critério 2: Depois da data final, ele mostra a mensagem de encerramento em vez de números negativos

#### Print 2.1 — Na Loja / Storefront (Estado Expirado com Mensagem Amigável)
- **Onde acessar:** Storefront configurado com data alvo no passado (ex: `targetDate: '2026-10-01 00:00:00'`).
- **O que comprova:** O contador não exibe valores negativos (ex: `-1d -5h`). No lugar do relógio, exibe a mensagem temática de encerramento: *"Os feitiços acabaram! Campanha Noite Assombrada encerrada."*.

> *[Adicionar Print 2.1 aqui: Storefront exibindo mensagem de campanha encerrada]*

#### Print 2.2 — No Código / IDE (Lógica do `ko.computed` e Tratamento de Expiração)
- **Arquivo:** [`src/app/design/frontend/Webjump/noite-assombrada/Magento_Theme/web/js/countdown.js`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada/Magento_Theme/web/js/countdown.js)
- **O que comprova:** Implementação do `ko.computed` validando `diff <= 0`, configurando `isExpired(true)` e retornando a mensagem amigável traduzida via `$t(...)`, prevenindo valores negativos.

> *[Adicionar Print 2.2 aqui: Trecho de código com ko.computed e diff <= 0]*

---

### 5.3. Critério 3: O componente foi inicializado por `x-magento-init` ou `data-mage-init`

#### Print 3.1 — No Código / IDE (Inicialização Declarativa via `x-magento-init`)
- **Arquivo:** [`src/app/design/frontend/Webjump/noite-assombrada/Magento_Theme/templates/html/countdown.phtml`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada/Magento_Theme/templates/html/countdown.phtml)
- **O que comprova:** Tag `<script type="text/x-magento-init">` vinculando o container ao `Magento_Ui/js/core/app`, instanciando o componente `Magento_Theme/js/countdown` com template `Magento_Theme/countdown`.

> *[Adicionar Print 3.1 aqui: Trecho do template PHTML com x-magento-init]*

---

### 5.4. Critério 4: O selo aparece nos produtos marcados, na listagem e no detalhe

#### Print 4.1 — Na Loja / Storefront (Selo nos Cards da Listagem - PLP)
- **Onde acessar:** Catálogo de Produtos / Categoria (ex: `/gear.html` ou `/women.html`).
- **O que comprova:** Card do produto marcado com o atributo exibindo o selo temático de Halloween (*Selo Assombrado 🎃* / *Sustentável Assombrado*) sobreposto à imagem.

> *[Adicionar Print 4.1 aqui: PLP com o selo visual no card do produto]*

#### Print 4.2 — Na Loja / Storefront (Selo na Página de Detalhes - PDP)
- **Onde acessar:** Página do produto marcado (ex: `/camisa-basica-de-algod-o.html`).
- **O que comprova:** Página de detalhe exibindo o selo temático estilizado posicionado acima do título do produto.

> *[Adicionar Print 4.2 aqui: PDP com o selo destacado]*

#### Print 4.3 — No Painel Admin (Atributo "Selo do Produto" no Cadastro)
- **Onde acessar:** **Catalog > Products > Selecionar Produto Marcado** (campo *Selo do Produto*).
- **O que comprova:** Atributo EAV `product_badge` configurado no painel administrativo com o valor selecionado.

> *[Adicionar Print 4.3 aqui: Painel Admin com o atributo preenchido no produto]*

---

### 5.5. Critério 5: Produto sem o atributo preenchido não gera erro nem espaço vazio

#### Print 5.1 — Na Loja / Storefront (Produto Não Marcado sem Ruído Visual)
- **Onde acessar:** Página de um produto sem o atributo (ex: `/joust-duffle-bag.html`) ou card lado a lado na listagem.
- **O que comprova:** Produto carrega normalmente (HTTP 200) sem renderizar o selo, sem espaçamento em branco residual e sem quebra visual no alinhamento dos elementos.

> *[Adicionar Print 5.1 aqui: Storefront com produto sem o selo perfeitamente alinhado]*

#### Print 5.2 — No Código / IDE (Cláusula de Guarda `hasBadge()`)
- **Arquivos:** [`src/app/design/frontend/Webjump/noite-assombrada/Magento_Catalog/templates/product/list.phtml`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada/Magento_Catalog/templates/product/list.phtml) e [`src/app/code/Webjump/ProductBadge/view/frontend/templates/product/view/badge.phtml`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductBadge/view/frontend/templates/product/view/badge.phtml)
- **O que comprova:** Condicional `if ($badgeViewModel && $badgeViewModel->hasBadge($_product))` garantindo que nenhum elemento HTML ou classe wrapper seja emitido para produtos não marcados.

> *[Adicionar Print 5.2 aqui: Trecho de código com a condicional hasBadge()]*

---

### 5.6. Critério 6: Os textos do contador e do selo passam pelo CSV de tradução

#### Print 6.1 — Na Loja / Storefront (Textos Traduzidos com Vocabulário da Campanha)
- **O que comprova:** Textos do contador (*"Ofertas da Noite Assombrada encerram em:"*, *"Dias"*, *"Horas"*, *"Minutos"*, *"Segundos"*) e do selo (*"Selo Assombrado 🎃"*) refletindo as traduções ativas do tema.

> *[Adicionar Print 6.1 aqui: Storefront evidenciando os termos traduzidos do contador e do selo]*

#### Print 6.2 — No Código / IDE (Dicionário de Tradução `pt_BR.csv`)
- **Arquivo:** [`src/app/design/frontend/Webjump/noite-assombrada/i18n/pt_BR.csv`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada/i18n/pt_BR.csv)
- **O que comprova:** Entradas de tradução em UTF-8 com os termos do componente Knockout e do atributo do produto.

> *[Adicionar Print 6.2 aqui: Trecho do pt_BR.csv com as chaves do 17.1]*

---

## 6. Comandos para Validação e Teste Local

```bash
# 1. Aplicar Data Patch para garantir a opção temática no atributo (se aplicável)
bin/magento setup:upgrade

# 2. Compilar assets estáticos e dicionário de tradução pt_BR
bin/magento setup:static-content:deploy -f pt_BR en_US

# 3. Limpar os caches de Layout, Blocos e Tradução
bin/magento cache:flush

# 4. Validar integridade do core (Regra de Ouro)
./.agents/skills/magento-engineer/scripts/check-vendor-changes.sh --working

# 5. Validar conformidade PHPCS
bin/cli vendor/bin/phpcs --standard=Magento2 app/code/Webjump/ProductBadge
```

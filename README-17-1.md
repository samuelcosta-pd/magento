# Desafio 17.1: Contagem Regressiva e Selo Assombrado

Documentação técnica, justificativa arquitetural, mapeamento de componentes Knockout JS, integração de layouts, templates e guia de evidências de sucesso do **Desafio 17.1** (Sprint 8 | Semana 17 — Comportamento e Autonomia) no tema [`Webjump/noite-assombrada`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada) e módulo [`Webjump_ProductBadge`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductBadge).

---

## 1. Critérios de Aceite Atendidos

| Critério de Aceite | Status | Detalhamento da Implementação |
|---|:---:|---|
| **1. O contador atualiza sozinho, sem recarregar a página** | [x] Atendido | Componente Knockout JS (`js/countdown`) com `ko.observable` reativo para timestamp atual, atualizado via `setInterval` a cada 1000ms. A UI reage de forma assíncrona sem disparar requisições HTTP adicionais e sem recarregar a página. |
| **2. Depois da data final, ele mostra a mensagem de encerramento em vez de números negativos** | [x] Atendido | Cálculo em `ko.computed` que avalia a diferença temporal (`target - now`). Se a data for atingida ou ultrapassada (`distance <= 0`), ativa `isExpired(true)` e exibe uma mensagem amigável de encerramento com estilo temático, bloqueando valores negativos ou `NaN`. |
| **3. O componente foi inicializado por `x-magento-init` ou `data-mage-init`** | [x] Atendido | Inicialização declarativa via `<script type="text/x-magento-init">` vinculada ao container DOM através do inicializador nativo `Magento_Ui/js/core/app`, respeitando os padrões de Full Page Cache (FPC) do Magento 2. |
| **4. O selo aparece nos produtos marcados, na listagem e no detalhe** | [x] Atendido | O selo reaproveita o atributo de catálogo `product_badge` criado na Sprint 7. No detalhe (PDP), renderizado via bloco `product.info.badge`. Na listagem de produtos (PLP), integrado ao card de produto via sobrescrita de `Magento_Catalog::product/list.phtml` no tema, consumindo o `Webjump\ProductBadge\ViewModel\Badge`. |
| **5. Produto sem o atributo preenchido não gera erro nem espaço vazio** | [x] Atendido | Validação estrita via ViewModel (`hasBadge($_product)`). Produtos sem valor selecionado no atributo `product_badge` não geram tags no DOM, mantendo a grade limpa e sem elementos órfãos ou espaçamentos quebrados. |
| **6. Os textos do contador e do selo passam pelo CSV de tradução** | [x] Atendido | Todas as mensagens e rótulos utilizam `$t()` no JavaScript/Knockout e `__()` nos templates `.phtml`. Dicionário de tradução configurado em `i18n/pt_BR.csv` no tema. |

---

## 2. Decisões Arquiteturais e Abordagem Técnica

### 2.1. Componente Knockout JS (`web/js/countdown.js`)
* **Extensão de `uiComponent`**: Segue o padrão arquitetural oficial do Magento 2 UI Components.
* **Reatividade Declarativa**:
  - `this.now = ko.observable(new Date().getTime())`: armazena o instante presente.
  - `this.isExpired = ko.observable(false)`: flag booleana reativa que sinaliza o encerramento da contagem.
  - `this.formattedTime = ko.computed(...)`: computa e formata dias, horas, minutos e segundos restantes.
  - `this.days`, `this.hours`, `this.minutes`, `this.seconds`: computed observables individuais para renderização dos cards numéricos estilizados.
* **Segurança e FPC (Full Page Cache)**: A data limite (`2026-10-31T23:59:59`) é passada via configuração JSON e processada no lado do cliente. Isso garante que a página seja cacheada pelo Varnish / FPC nativo sem congelar a contagem regressiva em um estado estático desatualizado.

### 2.2. Inicialização via `x-magento-init`
Em vez de scripts inline imperativos (`<script>new Countdown()...</script>`), utiliza-se o padrão nativo `text/x-magento-init`:
```html
<script type="text/x-magento-init">
{
    "#halloween-countdown": {
        "Magento_Ui/js/core/app": {
            "components": {
                "halloweenCountdown": {
                    "component": "js/countdown",
                    "template": "Webjump_Theme/countdown",
                    "targetDate": "2026-10-31T23:59:59"
                }
            }
        }
    }
}
</script>
```
Isso desacopla a lógica do HTML, permite inicialização assíncrona via RequireJS e respeita políticas rígidas de CSP (Content Security Policy).

### 2.3. Integração do Selo Assombrado no Catálogo (PLP e PDP)
* **Reaproveitamento de Atributo**: O atributo `product_badge` (EAV `catalog_product`) configurado na Sprint 7 com `used_in_product_listing = true` e escopo `STORE` já está presente nas coleções carregadas na listagem e na PDP, eliminando consultas N+1 ao banco.
* **ViewModel `Webjump\ProductBadge\ViewModel\Badge`**: Utilizado como ponte de dados desacoplada (`ArgumentInterface`), fornecendo os métodos:
  - `hasBadge($product)`: verificação booleana da existência do valor.
  - `getBadgeLabel($product)`: retorno do rótulo amigável (ex: *"Assombrado"*).
  - `getBadgeCssClass($product)`: sanitização para classe CSS (ex: `product-badge--assombrado`).
* **Resiliência e Ausência de Espaço Vazio**: Caso `hasBadge()` retorne `false`, nenhuma estrutura HTML é impressa, garantindo que o grid permaneça uniforme.

---

## 3. Estrutura de Arquivos da Implementação

```text
src/app/design/frontend/Webjump/noite-assombrada/
├── Magento_Catalog/
│   ├── layout/
│   │   ├── catalog_category_view.xml     # Injeção de ViewModel para a listagem de produtos
│   │   └── catalog_product_view.xml      # Inclusão do contador na página de produto
│   └── templates/
│       └── product/
│           └── list.phtml                # Sobrescrita com injeção do selo assombrado nos cards
├── Magento_Theme/
│   ├── layout/
│   │   └── cms_index_index.xml           # Inclusão do contador regressivo na Home
│   └── templates/
│       └── html/
│           └── countdown.phtml           # Bloco PHTML com inicialização x-magento-init
├── i18n/
│   └── pt_BR.csv                         # Dicionário de tradução com os termos do contador e selo
└── web/
    ├── css/
    │   └── source/
    │       └── _extend.less              # Estilos temáticos de Halloween para o contador e selo
    ├── js/
    │   └── countdown.js                  # Componente Knockout JS (observable + computed)
    └── template/
        └── countdown.html                # Template Knockout renderizado na UI
```

---

## 4. Evidências de Sucesso

Esta seção reúne os prints comprobatórios de cada critério de aceite do desafio **17.1 - Contagem regressiva e selo assombrado**.

---

### 4.1. Critério 1: Contador Atualizando Sozinho sem Recarregar a Página

#### Print 1.1 — No Frontend (Home Page: Contador Regressivo em Tempo Real)
- **Onde acessar:** Home Page da loja (`/`)
- **O que comprova:** Bloco do contador regressivo renderizado na página inicial com estilo temático de Halloween, exibindo os dias, horas, minutos e segundos decrescendo dinamicamente a cada segundo sem necessidade de refresh do navegador.

> <img width="1800" height="900" alt="Contador na Home Page em tempo real" src="" />

#### Print 1.2 — No Frontend (PDP: Contador Regressivo na Página de Produto)
- **Onde acessar:** Página de Detalhe de Produto (ex: `/camisa-basica-de-algodao.html` ou `/strive-shoulder-pack.html`)
- **O que comprova:** Contador regressivo em funcionamento dentro da página de produto, posicionado na coluna de informações de compra, decrementando o tempo de forma assíncrona.

> <img width="1800" height="900" alt="Contador na Página de Produto" src="" />

#### Print 1.3 — No Código / IDE (Componente Knockout `countdown.js`)
- **Arquivo:** [`web/js/countdown.js`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada/web/js/countdown.js)
- **O que comprova:** Declaração de `ko.observable(new Date().getTime())` para o tempo presente e `setInterval` disparando a atualização do observable a cada 1000 milissegundos.

> <img width="1800" height="900" alt="Código Knockout com observable e setInterval" src="" />

---

### 4.2. Critério 2: Mensagem de Encerramento Amigável após a Data Final

#### Print 2.1 — No Frontend (Simulação de Data Expirada na UI)
- **Onde acessar:** Home Page ou PDP com data alvo retroativa configurada (ex: `2026-10-01T00:00:00`)
- **O que comprova:** O componente exibe a mensagem temática de encerramento da campanha (*"A Noite Assombrada chegou ao fim!"* / *"Campanha de Halloween Encerrada!"*) de forma elegante, sem exibir valores negativos como `-1 dias` ou `NaN`.

> <img width="1800" height="900" alt="Mensagem de encerramento amigável sem números negativos" src="" />

#### Print 2.2 — No Código / IDE (Lógica de Bloqueio de Negativos em `ko.computed`)
- **Arquivo:** [`web/js/countdown.js`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada/web/js/countdown.js)
- **O que comprova:** Condicional `if (distance <= 0)` no `ko.computed`, alternando `isExpired(true)` e retornando o texto traduzido de encerramento.

> <img width="1800" height="900" alt="Lógica de bloqueio de números negativos no código" src="" />

---

### 4.3. Critério 3: Inicialização Declarativa via `x-magento-init`

#### Print 3.1 — No Código / IDE (Template PHTML com `x-magento-init`)
- **Arquivo:** [`Magento_Theme/templates/html/countdown.phtml`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada/Magento_Theme/templates/html/countdown.phtml)
- **O que comprova:** Bloco HTML contendo a tag `<script type="text/x-magento-init">` vinculando o container DOM ao componente `Magento_Ui/js/core/app` e carregando `js/countdown`.

> <img width="1800" height="900" alt="Declaração de x-magento-init no template PHTML" src="" />

#### Print 3.2 — No Navegador / DevTools (Estrutura DOM e Binding do Knockout)
- **Onde acessar:** Inspecionar Elemento (F12) no bloco do contador
- **O que comprova:** Atributos `data-bind="scope: 'halloweenCountdown'"` renderizados no DOM e o script `text/x-magento-init` processado pelo RequireJS sem disparar erros no console.

> <img width="1800" height="900" alt="Inspeção do DOM com x-magento-init e scope Knockout" src="" />

---

### 4.4. Critério 4: Selo Assombrado nos Produtos Marcados (Listagem e Detalhe)

#### Print 4.1 — No Painel Admin (Produto com Atributo `product_badge` = "Assombrado")
- **Onde acessar:** **Catalog > Products** > Editar produto (ex: `Camisa Básica de Algodão` ou `Strive Shoulder Pack`)
- **O que comprova:** Campo **Selo do Produto** (`product_badge`) preenchido com a opção **Assombrado** salva com sucesso.

> <img width="1800" height="900" alt="Admin do produto com atributo product_badge marcado como Assombrado" src="" />

#### Print 4.2 — No Frontend (Catálogo / Listagem PLP: Selo Visível nos Cards Marcados)
- **Onde acessar:** Página de Categoria (ex: `/men/tops-men/tees-men.html` ou `/gear/bags.html`)
- **O que comprova:** Selo temático **Assombrado** visível e destacado no card dos produtos marcados, harmonizado com o layout noturno de Halloween.

> <img width="1800" height="900" alt="Selo visível nos produtos marcados na listagem" src="" />

#### Print 4.3 — No Frontend (Página de Detalhe PDP: Selo em Destaque)
- **Onde acessar:** Página do produto marcado (ex: `/camisa-basica-de-algodao.html`)
- **O que comprova:** Selo temático **Assombrado** posicionado junto ao título do produto na PDP.

> <img width="1800" height="900" alt="Selo visível na página de detalhes do produto" src="" />

#### Print 4.4 — No Código / IDE (Integração na Listagem `product/list.phtml` e PDP)
- **Arquivos:** [`Magento_Catalog/templates/product/list.phtml`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada/Magento_Catalog/templates/product/list.phtml) e [`catalog_product_view.xml`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductBadge/view/frontend/layout/catalog_product_view.xml)
- **O que comprova:** Chamada de `$badgeViewModel->hasBadge($_product)` e renderização do badge no loop de produtos da listagem.

> <img width="1800" height="900" alt="Código da integração do selo no template list.phtml" src="" />

---

### 4.5. Critério 5: Produto sem Atributo Não Gera Erro Nem Espaço Vazio

#### Print 5.1 — No Frontend (Comparativo Lado a Lado no Grid de Catálogo)
- **Onde acessar:** Página de Categoria com múltiplos produtos
- **O que comprova:** Produtos que não possuem o atributo marcado são renderizados perfeitamente alinhados, sem espaços em branco deslocados, sem placeholders quebrados e sem elementos vazios no DOM.

> <img width="1800" height="900" alt="Produtos com e sem selo alinhados perfeitamente no catálogo" src="" />

#### Print 5.2 — No Navegador / DevTools (Inspeção do DOM de Produto sem Selo)
- **Onde acessar:** Inspecionar Elemento (F12) sobre o card de um produto sem o atributo
- **O que comprova:** Ausência de tags `<div class="product-badge-wrapper">` vazias ou nós órfãos no container do card.

> <img width="1800" height="900" alt="Inspeção DOM comprovando ausência de espaço vazio ou tag residual" src="" />

#### Print 5.3 — No Código / IDE (Guarda de Proteção no Template e ViewModel)
- **Arquivos:** [`templates/product/list.phtml`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada/Magento_Catalog/templates/product/list.phtml) e [`ViewModel/Badge.php`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductBadge/ViewModel/Badge.php)
- **O que comprova:** Retorno precoce (`return` / `if ($badgeViewModel->hasBadge($_product))`) que previne a renderização de qualquer markup quando o atributo não estiver definido.

> <img width="1800" height="900" alt="Código com checagem estrita impedindo marcação vazia" src="" />

---

### 4.6. Critério 6: Textos do Contador e do Selo Traduzidos via CSV

#### Print 6.1 — No Frontend (Textos do Contador e Selo em Português)
- **Onde acessar:** Home Page e Catálogo
- **O que comprova:** Rótulos como *"Dias"*, *"Horas"*, *"Minutos"*, *"Segundos"*, *"Ofertas de Halloween terminam em:"* e *"Assombrado"* exibidos em português correto na interface.

> <img width="1800" height="900" alt="Interface renderizando textos traduzidos em pt_BR" src="" />

#### Print 6.2 — No Código / IDE (Dicionário de Tradução `i18n/pt_BR.csv`)
- **Arquivo:** [`i18n/pt_BR.csv`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada/i18n/pt_BR.csv)
- **O que comprova:** Mapeamento de todas as chaves do contador e do selo no arquivo CSV do tema.

> <img width="1800" height="900" alt="Chaves de tradução cadastradas no CSV pt_BR" src="" />

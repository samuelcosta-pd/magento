# Documentação de Entrega: Redesign Temático de Halloween (Noite Assombrada)
## Sprint 8 — Semanas 16 e 17 (Programa de Estágio Webjump)
**Branch de Entrega:** `exercicio/redesing-tema`  
**Tema Desenvolvido:** `Webjump/noite-assombrada` (herança de `Magento/luma`)  
**Modelo de Referência Visual:** Horror-Shop.com (`Horror-Shop.com _ Halloween Shop für Kostüme, Deko & Horror Masken.html`)

---

## 1. Visão Geral da Solução

O objetivo desta entrega é transformar o tema da loja Magento 2 Open Source em uma experiência imersiva de campanha de Halloween, reproduzindo com fidelidade estética, estrutural e comportamental o modelo da **Horror-Shop.com**.

Foram contemplados todos os requisitos das Semanas 16 e 17, respeitando integralmente as boas práticas da arquitetura Magento 2 e os padrões do `@magento-engineer`:
- **Sem alterações em arquivos do core (`vendor/`) ou do tema base `Magento/luma`**.
- **Sem alterações diretas em arquivos gerados em `pub/static`**.
- Herança declarada no `theme.xml` a partir de `Magento/luma`.
- Priorização de mesclagem de layout XML e extensão via LESS sobre cópia de templates `.phtml`.
- Escape rigoroso de dados (`$block->escapeHtml()`, `$block->escapeHtmlAttr()`, etc.) e suporte à tradução `__('Texto')`.
- Tratamento de dados privados via Knockout.js e `customer-data`, sem vazamento no Full Page Cache (FPC).
- Uso obrigatório de `mixins` no RequireJS preservando `this._super()`, sem utilizar `map` destrutivo.

---

## 2. Variáveis LESS da UI Library Sobrescritas e Justificativa

Todas as sobrescritas globais de variáveis da UI Library do Magento foram concentradas em `web/css/source/_theme.less`. As customizações adicionais e regras de layouts temáticos foram estruturadas em `web/css/source/_extend.less`.

| Variável LESS | Valor Aplicado | Justificativa Arquitetural e Visual |
| :--- | :--- | :--- |
| `@color-blood-red` | `#aa0707` | Cor primária oficial da identidade Horror-Shop, substituindo o azul corporativo padrão do Luma (`#006bb4`) para os botões de ação principal (CTA), preços e títulos com temática de sangue. |
| `@color-blood-dark` | `#880000` | Variação escurecida do vermelho para estados `:active`, bordas em baixo-relevo e sombras de botões primários. |
| `@color-pumpkin-orange` | `#ff6500` | Cor de destaque de alta visibilidade da campanha (Halloween Pumpkin), utilizada para estados `:hover` de links, botões, ícones, divisores e badges de cupons. |
| `@color-haunted-gold` | `#d69849` | Dourado envelhecido utilizado em gradientes sutis dos selos de produto assombrado. |
| `@color-charcoal-dark` | `#262728` | Tom carvão profundo empregado no cabeçalho principal, botões secundários, submenus do mega menu e barra de copyright. |
| `@color-header-midnight`| `#1a1a1a` | Tom de preto da meia-noite utilizado na Announcement Bar e no painel superior (`header.panel`). |
| `@color-parchment-bg` | `#eae3db` | Cor de pergaminho vintage do Horror-Shop que serve de fundo uniforme sob o pattern gráfico texturizado (`hs_bg_pattern_new.jpg`). |
| `@color-parchment-card` | `#ffffff` | Fundo branco limpo para o miolo dos cards de produtos, garantindo contraste impecável para as fotografias dos itens. |
| `@color-parchment-footer`| `#ede9e2` | Fundo vintage do rodapé, conferindo suavidade ao encerramento da página. |
| `@color-border-vintage` | `#d3d3d3` | Cor para a moldura fina e contornos chanfrados vintage dos cards de catálogo. |
| `@color-text-primary` | `#121212` | Cor de texto primária com contraste de alto padrão (WCAG AAA) sobre fundo claro pergaminho. |
| `@color-text-muted` | `#555555` | Tom cinza escuro para SKUs, datas e informações secundárias. |
| `@font-family__base` | `'Oswald', 'Helvetica Neue', Helvetica, Arial, sans-serif` | Tipografia display robusta da Horror-Shop, carregada localmente no tema via `@font-face` com interpolação `@{baseDir}fonts/Oswald/`. |
| `@heading__font-family__base` | `'Oswald', sans-serif` | Unificação da tipografia dos títulos e headings (h1 a h6) em caixa alta (uppercase). |
| `@heading__font-weight__base` | `700` | Peso bold característico da tipografia de manchetes da Horror-Shop. |
| `@h1__font-color`, `@h2__font-color` | `@color-blood-red` | Títulos principais em vermelho sangue da campanha. |
| `@button-primary__background` | `@color-blood-red` | Padronização dos botões primários da loja (como "Adicionar ao Caldeirão" e "Pacto Final"). |
| `@button-primary__hover__background` | `@color-pumpkin-orange` | Feedback visual interativo moderno com transição suave ao passar o cursor. |
| `@button__background` | `@color-charcoal-dark` | Botões secundários no tom carvão para manter o estilo sombrio do Horror-Shop. |
| `@header__background-color` | `@color-charcoal-dark` | Fundo do cabeçalho que suporta a imagem de sangue pingando (`hs_newblut.png`). |
| `@navigation__background` | `@color-charcoal-dark` | Fundo do menu de navegação horizontal. |
| `@navigation-level0-item__color` | `#ffffff` | Cor dos links das categorias principais no menu horizontal. |
| `@navigation-level0-item__hover__color` | `@color-pumpkin-orange` | Destaque em laranja nas categorias ao passar o cursor. |
| `@price-color` | `@color-blood-red` | Preços destacados em vermelho sangue. |
| `@form-element-input__border-color` | `@color-border-vintage` | Campos de formulário com contorno arredondado e bordas suaves. |

---

## 3. Justificativa Arquitetural: Uso de RequireJS Mixin em vez de Map no Minicart

Na implementação do desafio **17.2** (mensagem dinâmica no carrinho lateral com reatividade de itens), a extensão do componente JavaScript `Magento_Checkout/js/view/minicart` foi realizada estritamente através do mecanismo de **Mixins** no `requirejs-config.js`:

```javascript
var config = {
    deps: [
        'Magento_Theme/js/haunted-mode',
        'Magento_Theme/js/bindings/spooky-shake'
    ],
    config: {
        mixins: {
            'Magento_Checkout/js/view/minicart': {
                'Magento_Checkout/js/view/minicart-mixin': true
            }
        }
    }
};
```

### Por que o uso de `map` é considerado uma má prática arquitetural?
1. **Quebra de Atualizações do Core (Fragilidade):**  
   A diretiva `map` faz uma substituição cega e total do arquivo solicitado. Caso a Adobe lance uma atualização de segurança, suporte a CSP, novas rotinas de pagamento ou melhorias de performance no `Magento_Checkout/js/view/minicart`, a loja nunca receberá essas melhorias se o arquivo foi mapeado para um clone estático.
2. **Incompatibilidade com Módulos de Terceiros e Plugins:**  
   Se um gateway de pagamento ou módulo de frete também precisar estender o minicart, o uso de `map` impede que outros módulos colaborem na mesma cadeia de execução, gerando conflitos de precedência difíceis de diagnosticar.
3. **Preservação de Herança e Comportamento Original com `mixins`:**  
   O mixin utiliza o padrão de projeto *Decorator*. Ele recebe o componente alvo (`target`) e retorna `target.extend({ ... })`, invocando `this._super()`:
   ```javascript
   initialize: function () {
       var self = this;
       var res = this._super(); // Preserva 100% da inicialização nativa do Magento
       
       this.halloweenMessage = ko.computed(function () {
           var count = self.getCartParam('summary_count');
           // Reatividade sem interferir no fluxo padrão
       });
       return res;
   }
   ```
   Dessa forma, qualquer método ou funcionalidade presente no core continua intacto, garantindo total estabilidade do carrinho.

---

## 4. Ciclo de Vida do Dado: Mensagem Assombrada no Checkout (Desafio 17.3)

O desafio 17.3 implementou um campo de texto adicional na etapa de entrega do checkout (`spooky_order_comment`). O percurso completo percorrido pelo dado, desde a interação na interface até o banco de dados e a tela do painel administrativo, ocorre nas seguintes etapas:

```
[UI do Checkout: Textarea]
        │
        ▼ (LayoutProcessorPlugin injeta o campo no jsLayout)
[Formulário de Entrega (Shipping Step)]
        │
        ▼ (Cliente digita e avança para a etapa de pagamento)
[RequireJS Mixin: payload-extender-mixin.js]
        │
        ▼ (Anexa em payload.addressInformation.extension_attributes.spooky_order_comment)
[Requisição REST: /V1/carts/mine/shipping-information]
        │
        ▼
[Backend Plugin: ShippingInformationManagementPlugin::beforeSaveAddressInformation]
        │
        ▼ (Sanitização: máx 250 chars)
[Tabela quote (Coluna spooky_order_comment)]
        │
        ▼ (Cliente clica em 'Fazer Pedido' / 'Pacto Final')
[Observer: CopyCommentFromQuoteToOrder (evento sales_model_service_quote_submit_before)]
        │
        ▼
[Tabela sales_order (Coluna spooky_order_comment)]
        │
        ▼ (Lojista acessa Admin: Vendas > Pedidos > Ver Pedido)
[Bloco Admin: Webjump\CheckoutComment\Block\Adminhtml\Order\View\Comment]
        │
        ▼
[Template Admin: comment.phtml (Renderização segura com escape)]
```

### Detalhamento das Camadas:
1. **Injeção de UI (Plugin Backend):**  
   O plugin `Webjump\CheckoutComment\Plugin\Checkout\LayoutProcessorPlugin` intercepta `afterProcess()` e injeta o elemento UI textarea em `checkout.steps.shipping-step.shippingAddress.children.spooky-order-comment-fieldset.children.spooky_order_comment` sob a área `shippingAdditional`, configurando validação de até 250 caracteres e texto traduzido.
2. **Coleta no Frontend (Mixin JS):**  
   O mixin `payload-extender-mixin.js` envolve o `Magento_Checkout/js/model/shipping-save-processor/payload-extender`. Ao salvar as informações de frete, ele recupera o valor do campo e o anexa a `payload.addressInformation.extension_attributes.spooky_order_comment`.
3. **Persistência na Quote (Plugin Backend):**  
   O plugin `ShippingInformationManagementPlugin::beforeSaveAddressInformation` intercepta a chamada de salvamento de frete, lê o atributo de extensão, aplica sanitização (`mb_substr(trim($comment), 0, 250)`) e grava o comentário diretamente no registro ativo da `quote`.
4. **Conversão de Cotação para Pedido (Observer):**  
   Durante o fechamento do pedido, o observer `CopyCommentFromQuoteToOrder` escuta o evento `sales_model_service_quote_submit_before` e copia o valor da coluna `spooky_order_comment` da cotação para o pedido (`sales_order`). As colunas foram adicionadas declarativamente via `etc/db_schema.xml`.
5. **Apresentação no Admin:**  
   No painel administrativo (Vendas > Pedidos > Detalhes), o layout `sales_order_view.xml` insere o bloco `Webjump\CheckoutComment\Block\Adminhtml\Order\View\Comment` no container `order_additional_info`. O template `comment.phtml` exibe a mensagem assombrada em um quadro temático estilizado com escape de HTML rigoroso (`$escaper->escapeHtml()`).

---

## 5. Inventário de Arquivos Copiados do Core / Luma (Controle de Débito Técnico)

Em cumprimento às regras arquiteturais de governança de código, a lista a seguir detalha todos os arquivos originais do core Magento ou do tema Luma que foram sobrescritos no tema `noite-assombrada`, acompanhados de suas respectivas justificativas técnicas:

| Arquivo no Tema | Arquivo Original no Core | Justificativa Técnica / Débito Técnico |
| :--- | :--- | :--- |
| `Magento_Theme/templates/html/header/logo.phtml` | `vendor/magento/module-theme/view/frontend/templates/html/header/logo.phtml` | **Exibição de Logotipo Temático e Emblema de Campanha:** Sobrescrita integral para renderizar o logotipo de alta fidelidade da Horror-Shop (`hs_newlogo.png`) e incluir a tag de campanha "Edição Noite Assombrada". Preserva integralmente a resolução dinâmica de tamanho via `LogoSizeResolverInterface`, suporte a atributos `title`, `alt`, URLs seguras e o botão responsivo `nav-toggle`. |
| `Magento_Catalog/templates/product/list.phtml` | `vendor/magento/module-catalog/view/frontend/templates/product/list.phtml` | **Selo de Produto Assombrado (Desafio 17.1):** Sobrescrita integral do template de listagem de catálogo para injetar o selo visual do produto com base no atributo `product_badge` (Sprint 7). Mantém 100% da lógica de paginação, modos lista/grid, comparador, wishlist, AJAX add-to-cart e renderizadores de preço. |
| `Magento_Catalog/templates/product/view/addtocart.phtml` | `vendor/magento/module-catalog/view/frontend/templates/product/view/addtocart.phtml` | **Botões de Incremento e Decremento no Qty da PDP:** Adição de seletores interativos `+` e `-` com acessibilidade e validação de quantidade mínima, preservando a lógica de validação nativa de estoque e o script `Magento_Catalog/js/validate-product`. |
| `Magento_Checkout/web/template/minicart/content.html` | `vendor/magento/module-checkout/view/frontend/web/template/minicart/content.html` | **Banner do Caldeirão e Binding de Tremor (Desafios 17.2 e 17.5):** Sobrescrita do template Knockout do carrinho lateral para exibir a mensagem computada reativa (`halloweenMessage`) e aplicar o binding customizado `spookyShake` no ícone da bruxa. Todas as regiões nativas (`subtotalContainer`, `extraInfo`, `promotion`, etc.) foram rigorosamente preservadas. |
| `Magento_Sales/email/order_new.html` | `vendor/magento/module-sales/view/frontend/email/order_new.html` | **E-mail Transacional de Novo Pedido (Desafio 16.2):** Cópia integral do template de e-mail de pedido para clientes cadastrados, adicionando banner comemorativo da Noite Assombrada ("Covil da Noite Assombrada") e estilização condizente com a paleta de sangue e carvão. |
| `Magento_Sales/email/order_new_guest.html` | `vendor/magento/module-sales/view/frontend/email/order_new_guest.html` | **E-mail Transacional de Novo Pedido para Convidados (Desafio 16.2):** Cópia integral equivalente para assegurar uniformidade visual no fluxo de compra como visitante. |

> **Observação sobre Novos Componentes:** Arquivos como `announcement-bar.phtml`, `countdown.phtml`, `haunted-mode-toggle.phtml`, `countdown.js`, `spooky-shake.js` e `haunted-mode.js` são **novos arquivos criados para a campanha**, não constituindo cópias ou substituições de templates existentes do core.

---

## 6. Funcionalidades e Desafios Implementados

### Semana 16
- **16.1 — O Tema Noite Assombrada:**
  - Registro formal em `registration.php` e herança em `theme.xml` (`Magento/luma`).
  - Imagem de preview gerada em `media/preview.png`.
  - Sobrescrita de paleta em `_theme.less` com a identidade autêntica Horror-Shop: fundo pergaminho (`hs_bg_pattern_new.jpg`), cabeçalho com sangue pingando (`hs_newblut.png`), botões vermelho sangue e cantos decorados vintage (`bg_tl.png`, `bg_tr.png`, `bg_bl.png`, `bg_br.png`).
  - Divisores de caveira `.skullcenter` (com sprite `skulls.png`) e `.hs_skull_headline` (com linha `hs_skull_line.png`).
  - Recortes denteados `.masker` e `.masker_bot` com máscaras de recorte (`maske_top_2.png`, `maske_bottom_3.png`).
  - Tipografia Oswald declarada via `@font-face` utilizando `@{baseDir}fonts/Oswald/`.
- **16.2 — Estrutura, Layout XML, Tradução e E-mail:**
  - Layout XML `Magento_Theme/layout/default.xml` inserindo a Announcement Bar ("SAME-DAY SHIPPING", "SAVE AT THE CLUB - 10% OFF", "NOITE ASSOMBRADA").
  - Remoção do bloco `report.bugs` e reposicionamento do bloco `top.search` no cabeçalho.
  - Sobrescrita de `logo.phtml` com escape e suporte bilíngue.
  - Dicionário `i18n/pt_BR.csv` com mais de 20 termos traduzidos ("Adicionar ao Caldeirão", "Pacto Final", "Caçar", "Meu Baú Macabro", etc.).
  - E-mails transacionais customizados (`order_new.html`, `order_new_guest.html`, `_email-variables.less`, `_email-extend.less`).

### Semana 17
- **17.1 — Contagem Regressiva e Selo Assombrado:**
  - Componente Knockout `Magento_Theme/js/countdown.js` com timer em tempo real (atualização a cada 1000ms), observables e computeds formatando Dias, Horas, Minutos e Segundos até 31 de Outubro.
  - Tratamento de data expirada sem valores negativos.
  - Inserção na Home Page (`Magento_Cms/layout/cms_index_index.xml`) e na PDP (`Magento_Catalog/layout/catalog_product_view.xml`).
  - Selo visual `product_badge` exibido nos produtos com o atributo da Sprint 7, sem quebra de layout nos demais itens.
- **17.2 — Modo Assombrado e Mixin do Minicart:**
  - Switch de Modo Assombrado no cabeçalho carregado via `deps` no `requirejs-config.js` (`haunted-mode.js`).
  - Persistência no `localStorage` e alternância da classe `.haunted-mode` na tag `html`, ativando esquema ultra-dark com texto abóbora e cartões escuros.
  - Mixin Knockout em `Magento_Checkout/js/view/minicart` reagindo à quantidade de itens no carrinho com mensagens dinâmicas sobre o caldeirão de poções, mantendo `this._super()`.
- **17.3 — Mensagem Assombrada no Checkout:**
  - Módulo `Webjump_CheckoutComment` com plugin no `LayoutProcessor`, persistência via atributo de extensão na cotação e transferência para o pedido via observer.
  - Visualização destacada da mensagem no Admin de Pedidos.
- **17.4 — Caixão de Ofertas no Page Builder:**
  - Módulo `Webjump_PageBuilderCoffin` adicionando o content type "Caixão de Ofertas" ao Page Builder com formulário com 6 campos configuráveis (selo, título, descrição, imagem, link, botão), templates de preview e master, e estilização com cantos vintage e cores da campanha.
- **Diferencial 17.5 — Custom Knockout Binding (Spooky Shake):**
  - Binding customizado `spookyShake` registrado em `Magento_Theme/js/bindings/spooky-shake.js` com suporte a parâmetros (`intensity`, `onHover`, `trigger`).
  - Aplicado no ícone do contador regressivo e no ícone do caldeirão no minicart.
- **Diferencial 17.6 — Custom Customer-Data Section (Sessão Assombrada):**
  - Implementação de `Webjump\Samuel\CustomerData\SpookySession` implementando `SectionSourceInterface`.
  - Registrado no pool de seções via `etc/frontend/di.xml` sob a chave `spooky-session` e com regras de invalidação em `etc/frontend/sections.xml`.

---

## 7. Instruções de Verificação e Comandos Úteis

```bash
# Limpeza de cache
bin/magento cache:flush

# Compilação de DI e Código
bin/magento setup:di:compile

# Deploy de assets estáticos
bin/magento setup:static-content:deploy -f pt_BR en_US

# Verificação de padrões de código (PHPCS Magento 2)
bin/phpcs app/code/Webjump/Samuel/CustomerData/SpookySession.php
bin/phpcs app/code/Webjump/CheckoutComment/
```

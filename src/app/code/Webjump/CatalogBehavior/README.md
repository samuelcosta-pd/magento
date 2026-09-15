# Webjump_CatalogBehavior

Módulo de extensão do comportamento do catálogo - Desafio 13.2

---

## 1. Visão Geral do Desafio 13.2

Este módulo foi desenvolvido para atender aos requisitos da atividade **13.2 Estendendo o comportamento do catálogo**:

### Solução Entregue

1. **Parte 1 - Template Override + ViewModel e Plugin**: Modifica a apresentação visual na PDP (Product Detail Page) para exibir em destaque o alerta **“⚠️ ÚLTIMAS UNIDADES!”** quando o produto estiver com estoque crítico (1 a 3 unidades), sem alterar regras de negócio, estoque real, salabilidade ou fluxo de compra. A solução foi arquitetada via **Template Override limpo com ViewModel** (`StockStatus`) e estilização centralizada em `_module.less`, garantindo resiliência a mudanças de marcação de temas e traduções.
2. **Parte 2 - Observer**: Escuta o evento de ciclo de vida `catalog_product_save_after` e registra em `var/log/system.log` uma mensagem detalhada com ID, SKU, Nome e Status do produto sempre que ele for salvo.
3. **Integridade do Core**: implementado em `app/code/Webjump/CatalogBehavior/`, sem qualquer modificação no diretório `vendor/`.

---

## 2. Estrutura de Arquivos do Módulo

```text
app/code/Webjump/CatalogBehavior/
├── etc/
│   ├── module.xml
│   ├── di.xml
│   └── events.xml
├── ViewModel/
│   └── StockStatus.php
├── Plugin/
│   └── Block/
│       └── Stockqty/
│           └── AbstractStockqtyPlugin.php
├── Observer/
│   └── ProductSaveAfter.php
├── view/
│   └── frontend/
│       ├── layout/
│       │   └── catalog_product_view_type_simple.xml
│       ├── templates/
│       │   ├── product/
│       │   │   └── view/
│       │   │       └── type/
│       │   │           └── default.phtml
│       │   └── stockqty/
│       │       └── default.phtml
│       └── web/
│           └── css/
│               └── source/
│                   └── _module.less
├── registration.php
└── README.md
```

### Detalhamento dos Componentes

| Arquivo | Responsabilidade |
| :--- | :--- |
| [`registration.php`](registration.php) | Registra o módulo `Webjump_CatalogBehavior` no Magento. |
| [`etc/module.xml`](etc/module.xml) | Declara o módulo e dependências (`Magento_Catalog`, `Magento_CatalogInventory`). |
| [`etc/di.xml`](etc/di.xml) | Declara os plugins interceptors (ex: `AbstractStockqtyPlugin` para compatibilidade com bloco de threshold). |
| [`etc/events.xml`](etc/events.xml) | Registra o observer para o evento `catalog_product_save_after` em escopo global. |
| [`ViewModel/StockStatus.php`](ViewModel/StockStatus.php) | ViewModel que encapsula a regra de detecção de estoque baixo (1 a 3 unidades), compatível com MSI e legado, provendo mensagens traduzíveis. |
| [`view/frontend/templates/product/view/type/default.phtml`](view/frontend/templates/product/view/type/default.phtml) | Override do template nativo de estoque simples (`product.info.simple`), renderizando a classe semântica `.webjump-low-stock` sem estilos inline e sem depender de manipulação frágil de strings no HTML. |
| [`Plugin/Block/Stockqty/AbstractStockqtyPlugin.php`](Plugin/Block/Stockqty/AbstractStockqtyPlugin.php) | Plugin `after` que força `isMsgVisible()` como `true` para estoque de 1 a 3 unidades. |
| [`Observer/ProductSaveAfter.php`](Observer/ProductSaveAfter.php) | Implementa `ObserverInterface` para gravar log informativo ao salvar produto. |
| [`view/frontend/web/css/source/_module.less`](view/frontend/web/css/source/_module.less) | Estilos centralizados para as classes `.low-stock`, `.webjump-low-stock` e `.webjump-stock-alert` em conformidade com o tema Luma. |

---

## 3. Decisões Arquiteturais e Evolução Técnica

### Por que usar Observer para o log e Template Override + ViewModel para a apresentação?

* **Observer (na Parte 2 - registro em log):**
  Como a tarefa requer **reagir ao salvamento de um produto e gravar no log**, o mecanismo correto e desacoplado é o **Observer**, pois ele "escuta" o evento nativo disparado pela plataforma (`catalog_product_save_after`) sem interferir no fluxo nem no retorno da operação principal.

* **Template Override com ViewModel (na Parte 1 - apresentação do estoque baixo):**
  - **Problema da abordagem anterior**: Interceptar o `toHtml()` e aplicar `str_replace` procurando por tags literais (`'<span>' . __('In stock') . '</span>'`) é uma prática frágil. Se o tema for trocado, se classes forem alteradas ou se uma tradução ativa mudar a string, a substituição falha silenciosamente. Além disso, injetar `style="color: #e02b27..."` diretamente no PHP viola as boas práticas e quebra a separação de responsabilidades.
  - **Melhoria aplicada**:
    1. **Template Override**: Substituição de `Magento_Catalog::product/view/type/default.phtml` via layout XML no bloco `product.info.simple`. O template verifica semântica e diretamente a condição de estoque.
    2. **ViewModel (`StockStatus`)**: Toda a lógica de cálculo de saldo disponível (MSI com fallback para `StockRegistry`) e regras de threshold residem no ViewModel limpo, implementando `ArgumentInterface`.
    3. **Estilos em `_module.less`**: O HTML renderiza apenas classes CSS semânticas (`.stock.available.low-stock` e `.webjump-low-stock`), enquanto todas as regras visuais (cor vermelha `#e02b27`, negrito, alinhamento flexível e ícone) ficam centralizadas no arquivo LESS do módulo.

---

## 4. Implementação Técnica

### Parte 1 - Alerta de Estoque Baixo na PDP

* **Template Override**: `Webjump_CatalogBehavior::product/view/type/default.phtml` associado a `product.info.simple` em `view/frontend/layout/catalog_product_view_type_simple.xml`.
* **ViewModel**: `Webjump\CatalogBehavior\ViewModel\StockStatus`.
* **Regra de Apresentação**:
  * **1 a 3 unidades em estoque**: Renderiza `<span class="webjump-low-stock">&#9888; Últimas unidades!</span>` com classe `.low-stock`.
  * **$> 3$ unidades**: Mantém a renderização padrão (*"In stock"*).
  * **0 unidades (esgotado)**: Mantém a renderização padrão (*"Out of stock"*).
* **Estilização**: Definida em `_module.less` sob `.product-info-stock-sku .stock.available.low-stock` e `.webjump-low-stock`, sem qualquer estilo inline.

### Parte 2 - Observer (`ProductSaveAfter`)

* **Evento Escutado**: `catalog_product_save_after` (escopo global em `etc/events.xml`).
* **Implementação**: Classe implementando `Magento\Framework\Event\ObserverInterface`.
* **Serviço de Log**: Injeção de dependência `Psr\Log\LoggerInterface`.
* **Padrão de Mensagem**:
  ```text
  [Webjump_CatalogBehavior] Produto salvo — ID: {id} | SKU: {sku} | Nome: {nome} | Status: {status}
  ```

---

## 5. Critérios de Aceite Atendidos

| Critério de Aceite | Status | Onde e Como foi Atendido |
| :--- | :---: | :--- |
| **Apresentação visual resiliente e sem str_replace** | [x] Atendido | Implementado via Template Override com ViewModel (`StockStatus`), sem manipulação frágil de HTML renderizado. |
| **Estilos centralizados no LESS sem inline styles** | [x] Atendido | Estilização 100% contida em `view/frontend/web/css/source/_module.less` para a classe `.webjump-low-stock`. |
| **O observer está declarado em events.xml e dispara ao salvar um produto** | [x] Atendido | Declarado em [`etc/events.xml`](etc/events.xml) para o evento `catalog_product_save_after`. |
| **A mensagem aparece no log** | [x] Atendido | Mensagem registrada em `var/log/system.log` com prefixo `[Webjump_CatalogBehavior]`. |
| **Nenhum arquivo dentro de vendor/ foi modificado** | [x] Atendido | Validado via git status, garantindo integridade absoluta do diretório `vendor/`. |

---

## 6. Procedimentos de Validação e Teste

### 1. Testar o Alerta Visual na Loja

Acesse no navegador as páginas de produto:

1. **Produto com estoque baixo (1 a 3 unidades)**:
   * **Resultado**: Exibe o texto **“⚠️ Últimas unidades!”** formatado em vermelho `#e02b27` e negrito, via classe `.webjump-low-stock` sem CSS inline.
2. **Produto com estoque alto**:
   * **Resultado**: Exibe normalmente *"In stock"*.
3. **Produto sem estoque**:
   * **Resultado**: Exibe normalmente *"Out of stock"*.

### 2. Testar o Observer de Salvamento de Produto

* **Pelo Painel Admin**:
  1. Acesse **Catalog > Products**.
  2. Edite um produto e salve.
* **Pela Linha de Comando (CLI)**:
  ```bash
  bin/magento cache:flush
  ```

---

## 7. Evidências de Sucesso

* Arquivo `etc/di.xml` limpo de plugins frágeis de string replacement.
* Template [`view/frontend/templates/product/view/type/default.phtml`](view/frontend/templates/product/view/type/default.phtml) implementado de forma segura e semântica.
* ViewModel [`ViewModel/StockStatus.php`](ViewModel/StockStatus.php) com cobertura para MSI e CatalogInventory legado.
* Estilização declarativa no [`view/frontend/web/css/source/_module.less`](view/frontend/web/css/source/_module.less).

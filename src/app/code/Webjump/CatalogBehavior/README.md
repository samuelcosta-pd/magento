# Webjump_CatalogBehavior

Módulo de extensão do comportamento do catálogo - Desafio 13.2

---

## 1. Visão Geral do Desafio 13.2

Este módulo foi desenvolvido para atender aos requisitos da atividade **13.2 Estendendo o comportamento do catálogo**:

### Solução Entregue

1. **Parte 1 - Plugin `after`**: Modifica a apresentação visual na PDP (Product Detail Page) para exibir em destaque o alerta **“⚠️ ÚLTIMAS UNIDADES!”** quando o produto estiver com estoque crítico (1 a 3 unidades), sem alterar regras de negócio, estoque real, salabilidade ou fluxo de compra.
2. **Parte 2 - Observer**: Escuta o evento de ciclo de vida `catalog_product_save_after` e registra em `var/log/system.log` uma mensagem detalhada com ID, SKU, Nome e Status do produto sempre que ele for salvo.
3. **Integridade do Core**: implementado em `app/code/Webjump/CatalogBehavior/`, sem modificação no diretório `vendor/`.

---

## 2. Estrutura de Arquivos do Módulo

```text
app/code/Webjump/CatalogBehavior/
├── etc/
│   ├── module.xml
│   ├── di.xml
│   └── events.xml
├── Plugin/
│   └── Block/
│       ├── Product/
│       │   └── View/
│       │       └── Type/
│       │           └── SimpleProductViewPlugin.php
│       └── Stockqty/
│           └── AbstractStockqtyPlugin.php
├── Observer/
│   └── ProductSaveAfter.php
├── view/
│   └── frontend/
│       ├── layout/
│       │   └── catalog_product_view_type_simple.xml
│       ├── templates/
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
| [`etc/di.xml`](etc/di.xml) | Declara os plugins interceptors para modificação da visualização na PDP. |
| [`etc/events.xml`](etc/events.xml) | Registra o observer para o evento `catalog_product_save_after` em escopo global. |
| [`Plugin/Block/Product/View/Type/SimpleProductViewPlugin.php`](Plugin/Block/Product/View/Type/SimpleProductViewPlugin.php) | Plugin `after` que intercepta `toHtml()` de `Simple` para substituir *"IN STOCK"* por *"⚠️ ÚLTIMAS UNIDADES!"*. |
| [`Plugin/Block/Stockqty/AbstractStockqtyPlugin.php`](Plugin/Block/Stockqty/AbstractStockqtyPlugin.php) | Plugin `after` que força `isMsgVisible()` como `true` para estoque de 1 a 3 unidades. |
| [`Observer/ProductSaveAfter.php`](Observer/ProductSaveAfter.php) | Implementa `ObserverInterface` para gravar log informativo ao salvar produto. |
| [`view/frontend/web/css/source/_module.less`](view/frontend/web/css/source/_module.less) | Estilos do badge de urgência e alerta visual no tema Luma. |

---

## 3. Por que usei Plugin em um caso e Observer no outro?

A escolha entre Plugin e Observer se baseia no objetivo de cada parte do desafio:

* **Plugin (na Parte 1 - alteração visual do produto):**
  Como o desafio pedia para **modificar algo visível do produto** o mecanismo correto é o **Plugin do tipo `after`**, pois ele permite interceptar a execução de um método público da classe responsável pela exibição (`toHtml()`), receber o HTML pronto e alterar o texto de *"In stock"* para *"⚠️ ÚLTIMAS UNIDADES!"* antes que ele chegue à tela do usuário. O plugin é a ferramenta indicada sempre que precisamos **intervir no fluxo ou alterar o resultado** de uma função do Magento sem reescrever a classe original.

* **Observer (na Parte 2 - registro em log):**
  Como o desafio pedia para **reagir ao salvamento de um produto e gravar no log** o correto a se usar é o **Observer**, pois ele "escuta" um evento de ciclo de vida que a plataforma já dispara (`catalog_product_save_after`). O observer não precisa alterar o comportamento do Magento nem mexer nos dados do produto; ele apenas executa uma tarefa secundária (registrar uma linha no arquivo de log) de forma totalmente desacoplada.

**Em poucas palavras:**
* Usamos **Plugin** quando precisamos **mudar o comportamento, os parâmetros ou o retorno** de uma ação específica.
* Usamos **Observer** quando precisamos apenas **ser avisados de que algo aconteceu** no sistema para executar uma rotina complementar, sem interferir no processo principal.

---

## 4. Implementação Técnica

### Parte 1 - Plugin `after` (`SimpleProductViewPlugin`)

#### Como encontramos a classe correta para interceptar?
Para identificar com precisão a classe responsável pela renderização da disponibilidade na página do produto (PDP):
1. **Inspeção do elemento visual**: Ao inspecionar o bloco de estoque no navegador (*"IN STOCK"*), identificamos a estrutura `<div class="stock available"><span>In stock</span></div>`, localizada junto ao SKU.
2. **Rastreamento via Layout XML**: As páginas de produto utilizam o handle `catalog_product_view.xml`. Para produtos simples, o Magento estende esse layout através de `catalog_product_view_type_simple.xml`, que define o bloco `product.info.simple`.
3. **Localização da classe no Core**: Verificando a declaração desse bloco no módulo nativo `Magento_Catalog`, localizamos a classe PHP associada: `Magento\Catalog\Block\Product\View\Type\Simple`.
4. **Escolha do método público (`toHtml`)**: Todo bloco visual no Magento herda de `AbstractBlock`, cujo método responsável por gerar e retornar a string HTML final para o navegador é o `toHtml()`. Interceptar esse método com um plugin `after` (`afterToHtml`) nos permite manipular a string gerada e substituir o texto nativo com total segurança, sem depender de layouts complexos ou afetar regras fiscais e de estoque.

#### Detalhes Técnicos da Implementação
* **Classe Interceptada**: `Magento\Catalog\Block\Product\View\Type\Simple`
* **Método Interceptado**: `toHtml()`
* **Tipo**: `after` (`afterToHtml`)
* **Regra de Apresentação**:
  * **1 a 3 unidades em estoque**: Substitui a disponibilidade nativa (*"IN STOCK"*) pelo alerta **“⚠️ ÚLTIMAS UNIDADES!”** com destaque visual em vermelho (`#e02b27`).
  * **$> 3$ unidades**: Mantém a renderização original do Magento (*"IN STOCK"*).
  * **0 unidades (esgotado)**: Mantém a renderização original do Magento (*"OUT OF STOCK"*).

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
| **O plugin está declarado no di.xml e funciona na loja** | [x] Atendido | Declarado em [`etc/di.xml`](etc/di.xml) e ativo na PDP substituindo o texto de estoque para 1 a 3 unidades. |
| **O observer está declarado em events.xml e dispara ao salvar um produto** | [x] Atendido | Declarado em [`etc/events.xml`](etc/events.xml) para o evento `catalog_product_save_after`, acionado via Admin e CLI. |
| **A mensagem aparece no log (print em evidências de sucesso)** | [x] Atendido | Mensagem registrada em `var/log/system.log` com prefixo `[Webjump_CatalogBehavior]`. |
| **Nenhum arquivo dentro de vendor/ foi modificado** | [x] Atendido | Validado via `git status vendor/` e `git diff vendor/`, comprovando integridade total do core. |
| **README responde: por que usei plugin em um caso e observer no outro?** | [x] Atendido | Respondido na [Seção 3](#3-por-que-usei-plugin-em-um-caso-e-observer-no-outro). |

---

## 6. Procedimentos de Validação e Teste

### 1. Testar o Alerta Visual na Loja (Plugin `after`)

Acesse no navegador as seguintes URLs:

1. **Produto com estoque baixo (2 unidades)**:
   * URL: [https://magento.test/joust-duffle-bag.html](https://magento.test/joust-duffle-bag.html)
   * **Resultado**: Exibe o badge em vermelho **“⚠️ ÚLTIMAS UNIDADES!”** no lugar de *"IN STOCK"*.
2. **Produto com estoque alto (100 unidades)**:
   * URL: [https://magento.test/fusion-backpack.html](https://magento.test/fusion-backpack.html)
   * **Resultado**: Exibe normalmente o padrão *"IN STOCK"*.
3. **Produto sem estoque (0 unidades)**:
   * URL: [https://magento.test/sprite-yoga-companion-kit.html](https://magento.test/sprite-yoga-companion-kit.html)
   * **Resultado**: Exibe normalmente o padrão *"OUT OF STOCK"*.

### 2. Testar o Observer de Salvamento de Produto

* **Pelo Painel Admin**:
  1. Acesse [https://magento.test/admin](https://magento.test/admin) > **Catalog > Products**.
  2. Edite qualquer produto e clique no botão **Save**.
* **Pela Linha de Comando (CLI)**:
  ```bash
  docker exec magento-phpfpm-1 php -r "
  require '/var/www/html/app/bootstrap.php';
  \$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, \$_SERVER);
  \$om = \$bootstrap->getObjectManager();
  \$repo = \$om->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
  \$product = \$repo->get('24-MB01');
  \$repo->save(\$product);
  echo 'Produto salvo via CLI.' . PHP_EOL;
  "
  ```

### 3. Verificar o Registro no Log

```bash
docker exec -it magento-phpfpm-1 tail -f /var/www/html/var/log/system.log | grep --line-buffered "Webjump_CatalogBehavior"
```
---

## 7. Desafios Enfrentados e Decisões Técnicas

Durante o desenvolvimento do módulo, foram tomadas decisões arquiteturais importantes para contornar peculiaridades do Magento:

1. **O Conflito com o Plugin `around` do MSI (`Magento_InventorySalesFrontendUi`)**:
   * *Desafio*: Na tentativa inicial de estender `AbstractStockqty::isMsgVisible()`, o módulo core do MSI implementa um plugin `around` com `sortOrder="0"` que calcula o estoque e **não chama `$proceed()`**, interrompendo a cadeia de plugins subsequentes.
   * *Solução*: Configuramos `sortOrder="-10"` em `di.xml` para envelopar o plugin do MSI e, como garantia definitiva de apresentação na loja, implementamos o plugin `SimpleProductViewPlugin` interceptando diretamente o `toHtml()` do bloco de visualização simples (`Simple`), garantindo que o alerta seja renderizado na PDP sem depender de chamadas internas da cadeia de inventory.

2. **Isolamento da Apresentação**:
   * *Decisão*: Evitamos intencionalmente métodos de regra de negócio como `isSalable()` ou `isAvailable()`. Alterar esses métodos poderia impedir a compra ou afetar a integração com meios de pagamento/carrinho. A interceptação focou na saída HTML (`toHtml`).

3. **Prevenção de Edição do Core (`vendor/`)**:
   * *Decisão*: Toda a engenharia reversa foi feita por inspeção de código. Nenhuma classe do core foi editada, mantendo o Magento preparado para futuras atualizações sem quebra de compatibilidade.

---

## 8. Evidências de Sucesso e Critérios de Aceite
---

### 1. O plugin está declarado no di.xml e funciona na loja

* **Os prints comprovam:**
  1. O plugin está configurado no arquivo `etc/di.xml`.
  2. O alerta visual é renderizado na página do produto na loja quando o estoque está baixo.

> <img width="1465" height="886" alt="image" src="https://github.com/user-attachments/assets/dcc3ecd8-91f7-4985-b158-f30f0a61b7ca" />
> <img width="1707" height="1231" alt="image" src="https://github.com/user-attachments/assets/63777faf-9101-4455-91db-9ae58ce5a56f" />

---

### 2. O observer está declarado em events.xml e dispara ao salvar um produto

* **Os prints comprovam:**
  1. O observer está registrado no arquivo `etc/events.xml` para o evento `catalog_product_save_after`.
  2. A rotina é executada ao salvar um produto no Magento.

> <img width="1516" height="579" alt="image" src="https://github.com/user-attachments/assets/793f6444-c268-45d9-9020-11c861016013" />
> <img width="1644" height="1182" alt="image" src="https://github.com/user-attachments/assets/8947f121-c949-4cae-adea-c826a1876964" />
> <img width="2167" height="1124" alt="image" src="https://github.com/user-attachments/assets/ea8bb10a-7b5e-4d92-84c6-c05093555a30" />

---




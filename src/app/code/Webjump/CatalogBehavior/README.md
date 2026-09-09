# Webjump_CatalogBehavior

Módulo de extensão do comportamento do catálogo — Desafio 13.2 (Magento Open Source 2.4.8-p1)

---

## Objetivo do Módulo

Estender o comportamento do catálogo do Magento **sem alterar nenhum arquivo dentro de `vendor/`**, cumprindo os dois requisitos do desafio:

1. **Parte 1 — Plugin `after`**: Modificar a apresentação visual da página do produto (PDP) para indicar **“⚠️ Últimas unidades!”** quando o produto estiver com estoque baixo (1 a 3 unidades), sem interferir na lógica de estoque real, salabilidade, carrinho ou checkout.
2. **Parte 2 — Observer**: Reagir ao evento da plataforma `catalog_product_save_after`, registrando no log do sistema uma mensagem detalhada sempre que um produto for salvo (via Admin, API ou CLI).

---

## Estrutura de Arquivos do Módulo

```text
Webjump/CatalogBehavior/
├── etc/
│   ├── module.xml
│   ├── di.xml
│   └── events.xml
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
│       │   └── stockqty/
│       │       └── default.phtml
│       └── web/
│           └── css/
│               └── source/
│                   └── _module.less
├── registration.php
└── README.md
```

---

## Fundamentação Conceitual: Plugin vs Observer

| Mecanismo | Quando usar | Por que foi usado nesta tarefa |
| :--- | :--- | :--- |
| **Plugin (`Interceptor`)** | Quando precisamos **modificar o comportamento, os parâmetros ou o resultado** de um método público específico de uma classe do núcleo. | **Parte 1**: Precisávamos interceptar a chamada do método público `isMsgVisible()` do bloco `AbstractStockqty` para retornar `true` sob nossa regra de negócio (1 a 3 unidades), forçando a exibição visual da mensagem. |
| **Observer (`Eventos`)** | Quando precisamos **reagir a um evento de ciclo de vida da plataforma** de forma desacoplada, sem alterar o fluxo nem o retorno do método que disparou o evento. | **Parte 2**: Precisávamos registrar no log que um produto foi salvo, reagindo ao evento nativo `catalog_product_save_after` sem acoplar nossa regra à persistência ou repositório de produto. |

---

## Parte 1 — Plugin `after`

### Comportamento
* **1 a 3 unidades disponíveis**: Exibe o aviso visual **“⚠️ Últimas unidades!”** logo abaixo da disponibilidade e acima do SKU.
* **Mais de 3 unidades (estoque normal)**: Mantém o comportamento original do Magento (apenas *"In stock"*).
* **0 unidades (sem estoque)**: Mantém o comportamento original do Magento (*"Out of stock"*).

### Implementação Técnica
* **Classe Alvo**: `Magento\CatalogInventory\Block\Stockqty\AbstractStockqty`
* **Método Interceptado**: `isMsgVisible()`
* **Tipo**: `after` (`afterIsMsgVisible`) com `sortOrder="-10"`
* **Template Customizado**: `Webjump_CatalogBehavior::stockqty/default.phtml` referenciado no layout `catalog_product_view_type_simple.xml`.
* **Estilização**: `_module.less` adicionando classe `.webjump-stock-alert` com tom de alerta (`#d9534f`) e alinhamento visual.

### O que NÃO é alterado
* Quantidade real de estoque em banco.
* Salabilidade do produto (`isSalable()`).
* Quantidade salável do MSI (`getSalableQty()`).
* Regras de carrinho, minicart ou checkout.
* Nenhum arquivo em `vendor/`.

---

## Parte 2 — Observer

### Comportamento
Escuta o evento `catalog_product_save_after` e gera no log (`var/log/system.log`) uma linha informativa com os dados do produto salvo:

```text
[Webjump_CatalogBehavior] Produto salvo — ID: 1 | SKU: 24-MB01 | Nome: Joust Duffle Bag | Status: Habilitado
```

### Implementação Técnica
* **Evento**: `catalog_product_save_after` declarado em `etc/events.xml`.
* **Classe**: `Webjump\CatalogBehavior\Observer\ProductSaveAfter` implementando `Magento\Framework\Event\ObserverInterface`.
* **Logger**: Injeção de dependência via PSR-3 `Psr\Log\LoggerInterface`.

---

## Resumo dos Problemas e Soluções Encontrados

Durante toda a concepção, desenvolvimento e depuração deste módulo, passamos pelas seguintes situações, análises e soluções definitivas:

| Problema / Situação | Análise / Investigação | Solução Definitiva | Status |
| :--- | :--- | :--- | :--- |
| **Alteração sem modificar o `vendor/`** | O Magento 2 proíbe edição no core para preservar atualizações e modularidade. | Módulo customizado `Webjump_CatalogBehavior` criado em `app/code/`. | **Concluído** |
| **Implementação de plugin `after`** | A tarefa 13.2 exige especificamente um plugin do tipo `after` para a primeira etapa. | Criado `AbstractStockqtyPlugin.php` com o método `afterIsMsgVisible()` e registrado no `etc/di.xml`. | **Concluído** |
| **Escolha do método `isMsgVisible()` como ponto de extensão** | Era preciso uma alteração estritamente de apresentação. | `isMsgVisible()` controla unicamente a renderização do bloco de mensagem sem afetar estoque, salabilidade, carrinho ou checkout. | **Concluído** |
| **Confirmação de registro do plugin no DI** | Necessidade de verificar se o Magento reconheceu o plugin. | Validação via `bin/magento dev:di:info 'Magento\CatalogInventory\Block\Stockqty\DefaultStockqty'`, confirmando o interceptor ativo. | **Concluído** |
| **Garantia de tipo `after`** | O plugin precisa rodar após a execução do método original. | Confirmado via `dev:di:info`: `Webjump\CatalogBehavior\Plugin\Block\Stockqty\AbstractStockqtyPlugin \| isMsgVisible \| after`. | **Concluído** |
| **Validação de sintaxe PHP** | Evitar erros de compilação ou parse durante o deploy. | Executado `php -l` em todos os arquivos PHP do módulo com retorno: *“No syntax errors detected”*. | **Concluído** |
| **Mensagem não aparecia no produto de teste** | Ao abrir a PDP de `24-MB01`, nenhum aviso aparecia na tela. | Investigamos o bloco `DefaultStockqty`, seu container pai no layout e o template PHTML correspondente. | **Concluído** |
| **Descoberta da funcionalidade nativa de estoque baixo** | O Magento já possui nativamente o `Only X left Threshold` que exibe a mensagem *“Only X left”*. | Confirmamos que o bloco já existia, mas sua exibição estava condicionada ao threshold das configurações gerais. | **Concluído** |
| **`afterIsMsgVisible()` sozinho não mudava o texto exibido** | Mesmo que `isMsgVisible()` retornasse `true`, o texto exibido nativamente seria *"Only %1 left"*, e não *"Últimas unidades!"*. | Criamos o template `view/frontend/templates/stockqty/default.phtml` e o layout XML para substituir o template nativo. | **Concluído** |
| **Configuração `Only X left Threshold` em `0`** | O threshold global do Magento estava zerado, fazendo a verificação nativa sempre retornar `false`. | Nosso plugin `after` independe dessa configuração: ele injeta sua própria regra de negócio (1 a 3 unidades). | **Concluído** |
| **Produto de teste com estoque alto (100)** | O produto `24-MB01` estava com estoque abundante. | Quantidade ajustada para `2` para simular o cenário real de estoque baixo. | **Concluído** |
| **Configurações específicas de inventário do produto** | Validar se parâmetros de catálogo impediam a renderização. | Confirmados `Out-of-Stock Threshold = 0`, `Backorders = No Backorders`, `Stock Status = In Stock`, quantidade mínima 1. | **Concluído** |
| **Confusão entre `Notify for Quantity Below` e `Only X left Threshold`** | Suspeita de que o aviso de estoque dependia de `Notify for Quantity Below`. | Esclarecido: `Notify for Quantity Below` é um alerta administrativo interno; `Only X left Threshold` controla a mensagem da loja. | **Concluído** |
| **Requisito visual específico: “Últimas unidades!”** | O cliente não queria o texto nativo em inglês *"Only X left"*. | Template PHTML customizado para imprimir com segurança `<span>&#9888; <?= __('Últimas unidades!') ?></span>`. | **Concluído** |
| **Risco de escolher métodos de negócio (`isSalable`, `isAvailable`)** | Alterar esses métodos poderia impedir a compra ou afetar regras fiscais/estoque. | Esses métodos foram intencionalmente **descartados**, mantendo o foco estrito na camada de apresentação (View). | **Concluído** |
| **Inspeção sem edição de arquivos em `vendor/`** | Foi necessário inspecionar os blocos e templates do módulo `Magento_CatalogInventory`. | Arquivos do core foram apenas lidos para engenharia reversa; nenhuma linha do `vendor/` foi tocada. | **Concluído** |
| **Identificação do container e bloco no Layout XML** | Saber onde o bloco é injetado na árvore do Magento. | Rastreamento feito: `product.info.type` → container `product.info.simple.extra` → bloco `product.info.simple.extra.catalog_inventory_stockqty_default`. | **Concluído** |
| **Inconsistência de código compilado entre Host e Container Docker** | Alterações no host não refletiam no PHP em execução no container. | Execução obrigatória de `bin/magento setup:di:compile` e `cache:flush` diretamente dentro do container `magento-phpfpm-1`. | **Concluído** |
| **Conflito com o Plugin `around` do MSI (`aroundIsMsgVisible`)** | O MSI possui um plugin `around` com `sortOrder="0"` que **não chama `$proceed()`**, abortando plugins subsequentes da cadeia. | Configuramos `sortOrder="-10"` no nosso `etc/di.xml`. Nosso plugin passa a envelopar o MSI por fora e recebe o resultado normalmente. | **Concluído** |
| **Instanciação isolada do bloco vs template em testes PHP CLI** | Testes diretos via ObjectManager retornavam HTML vazio mesmo com `isMsgVisible() === true`. | Constatado que o layout XML injeta o template. No CLI, a validação exige `$block->setTemplate(...)`; no browser funciona automaticamente via layout. | **Concluído** |
| **Isolamento de regras (0 unidades vs 1–3 vs > 3)** | Garantir que produtos sem estoque ou com estoque alto não mostrem a mensagem. | Regra estrita no plugin: `$stockQtyLeft > 0 && $stockQtyLeft <= 3`. Validado com sucesso via requisições HTTP para os 3 cenários. | **Concluído** |
| **Estilização e destaque visual no tema Luma** | O texto precisava de destaque com ícone e cor de alerta sem quebrar o layout de SKU e preço. | Criado `view/frontend/web/css/source/_module.less` com a classe `.webjump-stock-alert` (`#d9534f`, semibold, flex alignment). | **Concluído** |
| **Segunda parte da tarefa: Observer para `catalog_product_save_after`** | Reagir ao salvamento de produto sem acoplamento. | Criado `etc/events.xml` e `Observer/ProductSaveAfter.php` com injeção do `LoggerInterface`. | **Concluído** |
| **Gravação e validação do log do Observer** | Comprovar que o log é disparado em ações reais de salvamento. | Validado via CLI e painel Admin; log gravado com sucesso em `var/log/system.log`. | **Concluído** |
| **Documentação conceitual Plugin x Observer no README** | Explicar as diferenças conceituais e a razão da escolha de cada mecanismo. | Seção dedicada incluída no README detalhando as responsabilidades de cada padrão no Magento 2. | **Concluído** |

---

## Detalhe Arquitetural Crítico: O Enigma do `sortOrder="-10"` e o MSI

Um dos pontos mais avançados e ricos desta implementação foi a descoberta do comportamento da cadeia de interceptação do Magento 2 em conjunto com o Multi-Source Inventory (MSI):

1. **O Cenário**: O módulo core `Magento_InventorySalesFrontendUi` possui um plugin `around` sobre `AbstractStockqty::isMsgVisible()` (`sortOrder="0"`).
2. **O Problema do `$proceed()`**: O código do MSI calcula o estoque salável e retorna um booleano diretamente, **sem chamar `$proceed()`**:
   ```php
   public function aroundIsMsgVisible(AbstractStockqty $subject, callable $proceed): bool
   {
       // ... lógica do MSI ...
       return $this->qtyLeftChecker->execute($productSalableQty); // NÃO invoca $proceed()
   }
   ```
3. **Mecanismo de Interceptação (`___callPlugins`)**: No Magento 2, os plugins com `sortOrder` maior que o do `around` ficam encapsulados dentro da closure `$next` passada como `$proceed`. Como o MSI não invoca `$proceed`, a closure interna **nunca é executada**. Portanto, nosso plugin `after` original (com `sortOrder="100"`) era sumariamente ignorado.
4. **A Solução Elegante**: Ao declarar `sortOrder="-10"` no nosso plugin, nosso interceptor passa a ocupar a **camada mais externa**:
   * O `around` do MSI executa internamente e retorna `false`.
   * Esse retorno é recebido como argumento `$result` pelo nosso método `afterIsMsgVisible($subject, $result)`.
   * Nosso plugin avalia se o estoque está entre 1 e 3 unidades e retorna `true`, forçando a exibição da mensagem de alerta com total compatibilidade com o MSI!

---

## Procedimentos de Teste e Validação

### 1. Testar o Alerta Visual (Plugin `after`)

1. Acesse o produto de teste com estoque baixo (2 unidades):
   * URL: [https://magento.test/joust-duffle-bag.html](https://magento.test/joust-duffle-bag.html)
   * **Resultado esperado**: Exibição em destaque de:
     ```html
     <div class="availability only webjump-stock-alert" title="Últimas unidades!">
         <span>⚠️ Últimas unidades!</span>
     </div>
     ```
2. Acesse um produto com estoque alto (ex: 100 unidades em [https://magento.test/fusion-backpack.html](https://magento.test/fusion-backpack.html)):
   * **Resultado esperado**: Alerta **não** é exibido.
3. Acesse um produto sem estoque (ex: 0 unidades em [https://magento.test/sprite-yoga-companion-kit.html](https://magento.test/sprite-yoga-companion-kit.html)):
   * **Resultado esperado**: Alerta **não** é exibido (mantém *"Out of stock"* nativo).

---

### 2. Testar o Registro de Log (Observer)

#### Pelo Painel Administrativo:
1. Acesse o Admin: [https://magento.test/admin](https://magento.test/admin).
2. Vá em **Catalog > Products**, abra qualquer produto e clique em **Save**.

#### Pela Linha de Comando (CLI):
```bash
docker exec magento-phpfpm-1 php -r "
require '/var/www/html/app/bootstrap.php';
\$bootstrap = \Magento\Framework\App\Bootstrap::create(BP, \$_SERVER);
\$om = \$bootstrap->getObjectManager();
\$state = \$om->get(\Magento\Framework\App\State::class);
try {
    \$state->setAreaCode(\Magento\Framework\App\Area::AREA_ADMINHTML);
} catch (\Exception \$e) {}

\$productRepo = \$om->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
\$product = \$productRepo->get('24-MB01');
\$productRepo->save(\$product);
echo 'Produto salvo via script.' . PHP_EOL;
"
```

#### Comandos para Verificar o Log:
```bash
# Ver as últimas linhas registradas:
docker exec magento-phpfpm-1 grep "Webjump_CatalogBehavior" /var/www/html/var/log/system.log

# Acompanhar em tempo real:
docker exec -it magento-phpfpm-1 tail -f /var/www/html/var/log/system.log | grep --line-buffered "Webjump_CatalogBehavior"
```

---

## Estado Atual do Desafio 13.2

**100% Concluído e Validado em Ambiente Real:**

- [x] Módulo `Webjump_CatalogBehavior` criado e ativo.
- [x] Plugin `after` implementado em `AbstractStockqty::isMsgVisible()` com `sortOrder="-10"`.
- [x] Regra de apresentação visual ativa: 1 a 3 unidades exibe **“⚠️ Últimas unidades!”**; 0 ou > 3 unidades mantém o padrão.
- [x] Template e layout customizados sem alterar nenhum arquivo dentro de `vendor/`.
- [x] Estilização LESS criada para o alerta.
- [x] Observer registrado para o evento `catalog_product_save_after` em escopo global.
- [x] Registro estruturado em log testado e validado em `var/log/system.log`.
- [x] Nenhuma alteração em estoque real, salabilidade, carrinho ou checkout.
- [x] Documentação técnica completa e detalhada consolidada neste README.

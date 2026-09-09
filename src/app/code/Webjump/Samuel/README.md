# Webjump_Samuel

Módulo de criação de bloco próprio na Home Page com separação de lógica via ViewModel — Desafio 13.1 (Magento Open Source 2.4.8-p1)

---

## 1. Objetivo do Módulo

O objetivo principal deste módulo é criar uma extensão do zero no Magento 2 para exibir um **bloco customizado na página inicial da loja**, aplicando as melhores práticas de desenvolvimento da plataforma:

* **Separação estrita de camadas**: Toda a regra de negócio e recuperação de dados reside no **ViewModel**, mantendo a classe de Bloco e o template (`.phtml`) focados exclusivamente na apresentação.
* **Vitrine de "Últimas Unidades"**: Exibição dinâmica de produtos do catálogo com estoque crítico ($\le 5$ unidades), criando um senso de urgência e oportunidade de compra.
* **Segurança e Escapamento**: 100% das saídas dinâmicas no template são tratadas utilizando `$escaper->escapeHtml()`, `$escaper->escapeUrl()` e `$escaper->escapeHtmlAttr()`.
* **Design e CSS Próprio**: Estilização moderna e responsiva utilizando o pré-processador LESS oficial do Magento (`_module.less`), com layout em grid, cards elegantes, badges de alerta e micro-interações de hover.

---

## 2. Estrutura de Diretórios Criada

```text
app/code/Webjump/Samuel/
├── Block/
│   └── HomeBlock.php
├── etc/
│   └── module.xml
├── registration.php
├── ViewModel/
│   └── Home.php
├── view/
│   └── frontend/
│       ├── layout/
│       │   └── cms_index_index.xml
│       ├── templates/
│       │   └── home.phtml
│       └── web/
│           └── css/
│               └── source/
│                   └── _module.less
└── README.md
```

### Detalhamento das Pastas e Arquivos

| Diretório / Arquivo | Responsabilidade |
| :--- | :--- |
| [`registration.php`](registration.php) | Registra o módulo `Webjump_Samuel` no ecossistema do Magento através de `\Magento\Framework\Component\ComponentRegistrar::register()`. |
| [`etc/module.xml`](etc/module.xml) | Declara o módulo e define suas dependências de carregamento (`<sequence>`), garantindo que `Magento_Catalog`, `Magento_CatalogInventory`, `Magento_Theme` e `Magento_Cms` sejam inicializados antes. |
| [`Block/HomeBlock.php`](Block/HomeBlock.php) | Classe de bloco PHP que estende `\Magento\Framework\View\Element\Template`. Permanece limpa, sem lógica de negócio, atuando como o ponto de ancoragem para o layout e injeção do ViewModel. |
| [`ViewModel/Home.php`](ViewModel/Home.php) | Classe que implementa `\Magento\Framework\View\Element\Block\ArgumentInterface`. Concentra toda a lógica de recuperação dos produtos de estoque baixo, formatação de preços e geração de URLs de imagem. |
| [`view/frontend/layout/cms_index_index.xml`](view/frontend/layout/cms_index_index.xml) | Instrução de layout XML que injeta o bloco no container `content` da Home Page (`cms_index_index`) e passa o ViewModel como argumento do tipo `object`. |
| [`view/frontend/templates/home.phtml`](view/frontend/templates/home.phtml) | Template PHTML responsável pela renderização visual do bloco (grid de produtos, badges de estoque, preços e links). |
| [`view/frontend/web/css/source/_module.less`](view/frontend/web/css/source/_module.less) | Folha de estilos do módulo. O Magento compila este arquivo automaticamente no bundle de estilos do tema (`styles-m.css` / `styles-l.css`). |

---

## 3. Decisão Arquitetural: ViewModel vs Fat Block

Nas versões antigas do Magento 2 (anteriores à 2.2), a forma padrão de disponibilizar dados para um template era criar métodos na própria classe do Bloco:

```php
// Padrão antigo (desencorajado):
class HomeBlock extends \Magento\Framework\View\Element\Template {
    public function getLowStockProducts() { ... }
}
```

Esse padrão trazia desvantagens graves:
1. **Acoplamento excessivo**: O bloco herda uma árvore gigante de dependências (`AbstractBlock`, `Context`, `Session`, `Layout`, etc.).
2. **Dificuldade de testes**: Testar unitariamente um bloco exige mockar dezenas de dependências do framework.
3. **Falta de reutilização**: A lógica fica presa à hierarquia de renderização do bloco.

### O Padrão ViewModel Adotado

A partir do Magento 2.2+, a Adobe recomenda o **ViewModel Pattern**:
* O ViewModel implementa apenas a interface marcadora `\Magento\Framework\View\Element\Block\ArgumentInterface`.
* Não herda nenhuma classe do framework de visualização.
* O construtor recebe apenas as dependências necessárias via injeção de dependência pura (`ProductCollectionFactory`, `ImageHelper`, `PriceCurrencyInterface`).
* É injetado no layout XML como um argumento simples:
  ```xml
  <arguments>
      <argument name="view_model" xsi:type="object">Webjump\Samuel\ViewModel\Home</argument>
  </arguments>
  ```
* O template obtém o ViewModel com `$block->getData('view_model')` e consome seus métodos diretamente.

---

## 4. Critérios de Aceite Atendidos

| Critério | Status | Implementação / Evidência |
| :--- | :---: | :--- |
| **Módulo ativo em `bin/magento module:status`** | Concluído | `Webjump_Samuel : Module is enabled`. |
| **Bloco aparece na home da loja** | Concluído | Injetado no handle `cms_index_index` no container `content`. |
| **Lógica no ViewModel, não no template nem no Block** | Concluído | [`Home.php`](ViewModel/Home.php) executa a filtragem de estoque e formata dados; [`HomeBlock.php`](Block/HomeBlock.php) e [`home.phtml`](view/frontend/templates/home.phtml) contêm apenas renderização. |
| **Toda saída passa por `escapeHtml()` ou equivalente** | Concluído | `$escaper->escapeHtml()`, `$escaper->escapeUrl()` e `$escaper->escapeHtmlAttr()` cobrem todos os campos dinâmicos. |
| **CSS próprio em `view/frontend/web/` aplicado ao bloco** | Concluído | [`_module.less`](view/frontend/web/css/source/_module.less) implementa cards responsivos, badges de urgência e transições suaves. |
| **README explica a estrutura de pastas criada** | Concluído | Documento atual descrevendo arquitetura, arquivos e diretórios. |

---

## 5. Procedimentos de Validação e Teste

### 1. Verificar o Status do Módulo
```bash
bin/magento module:status Webjump_Samuel
```

### 2. Limpar Cache e Compilar Estilos
```bash
bin/magento cache:flush
```

### 3. Acessar a Home da Loja
Abra o navegador em:
* [https://magento.test/](https://magento.test/)

**Resultado Esperado:**
* Uma seção intitulada **"Últimas Unidades em Estoque"** no topo da página inicial, exibindo cards com fotos dos produtos, badge de estoque crítico (ex: *"Restam 2 unidades!"*), barra de disponibilidade proporcional, preço formatado e botão com link direto para a página do produto (PDP).

---

## 6. Problemas Enfrentados e Soluções

Durante o desenvolvimento do módulo no ambiente Docker (`docker-magento`), foram enfrentados três desafios principais:

### 1. Módulo não encontrado pelo Magento (`Module does not exist`)
* **Problema:** Ao tentar habilitar o módulo via CLI, o container do PHP-FPM não enxergava os novos arquivos criados no host devido a uma inconsistência no bind mount do Docker.
* **Solução:** Reiniciar o container com `bin/docker-compose restart phpfpm`. O Docker remontou o volume e os arquivos ficaram visíveis imediatamente.

### 2. Ponto de montagem corrompido por cópia manual (`docker cp`)
* **Problema:** A tentativa de copiar a pasta do módulo para o container usando `docker cp` corrompeu o ponto de montagem, travando o diretório como ocupado (*device or resource busy*).
* **Solução:** Como o ambiente sincroniza `src/app/code/` automaticamente via bind mount, o uso de `docker cp` foi abandonado e o container foi reiniciado para restaurar o estado normal.

### 3. Alterações de CSS (LESS) não refletiam na loja
* **Problema:** O arquivo `_module.less` foi criado com os estilos do bloco, mas a Home continuava sendo renderizada sem a estilização, pois o Magento manteve o cache pré-processado antigo em `var/view_preprocessed/`.
* **Solução:** Limpeza forçada dos arquivos estáticos pré-processados e flush do cache:
  ```bash
  bin/magento cache:flush
  ```
  Na requisição seguinte, o Magento recompilou o LESS com os novos estilos do módulo.

---

## 7. Evidências de Sucesso e Critérios de Aceite

Abaixo estão relacionadas as comprovações de atendimento para cada um dos critérios de aceite exigidos na atividade:

---

### [x] 1. O módulo aparece como ativo em `bin/magento module:status`
* **Comando executado:**
  ```bash
  bin/magento module:status Webjump_Samuel
  ```
* **Saída obtida:**
  ```text
  Webjump_Samuel : Module is enabled
  ```
* **Comprovação Visual:**
  <!-- Inserir print do terminal com a execução do comando module:status -->
  ![Evidência 1 - Módulo Ativo](docs/images/01-module-status.png)

---

### [x] 2. O bloco aparece na home da loja
* **Validação:** Injetado via layout handle `cms_index_index` no container `content` com posicionamento prioritário (`before="-"`).
* **HTML renderizado na Home:**
  ```html
  <section class="webjump-samuel-home-block" aria-labelledby="low-stock-title">
      <div class="block-header">
          <h2 id="low-stock-title" class="block-title">Últimas Unidades em Estoque</h2>
          <p class="block-subtitle">Produtos esgotando. Aproveite as ofertas antes que zerem os estoques!</p>
      </div>
      <div class="products-grid">
          <!-- 4 cards de produtos com estoque <= 5 -->
      </div>
  </section>
  ```
* **Comprovação Visual:**
  <!-- Inserir print da página inicial (https://magento.test/) exibindo o bloco no topo -->
  ![Evidência 2 - Bloco na Home](docs/images/02-block-on-home.png)

---

### [x] 3. A lógica está no ViewModel, não no template nem no Block
* **Validação:**
  * [`HomeBlock.php`](Block/HomeBlock.php): Classe limpa herdando de `Template`, sem métodos de negócio.
  * [`Home.php`](ViewModel/Home.php): Implementa `ArgumentInterface` e encapsula a consulta via `ProductCollectionFactory`, filtrando produtos com estoque salável $\le 5$, formatando preços e URLs.
  * [`home.phtml`](view/frontend/templates/home.phtml): Contém apenas estruturas de repetição e renderização da apresentação.
* **Comprovação Visual:**
  <!-- Inserir print do código do ViewModel e do Bloco evidenciando a separação de responsabilidades -->
  ![Evidência 3 - ViewModel e Block](docs/images/03-viewmodel-separation.png)

---

### [x] 4. Toda saída passa por `escapeHtml()` ou equivalente
* **Validação:** O template [`home.phtml`](view/frontend/templates/home.phtml) utiliza as funções nativas de segurança do `$escaper`:
  * `$escaper->escapeHtml()` em textos e nomes de produtos.
  * `$escaper->escapeUrl()` em links de imagem e páginas de detalhe (PDP).
  * `$escaper->escapeHtmlAttr()` em atributos HTML (`alt`, `title`, `style`).
* **Comprovação Visual:**
  <!-- Inserir print do template home.phtml destacando as chamadas ao $escaper -->
  ![Evidência 4 - Escapamento de Saída](docs/images/04-escaper-usage.png)

---

### [x] 5. O módulo tem CSS próprio em `view/frontend/web/` aplicado ao bloco
* **Validação:** Folha de estilos criada em [`view/frontend/web/css/source/_module.less`](view/frontend/web/css/source/_module.less), compilada pelo Magento dentro de `styles-m.css` com:
  * Grid responsivo (`display: grid`).
  * Cards com elevação e efeito hover (`transform: translateY(-4px)`).
  * Badges de urgência em vermelho e barra de disponibilidade.
* **Comprovação Visual:**
  <!-- Inserir print do DevTools mostrando as classes CSS aplicadas ao bloco -->
  ![Evidência 5 - CSS Aplicado ao Bloco](docs/images/05-css-styling.png)

---

### [x] 6. README explica a estrutura de pastas criada
* **Validação:** A Seção 2 deste documento detalha integralmente cada diretório e arquivo criado (`Block`, `ViewModel`, `etc`, `layout`, `templates`, `web/css/source`) e suas respectivas responsabilidades no ciclo de vida do Magento.
* **Comprovação Visual:**
  <!-- Inserir print desta seção do README documentando a árvore de arquivos -->
  ![Evidência 6 - README com Estrutura de Pastas](docs/images/06-readme-structure.png)


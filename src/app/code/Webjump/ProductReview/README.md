# Desafio **14.2 - Entidade própria, do banco ao repositório**.

---

## 1. Visão Geral

O módulo implementa uma entidade customizada para avaliações de produtos (**Product Reviews**), contemplando desde a camada de banco de dados com **Declarative Schema** e **Whitelist**, até a camada de serviço com **Service Contracts**, **Model**, **ResourceModel**, **Collection**, **Repository** (com suporte completo a `SearchCriteria`) e **Data Patch** de carga inicial.

---

## 2. Decisão Arquitetural: Por que Tabela Própria (*Flat Table*) e não EAV?

Um dos pontos mais críticos na modelagem de software no Magento é a escolha entre uma entidade **EAV (Entity-Attribute-Value)** e uma **Tabela Própria (Flat Table)**.

Para a entidade de avaliações de produtos (`webjump_product_review`), a escolha de **Tabela Própria** é a mais indicada pelas seguintes razões:

### 2.1. Homogeneidade e Estabilidade do Schema
- **No EAV**: O modelo EAV foi projetado para entidades com centenas de atributos dinâmicos e esparsos, onde cada item pode ter atributos completamente diferentes (por exemplo, um produto do tipo *Notebook* possui processador e memória RAM, enquanto uma *Camiseta* possui cor e tamanho).
- **Nas Avaliações**: A estrutura de uma avaliação é fixa e previsível. Toda avaliação contém: ID, ID do produto, autor, comentário, nota, status de aprovação e datas. Não há necessidade de o lojista criar atributos dinâmicos arbitrários por Store View em tempo de execução para cada avaliação.

### 2.2. Desempenho e Custo de Input/Output
- **No EAV**: Para carregar uma única entidade com 5 atributos, o banco precisa consultar a tabela principal e realizar múltiplos `LEFT JOIN` nas tabelas de tipos primitivos (`_varchar`, `_text`, `_int`, `_datetime`), ou executar queries adicionais para pivotar os dados.
- **Na Tabela Própria**: Todas as colunas residem na mesma linha de uma única tabela. Uma consulta de listagem realiza um único `SELECT` sem nenhum join desnecessário, reduzindo drasticamente o consumo de memória, CPU do banco e tempo de resposta.

### 2.3. Eficiência em Filtros, Ordenação e Paginação
- As listagens de avaliações na PDP e no painel admin exigem frequentemente filtros como `product_id = X AND is_approved = 1`, ordenados por `created_at DESC`.
- Em uma tabela própria, podemos criar índices B-Tree diretos ou compostos (`product_id`, `is_approved`), permitindo que o MySQL execute index seeks em tempo constante $O(\log n)$.
- No EAV, filtrar e ordenar por múltiplos atributos exige joins cruzados complexos, o que em tabelas com milhares de avaliações causa problemas severos de escalabilidade.

### 2.4. Integridade Referencial Nativa (Foreign Keys)
- Em tabelas próprias, podemos declarar *foreign keys* nativas de banco de dados com `ON DELETE CASCADE` referenciando `catalog_product_entity.entity_id`. Se um produto for excluído, o próprio MySQL limpa as avaliações órfãs de forma atômica e consistente.
- No EAV, o relacionamento é mantido a nível de aplicação pelo ORM do Magento, aumentando a complexidade e o risco de dados órfãos.

---

## 3. Estrutura de Arquivos e Componentes

```text
app/code/Webjump/ProductReview/
├── Api/
│   ├── Data/
│   │   ├── ReviewInterface.php               # Contrato da entidade de dados (getters/setters)
│   │   └── ReviewSearchResultsInterface.php  # Contrato para resultados de busca paginados
│   └── ReviewRepositoryInterface.php         # Contrato do repositório (save, getById, delete, getList)
├── Model/
│   ├── ResourceModel/
│   │   ├── Review/
│   │   │   └── Collection.php                # Coleção de banco da entidade
│   │   └── Review.php                        # Resource Model responsável pelas queries SQL
│   ├── Review.php                            # Model de negócio implementando ReviewInterface
│   ├── ReviewRepository.php                  # Implementação do repositório usando CollectionProcessor
│   └── ReviewSearchResults.php               # Implementação do SearchResults
├── Setup/
│   └── Patch/
│       └── Data/
│           └── CreateSampleReviews.php       # Data Patch que insere 5 avaliações iniciais
├── etc/
│   ├── db_schema.xml                         # Declarative Schema da tabela webjump_product_review
│   ├── db_schema_whitelist.json              # Whitelist gerada para validação de integridade
│   ├── di.xml                                # Preferences para injeção de dependência dos Service Contracts
│   └── module.xml                            # Definição e dependências do módulo
├── registration.php                          # Registro do componente no Magento
└── README.md                                 # Documentação técnica e justificativas
```

---

## 4. Métodos do Repositório (`ReviewRepositoryInterface`)

- `save(ReviewInterface $review): ReviewInterface`: Persiste a avaliação (insert ou update) e trata exceções com `CouldNotSaveException`.
- `getById(int $reviewId): ReviewInterface`: Carrega a avaliação pelo ID primário ou lança `NoSuchEntityException`.
- `getList(SearchCriteriaInterface $searchCriteria): ReviewSearchResultsInterface`: Processa filtros, limites (paginação) e ordenações utilizando o `CollectionProcessorInterface` do framework.
- `delete(ReviewInterface $review): bool`: Remove a entidade ou lança `CouldNotDeleteException`.
- `deleteById(int $reviewId): bool`: Carrega a entidade por ID e executa a deleção.

---

## 5. Como Executar e Validar

### 5.1. Instalação e Atualização de Schema
```bash
bin/magento module:enable Webjump_ProductReview
bin/magento setup:upgrade
bin/magento setup:di:compile
```

### 5.2. Verificação no Banco de Dados
A tabela é gerada automaticamente:
```bash
bin/mysql -e "DESCRIBE webjump_product_review;"
```

Os 5 registros inseridos pelo Data Patch podem ser visualizados com:
```bash
bin/mysql -e "SELECT review_id, product_id, author_name, rating, is_approved, created_at FROM webjump_product_review;"
```

## 6. Evidências de sucesso

Esta seção busca comprovar através de prints o atendimento de todos os critérios de aceite do desafio **14.2 - Entidade própria, do banco ao repositório**.

### 6.1. Critério 1: A tabela é criada sozinha ao rodar `setup:upgrade`

#### Objetivo
Comprovar que a tabela `webjump_product_review` foi definida via Declarative Schema e criada de forma autônoma pelo instalador do Magento.

#### Prints Recomendados

#### Print 1.1 — Código (IDE)
- **Arquivo:** `src/app/code/Webjump/ProductReview/etc/db_schema.xml`
- **O que mostrar/destacar:**
  - A tag `<table name="webjump_product_review" ...>`
  - As colunas mínimas exigidas: `review_id`, `product_id`, `author_name`, `comment`, `rating`, `is_approved`, `created_at` e `updated_at`.
  - A constraint de chave estrangeira com `referenceTable="catalog_product_entity"` e `onDelete="CASCADE"`.

#### Print 1.2 — Execução no Terminal (CLI)
- **Comando:**
  ```bash
  bin/magento setup:upgrade
  ```
- **O que mostrar/destacar:** A saída do comando passando pelo módulo `Webjump_ProductReview` e atualizando o schema sem erros.

#### Print 1.3 — Banco / Interface Visual (phpMyAdmin)
- **Onde acessar:** `http://localhost:8080` (banco `magento`)
- **Tela:** Selecionar a tabela `webjump_product_review` e clicar na aba **Estrutura** (ou rodar `DESCRIBE webjump_product_review;` no terminal).
- **O que mostrar/destacar:** Todas as 8 colunas criadas, tipos de dados, chave primária (`review_id` com auto_increment) e índices.

---

## 2. Critério 2: A whitelist foi gerada e versionada

### Objetivo
Comprovar que o arquivo `db_schema_whitelist.json` foi gerado via CLI do Magento e faz parte do controle de versão Git.

### Prints Recomendados

#### Print 2.1 — Código (IDE)
- **Arquivo:** `src/app/code/Webjump/ProductReview/etc/db_schema_whitelist.json`
- **O que mostrar/destacar:** O JSON contendo o bloco `"webjump_product_review"`, com o dicionário de todas as colunas mapeadas com `: true`, os índices e a constraint `WEBJUMP_PRD_REVIEW_PRD_ID_CAT_PRD_ENTT_ENTT_ID`.

#### Print 2.2 — Controle de Versão no Terminal (Git)
- **Comando:**
  ```bash
  git status
  ```
- **O que mostrar/destacar:** O arquivo `src/app/code/Webjump/ProductReview/etc/db_schema_whitelist.json` presente e rastreado no repositório Git, pronto ou já adicionado à submissão.

---

## 3. Critério 3: Existe interface de repositório em `Api/`, e a implementação está ligada por *preference*

### Objetivo
Comprovar a existência dos Service Contracts e a correta amarração de injeção de dependência via `di.xml`.

### Prints Recomendados

#### Print 3.1 — Interface em `Api/` (IDE)
- **Arquivo:** `src/app/code/Webjump/ProductReview/Api/ReviewRepositoryInterface.php`
- **O que mostrar/destacar:** O namespace `Webjump\ProductReview\Api`, a declaração da interface `ReviewRepositoryInterface` e as assinaturas dos métodos públicos.

#### Print 3.2 — Preference no `di.xml` (IDE)
- **Arquivo:** `src/app/code/Webjump/ProductReview/etc/di.xml`
- **O que mostrar/destacar:** O nó:
  ```xml
  <preference for="Webjump\ProductReview\Api\ReviewRepositoryInterface"
              type="Webjump\ProductReview\Model\ReviewRepository"/>
  ```
  *(pode incluir também as preferências de `ReviewInterface` e `ReviewSearchResultsInterface`)*.

---

## 4. Critério 4: O repositório tem `save`, `getById`, `delete` e `getList`

### Objetivo
Comprovar que a classe concreta do repositório implementa todas as operações CRUD e de busca.

### Prints Recomendados

#### Print 4.1 — Métodos do Repositório no Código (IDE)
- **Arquivo:** `src/app/code/Webjump/ProductReview/Model/ReviewRepository.php`
- **O que mostrar/destacar:**
  - Método `save(ReviewInterface $review)`
  - Método `getById(int $reviewId)`
  - Método `getList(SearchCriteriaInterface $searchCriteria)`
  - Método `delete(ReviewInterface $review)` e `deleteById(int $reviewId)`

#### Print 4.2 — Validação no Frontend / Navegador
- **Onde acessar:** `https://magento.test/product_review/test`
- **O que mostrar/destacar:**
  - Seção **1. Teste: getById(1)** com o badge verde `✔ Avaliação recuperada com sucesso!`, exibindo autor, nota e comentário.
  - Seção **3. Teste Dinâmico: save() e deleteById()** exibindo os checks de `save()` executado, `deleteById()` executado e `NoSuchEntityException confirmada` após a deleção.

---

## 5. Critério 5: O `getList` aceita `SearchCriteria` com filtro e limite

### Objetivo
Comprovar que o repositório filtra e pagina os resultados corretamente através do `SearchCriteria` e `CollectionProcessor`.

### Prints Recomendados

#### Print 5.1 — Código (IDE)
- **Arquivo:** `src/app/code/Webjump/ProductReview/Model/ReviewRepository.php` (método `getList`)
- **O que mostrar/destacar:** A linha `$this->collectionProcessor->process($searchCriteria, $collection);` aplicando os filtros e limites na coleção antes de retornar o `SearchResults`.

#### Print 5.2 — Validação Visual no Frontend (Navegador)
- **Onde acessar:** `https://magento.test/product_review/test`
- **O que mostrar/destacar:**
  - Seção **2. Teste: getList() com SearchCriteria (Filtro: is_approved = 1, Limite: 2)**.
  - O texto: *"Total no banco: 4 | Itens retornados: 2"*.
  - A tabela renderizando exatamente 2 avaliações aprovadas (respeitando o filtro e o limite configurados via `SearchCriteria`).

---

## 6. Critério 6: As 5 avaliações de exemplo são inseridas por patch

### Objetivo
Comprovar a execução do Data Patch e a presença dos 5 registros reais associados a produtos do catálogo no banco.

### Prints Recomendados

#### Print 6.1 — Código do Patch (IDE)
- **Arquivo:** `src/app/code/Webjump/ProductReview/Setup/Patch/Data/CreateSampleReviews.php`
- **O que mostrar/destacar:** A implementação de `DataPatchInterface`, o array com os 5 autores/comentários e o loop chamando `$this->reviewRepository->save($review)`.

#### Print 6.2 — Banco de Dados / phpMyAdmin
- **Onde acessar:** `http://localhost:8080` (tabela `webjump_product_review`, aba **Visualizar**)
- **O que mostrar/destacar:** As 5 linhas salvas com IDs de 1 a 5, com autores:
  1. *Mariana Silva* (Produto ID 2041, Nota 5, Aprovado 1)
  2. *Carlos Eduardo* (Produto ID 47, Nota 4, Aprovado 1)
  3. *Beatriz Souza* (Produto ID 48, Nota 5, Aprovado 1)
  4. *Rodrigo Mendes* (Produto ID 49, Nota 3, Aprovado 0)
  5. *Juliana Ferreira* (Produto ID 50, Nota 5, Aprovado 1)

#### Print 6.3 — Registro na tabela `patch_list` (Terminal ou phpMyAdmin)
- **Comando:**
  ```bash
  bin/mysql -e "SELECT patch_id, patch_name FROM patch_list WHERE patch_name LIKE '%CreateSampleReviews%';"
  ```
- **O que mostrar/destacar:** A linha comprovando que o patch foi registrado com sucesso (`patch_id = 205`).

#### Print 6.4 — Admin Magento (`Catalog > Products`)
- **Onde acessar:** `https://magento.test/admin/catalog/product/`
- **O que mostrar/destacar:** A grade de produtos filtrando por ID (ex: 2041, 47, 48), mostrando que os produtos aos quais as avaliações foram associadas existem e estão ativos no catálogo da loja.

---


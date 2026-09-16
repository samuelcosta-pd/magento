# Módulo Webjump_ProductReview

Módulo desenvolvido para o desafio **14.2 - Entidade própria, do banco ao repositório** no Magento 2.4.8-p1.

---

## 1. Visão Geral

O módulo implementa uma entidade customizada para avaliações de produtos (**Product Reviews**), contemplando desde a camada de banco de dados com **Declarative Schema** e **Whitelist**, até a camada de serviço com **Service Contracts**, **Model**, **ResourceModel**, **Collection**, **Repository** (com suporte completo a `SearchCriteria`) e **Data Patch** de carga inicial.

---

## 2. Decisão Arquitetural: Por que Tabela Própria (*Flat Table*) e não EAV?

Um dos pontos mais críticos na modelagem de software em Magento 2 é a escolha entre uma entidade **EAV (Entity-Attribute-Value)** e uma **Tabela Própria (Flat Table)**.

Para a entidade de avaliações de produtos (`webjump_product_review`), a escolha de **Tabela Própria** é a mais indicada pelas seguintes razões:

### 2.1. Homogeneidade e Estabilidade do Schema
- **No EAV**: O modelo EAV foi projetado para entidades com centenas de atributos dinâmicos e esparsos, onde cada item pode ter atributos completamente diferentes (por exemplo, um produto do tipo *Notebook* possui processador e memória RAM, enquanto uma *Camiseta* possui cor e tamanho).
- **Nas Avaliações**: A estrutura de uma avaliação é fixa e previsível. Toda avaliação contém: ID, ID do produto, autor, comentário, nota, status de aprovação e datas. Não há necessidade de o lojista criar atributos dinâmicos arbitrários por Store View em tempo de execução para cada avaliação.

### 2.2. Desempenho e Custo de I/O
- **No EAV**: Para carregar uma única entidade com 5 atributos, o banco precisa consultar a tabela principal e realizar múltiplos `LEFT JOIN` nas tabelas de tipos primitivos (`_varchar`, `_text`, `_int`, `_datetime`), ou executar queries adicionais para pivotar os dados.
- **Na Tabela Própria**: Todas as colunas residem na mesma linha de uma única tabela. Uma consulta de listagem realiza um único `SELECT` sem nenhum join desnecessário, reduzindo drasticamente o consumo de memória, CPU do banco e tempo de resposta (TTFB).

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

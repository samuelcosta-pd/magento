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

#### Evidência 1.1 - Código (IDE)
- **Arquivo:** `src/app/code/Webjump/ProductReview/etc/db_schema.xml`
- **Mostrando:**
  - A tag `<table name="webjump_product_review" ...>`
  - As colunas mínimas exigidas: `review_id`, `product_id`, `author_name`, `comment`, `rating`, `is_approved`, `created_at` e `updated_at`.
  - A constraint de chave estrangeira com `referenceTable="catalog_product_entity"` e `onDelete="CASCADE"`.
 > <img width="1454" height="779" alt="image" src="https://github.com/user-attachments/assets/859e6c17-07a6-4121-a210-4db7ad110958" />


#### Evidência 1.2 - Execução no Terminal (CLI)
- **Comando:**
  ```bash
  bin/magento setup:upgrade
  ```
- **Mostrando:** A saída do comando passando pelo módulo `Webjump_ProductReview` e atualizando o schema sem erros.
> <img width="1473" height="923" alt="image" src="https://github.com/user-attachments/assets/e5c801d9-8f68-4283-a028-d637e50aaacb" />


#### Evidência 1.3 - Banco
- Rodar `DESCRIBE webjump_product_review;` no terminal.
- **Mostrando:** Todas as 8 colunas criadas, tipos de dados, chave primária (`review_id` com auto_increment) e índices.
> <img width="1469" height="590" alt="image" src="https://github.com/user-attachments/assets/9c72027e-5547-4bef-ac25-704533a9a13a" />


---

## 2. Critério 2: A whitelist foi gerada e versionada

### Objetivo
Comprovar que o arquivo `db_schema_whitelist.json` foi gerado via CLI do Magento e faz parte do controle de versão Git.

#### Evidência 2.1 - Código (IDE)
- **Arquivo:** `src/app/code/Webjump/ProductReview/etc/db_schema_whitelist.json`
- **Mostrando:** O JSON contendo o bloco `"webjump_product_review"`, com o dicionário de todas as colunas mapeadas com `: true`, os índices e a constraint `WEBJUMP_PRD_REVIEW_PRD_ID_CAT_PRD_ENTT_ENTT_ID`.
> <img width="1433" height="512" alt="image" src="https://github.com/user-attachments/assets/7e1ae7f3-7e50-4c36-bccf-348c118c73d3" />


#### Evidência 2.2 - Controle de Versão no Terminal (Git)
- **Comando:**
  ```bash
  git status
  ```
- **Mostrando:** O arquivo `src/app/code/Webjump/ProductReview/etc/db_schema_whitelist.json` presente e rastreado no repositório Git, pronto ou já adicionado à submissão.
> <img width="1466" height="225" alt="image" src="https://github.com/user-attachments/assets/ccfcf010-239a-40c3-a283-c0fd467088d6" />

---

## 3. Critério 3: Existe interface de repositório em `Api/`, e a implementação está ligada por *preference*

### Objetivo
Comprovar a existência dos Service Contracts e a correta amarração de injeção de dependência via `di.xml`.

#### Evidência 3.1 - Interface em `Api/` (IDE)
- **Arquivo:** `src/app/code/Webjump/ProductReview/Api/ReviewRepositoryInterface.php`
- **Mostrando:** O namespace `Webjump\ProductReview\Api`, a declaração da interface `ReviewRepositoryInterface` e as assinaturas dos métodos públicos.
> <img width="1776" height="389" alt="image" src="https://github.com/user-attachments/assets/2347973e-94f9-4f00-a2c9-9319e0f4df96" />
> <img width="1776" height="389" alt="image" src="https://github.com/user-attachments/assets/75d835f4-92f9-4e13-95cc-4ff4eea9f921" />
> <img width="1789" height="1094" alt="image" src="https://github.com/user-attachments/assets/2999dd9d-c5c2-4407-ae88-16b20182ee4d" />



#### Evidência 3.2 - Preference no `di.xml` (IDE)
- **Arquivo:** `src/app/code/Webjump/ProductReview/etc/di.xml`
> <img width="1805" height="503" alt="image" src="https://github.com/user-attachments/assets/9a8fa8b2-0e79-46dc-be13-ecc042875dff" />


---

## 4. Critério 4: O repositório tem `save`, `getById`, `delete` e `getList`

### Objetivo
Comprovar que a classe concreta do repositório implementa todas as operações CRUD e de busca.

#### Evidência 4.1 - Métodos do Repositório no Código (IDE)
- **Arquivo:** `src/app/code/Webjump/ProductReview/Model/ReviewRepository.php`
- **Mostrando:** Os métodos `save`, `getById`, `delete`, `getList` e `deleteById`.
> Método `save(ReviewInterface $review)`
> <img width="1454" height="343" alt="image" src="https://github.com/user-attachments/assets/ef2c00c2-d749-4824-9fe2-82653b301b61" />

> Método `getById(int $reviewId)`
> <img width="1455" height="329" alt="image" src="https://github.com/user-attachments/assets/8642fc5d-cf97-4698-bfc9-90101564c0d5" />

> Método `getList(SearchCriteriaInterface $searchCriteria)`
> <img width="1423" height="341" alt="image" src="https://github.com/user-attachments/assets/ac386ca6-6e9a-4292-bfa7-c22efcd4ff68" />

> Método `delete(ReviewInterface $review)` e `deleteById(int $reviewId)`
> <img width="1441" height="619" alt="image" src="https://github.com/user-attachments/assets/e60880f3-e38b-4f35-9af1-847945133f79" />

---

## 5. Critério 5: O `getList` aceita `SearchCriteria` com filtro e limite

### Objetivo
Comprovar que o repositório filtra e pagina os resultados corretamente através do `SearchCriteria` e `CollectionProcessor`.

#### Evidência 5.1 - Código (IDE)
- **Arquivo:** `src/app/code/Webjump/ProductReview/Model/ReviewRepository.php` (método `getList`)
- **Mostrando:** A linha `$this->collectionProcessor->process($searchCriteria, $collection);` aplicando os filtros e limites na coleção antes de retornar o `SearchResults`.
> <img width="1423" height="341" alt="image" src="https://github.com/user-attachments/assets/5344129b-beb2-42a7-9561-980591f1b1a1" />

---

## 6. Critério 6: As 5 avaliações de exemplo são inseridas por patch

### Objetivo
Comprovar a execução do Data Patch e a presença dos 5 registros reais associados a produtos do catálogo no banco.

#### Evidência 6.1 - Código do Patch (IDE)
- **Arquivo:** `src/app/code/Webjump/ProductReview/Setup/Patch/Data/CreateSampleReviews.php`
- **Mostrando:** A implementação de `DataPatchInterface`, o array com os 5 autores e o loop chamando `$this->reviewRepository->save($review)`.
> <img width="1454" height="409" alt="image" src="https://github.com/user-attachments/assets/9efed37b-34d9-4762-ab3f-d9011a33a8fb" />
> <img width="1451" height="1042" alt="image" src="https://github.com/user-attachments/assets/385ae78b-c52e-4052-884d-290594cc05e1" />


#### Evidência 6.2 - Banco de Dados / phpMyAdmin
- **Onde acessar:** `http://localhost:8080` (tabela `webjump_product_review`, aba **Visualizar**)
- **Mostrando:** As 5 linhas salvas com IDs de 1 a 5, com autores:
> <img width="1454" height="567" alt="image" src="https://github.com/user-attachments/assets/1285d95b-e6d0-4983-a78f-89af0f847f37" />


#### Evidência 6.3 - Registro na tabela `patch_list` (Terminal)
- **Comando:**
  ```bash
  bin/mysql -e "SELECT patch_id, patch_name FROM patch_list WHERE patch_name LIKE '%CreateSampleReviews%';"
  ```
- **Mostrando:** A linha comprovando que o patch foi registrado com sucesso (`patch_id = 205`).
> <img width="1445" height="221" alt="image" src="https://github.com/user-attachments/assets/f1bc9e48-8ad7-4fd9-9789-b92db1a788dd" />

---

## 7. Desafio 15.1: Grid de Administração Completo

### 7.1. Componentes Desenvolvidos
- **Rota e Controller Admin:** Rota `webjump_productreview` declarada em `etc/adminhtml/routes.xml`. Controller `Controller/Adminhtml/Review/Index.php` com constante de proteção `public const ADMIN_RESOURCE = 'Webjump_ProductReview::reviews'`.
- **Árvore ACL Granular:** Declarada em `etc/acl.xml`, com o recurso `Webjump_ProductReview::reviews` e separação estrita para `Webjump_ProductReview::reviews_export` (exportação) e `Webjump_ProductReview::reviews_actions` (ações em massa).
- **Menu do Admin:** Menu pai `Webjump` e item `Avaliações de Produtos` declarados em `etc/adminhtml/menu.xml`.
- **Grid com UI Component (`webjump_productreview_listing.xml`):**
  - DataProvider vinculado à Grid Collection via `virtualType` de `SearchResult` em `etc/di.xml`.
  - Filtros: texto (`author_name`, `comment`), faixa numérica (`review_id`, `product_id`, `rating`), data (`created_at`, `updated_at`) e status de aprovação (`is_approved`) com `ApprovalStatus` OptionSource.
  - Ordenação e paginação nativas.
  - Ações em massa:
    - **Aprovar** (`massApprove`): altera status para Aprovado via `ReviewRepositoryInterface`.
    - **Reprovar / Pendente** (`massDisapprove`): altera status para Pendente via `ReviewRepositoryInterface`.
    - **Excluir** (`massDelete`): remove os registros via `ReviewRepositoryInterface` com modal de confirmação destrutiva.

---

### 7.2. Evidências de Sucesso

Documentação comprobatória completa também disponível em [`README-15-1.md`](file:///home/samuel/Sites/magento/README-15-1.md).

#### Critério 1: Menu, Rota e Controller com `ADMIN_RESOURCE`
- **Print 1.1 — No Painel Admin (Menu Webjump e Acesso ao Grid):**
> <!-- Cole aqui o Print 1.1 -->

- **Print 1.2 — No Código / IDE (Controller e `routes.xml`):**
> <!-- Cole aqui o Print 1.2 -->

#### Critério 2: Grid Listando Dados da Collection, com Ordenação e Paginação
- **Print 2.1 — No Painel Admin (Grid com Todas as Avaliações e Colunas):**
> <!-- Cole aqui o Print 2.1 -->

- **Print 2.2 — No Painel Admin (Ordenação e Paginação):**
> <!-- Cole aqui o Print 2.2 -->

- **Print 2.3 — No Código / IDE (Configuração do DataProvider e VirtualType):**
> <!-- Cole aqui o Print 2.3 -->

#### Critério 3: Filtros por Texto, Faixa Numérica, Data e Status Funcionando
- **Print 3.1 — No Painel Admin (Filtro por Texto):**
> <!-- Cole aqui o Print 3.1 -->

- **Print 3.2 — No Painel Admin (Filtro por Faixa Numérica):**
> <!-- Cole aqui o Print 3.2 -->

- **Print 3.3 — No Painel Admin (Filtro por Status de Seleção):**
> <!-- Cole aqui o Print 3.3 -->

- **Print 3.4 — No Código / IDE (Declaração dos Filtros no XML):**
> <!-- Cole aqui o Print 3.4 -->

#### Critério 4: Ações em Massa (Mass Actions) com Confirmação Destrutiva
- **Print 4.1 — No Painel Admin (Aprovação em Massa):**
> <!-- Cole aqui o Print 4.1 -->

- **Print 4.2 — No Painel Admin (Modal de Confirmação na Exclusão):**
> <!-- Cole aqui o Print 4.2 -->

- **Print 4.3 — No Código / IDE (Configuração do MassAction com `<confirm>` e Controller):**
> <!-- Cole aqui o Print 4.3 -->

#### Critério 5: Bloqueio de Acesso com Perfil Restrito (ACL)
- **Print 5.1 — No Painel Admin (Menu Oculto para Perfil Restrito):**
> <!-- Cole aqui o Print 5.1 -->

- **Print 5.2 — No Painel Admin (Tentativa de Bypass via URL Bloqueada):**
> <!-- Cole aqui o Print 5.2 -->

- **Print 5.3 — No Código / IDE (Interceptação e Proteção no Controller):**
> <!-- Cole aqui o Print 5.3 -->

#### Critério 6: ACL com Permissão Separada para Exportação
- **Print 6.1 — No Painel Admin (Árvore de Permissões em User Roles):**
> <!-- Cole aqui o Print 6.1 -->

- **Print 6.2 — No Código / IDE (Árvore de Recursos no `acl.xml`):**
> <!-- Cole aqui o Print 6.2 -->



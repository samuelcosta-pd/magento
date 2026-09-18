# Desafio 14.2: Entidade Própria, do Banco ao Repositório

Documentação e evidências da implementação do desafio **14.2 - Entidade própria, do banco ao repositório** no Magento 2.4.8-p1.

O módulo completo foi desenvolvido em [Webjump_ProductReview](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductReview).

---

## 1. Visão Geral do Desafio

### Requisito
- Ter uma tabela própria para avaliações de produtos, com uma forma limpa e reutilizável de ler e gravar.
- Criar a tabela com declarative schema (mínimo: id, produto, autor, comentário, nota, aprovado, data).
- Gerar a whitelist e versioná-la.
- Criar Model, ResourceModel e Collection.
- Criar a interface do repositório em `Api/`, implementá-la e registrar via `preference` no `di.xml`.
- Criar um data patch que insira 5 avaliações de exemplo.

---

## 2. Critérios de Aceite e Validações

- [x] **A tabela é criada sozinha ao rodar `setup:upgrade`**:
  Declarada em `etc/db_schema.xml` com a tabela `webjump_product_review` e criada automaticamente no banco pelo installer declarativo do Magento.
- [x] **A whitelist foi gerada e versionada**:
  Arquivo `etc/db_schema_whitelist.json` gerado pelo comando `setup:db-declaration:generate-whitelist` e devidamente versionado no Git.
- [x] **Existe interface de repositório em `Api/`, e a implementação está ligada por *preference***:
  Interface `Webjump\ProductReview\Api\ReviewRepositoryInterface` criada e vinculada a `Webjump\ProductReview\Model\ReviewRepository` no `etc/di.xml`.
- [x] **O repositório tem `save`, `getById`, `delete` e `getList`**:
  Todos os métodos implementados seguindo estritamente as convenções de Service Contracts e exceções tipadas do Magento (`CouldNotSaveException`, `NoSuchEntityException`, `CouldNotDeleteException`).
- [x] **O `getList` aceita `SearchCriteria` com filtro e limite**:
  Processado utilizando `Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface`, permitindo filtrar por campos (ex: `is_approved`, `product_id`, `rating`) e paginar resultados (`pageSize`, `currentPage`).
- [x] **As 5 avaliações de exemplo são inseridas por patch**:
  Implementado no Data Patch `Webjump\ProductReview\Setup\Patch\Data\CreateSampleReviews`, associando avaliações aos produtos existentes no catálogo através do próprio repositório/service contract.
- [x] **README explica por que tabela própria e não EAV neste caso**:
  Documentado detalhadamente no [README do Módulo](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductReview/README.md) e na seção abaixo.

---

## 3. Justificativa Arquitetural: Tabela Própria (*Flat Table*) vs EAV

### 3.1. Estrutura Homogênea e Fixa
O modelo EAV (*Entity-Attribute-Value*) foi desenvolvido para entidades que possuem centenas de atributos dinâmicos e altamente heterogêneos (como o catálogo de produtos, onde eletrônicos têm voltagem/processador e roupas têm cor/tamanho). Já uma avaliação de produto possui schema estático e universal: todo review possui autor, produto, nota, comentário, status e data. Não há variabilidade de atributos entre instâncias.

### 3.2. Performance de Banco e Redução de I/O
Em um modelo EAV, ler uma avaliação exige realizar múltiplos `LEFT JOIN` nas tabelas `_varchar`, `_text`, `_int` e `_datetime` ou executar consultas adicionais. Em uma tabela própria (*flat*), todos os dados residem em uma única linha. Um `SELECT` simples traz o registro completo sem overhead de join, economizando CPU e memória do banco de dados.

### 3.3. Índices e Consultas Complexas
Consultas em avaliações são muito frequentes na PDP (exibir avaliações aprovadas de um produto) e no painel admin (listar pendentes para moderação). Com a tabela própria, índices compostos como `(product_id, is_approved)` operam em tempo $O(\log n)$. No EAV, consultas com filtros compostos em múltiplos atributos tornam-se extremamente lentas em bases com alto volume de registros.

### 3.4. Integridade Referencial (Foreign Keys)
A tabela própria permite a criação de constraints de chave estrangeira a nível de banco de dados (`ON DELETE CASCADE` para `catalog_product_entity.entity_id`). Se um produto for removido, suas avaliações são excluídas atomicamente pelo MySQL, evitando registros órfãos. No EAV, a integridade depende inteiramente do código PHP da aplicação.

---

## 4. Estrutura dos Arquivos Desenvolvidos

1. `src/app/code/Webjump/ProductReview/registration.php`: Registro do componente.
2. `src/app/code/Webjump/ProductReview/etc/module.xml`: Declaração do módulo com dependência em `Magento_Catalog`.
3. `src/app/code/Webjump/ProductReview/etc/db_schema.xml`: Declarative Schema para `webjump_product_review`.
4. `src/app/code/Webjump/ProductReview/etc/db_schema_whitelist.json`: Whitelist de integridade do banco.
5. `src/app/code/Webjump/ProductReview/etc/di.xml`: Preferences de injeção de dependência.
6. `src/app/code/Webjump/ProductReview/Api/Data/ReviewInterface.php`: Service contract da entidade.
7. `src/app/code/Webjump/ProductReview/Api/Data/ReviewSearchResultsInterface.php`: Contrato para resultados de busca.
8. `src/app/code/Webjump/ProductReview/Api/ReviewRepositoryInterface.php`: Contrato do repositório.
9. `src/app/code/Webjump/ProductReview/Model/Review.php`: Model de negócio com identity tag.
10. `src/app/code/Webjump/ProductReview/Model/ResourceModel/Review.php`: Resource Model.
11. `src/app/code/Webjump/ProductReview/Model/ResourceModel/Review/Collection.php`: Coleção de reviews.
12. `src/app/code/Webjump/ProductReview/Model/ReviewSearchResults.php`: Implementação concreta de search results.
13. `src/app/code/Webjump/ProductReview/Model/ReviewRepository.php`: Implementação do repositório com suporte a `CollectionProcessor`.
14. `src/app/code/Webjump/ProductReview/Setup/Patch/Data/CreateSampleReviews.php`: Data Patch semeando 5 avaliações.
15. `src/app/code/Webjump/ProductReview/README.md`: Documentação técnica completa do módulo.

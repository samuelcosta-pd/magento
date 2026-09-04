# Explorando a loja e o catálogo

## Descrição

Este projeto consiste na realização de pequenas atividades práticas no Magento Open Source, com o objetivo de explorar o painel administrativo, compreender o funcionamento do catálogo e observar o efeito das alterações realizadas diretamente na loja.

As atividades foram executadas utilizando o **Admin do Magento** e a **linha de comando (CLI)**. Durante a execução, foram criados conteúdos reais no catálogo e no CMS, além da alteração de configurações da loja para validar seus efeitos no frontend.

Também foram estudados conceitos fundamentais da estrutura do Magento, incluindo atributos, conjuntos de atributos, categorias, tipos de produtos e a hierarquia de escopos entre **Website**, **Store** e **Store View**.

## Atividades realizadas

### Criação de categoria e produto simples

Foi criada a subcategoria **Moda Masculina**, vinculada à **Default Category**, ativada e incluída no menu da loja.

Também foi criado o produto simples **Camiseta Básica Algodão**, com as seguintes informações:

* **SKU:** `CAM-BAS-001`
* **Preço:** `79.90`
* **Quantidade em estoque:** `100`
* **Status do estoque:** `In Stock`
* **Visibilidade:** `Catalog, Search`
* Associado à categoria **Moda Masculina**

Após a criação, foi realizada a validação para confirmar a exibição do produto na loja.

Quando necessário, o índice do Magento pode ser atualizado utilizando o comando:

```bash
bin/magento indexer:reindex
```

O processo de reindexação é diferente da limpeza de cache. Enquanto comandos como `cache:clean` e `cache:flush` atuam nos dados temporários armazenados pelo Magento, o `indexer:reindex` atualiza os índices utilizados para disponibilizar corretamente informações do catálogo e outros dados no banco de dados.

## CMS Page e CMS Block

Foi criado um **CMS Block** com o identificador:

```text
banner-boas-vindas
```

O bloco contém um conteúdo HTML com uma mensagem de boas-vindas e uma oferta utilizando cupom.

Também foi criada uma **CMS Page** com a seguinte chave de URL:

```text
pagina-promocional
```

O CMS Block foi inserido na página para que seu conteúdo fosse renderizado dinamicamente.

Para exibir o bloco, foi utilizada a seguinte diretiva/widget:

```text
{{widget type="Magento\Cms\Block\Widget\Block" template="widget/static_block/default.phtml" block_id="banner-boas-vindas"}}
```

Dessa forma, foi possível validar a criação de conteúdo pelo CMS e a exibição de um bloco em uma página da loja.

## Exploração de configurações

Também foram exploradas configurações disponíveis no Magento.

Foi configurado o **Persistent Shopping Cart**, utilizando as seguintes opções:

* **Enable Persistence:** `Yes`
* **Enable "Remember Me":** `Yes`
* **Clear Persistence on Sign Out:** `No`

Com essa configuração, foi possível observar diretamente o efeito da persistência na loja, especialmente por meio da opção Remember Me disponibilizada durante o login e da manutenção do carrinho após a perda da sessão.

Para validar o funcionamento, durante os testes foi excluído manualmente o cookie PHPSESSID através das ferramentas de inspeção do navegador, em Application > Cookies. Dessa forma, foi possível simular a perda da sessão do usuário.

Mesmo após a exclusão do PHPSESSID, o carrinho continuou contendo os produtos previamente selecionados e o usuário permaneceu com o login persistente. A loja passou a exibir a mensagem "Welcome [nome], Not You?", comprovando que a persistência estava funcionando corretamente.

## Conceitos estudados

### Attribute

Um **Attribute** é uma informação individual que descreve um produto.

Por exemplo, campos como:

* Cor
* Tamanho
* Preço

podem ser considerados atributos.

Eles representam as características que podem ser utilizadas para cadastrar e organizar as informações dos produtos.

### Attribute Set

O **Attribute Set** funciona como uma estrutura que agrupa atributos utilizados por determinado tipo de produto.

Por exemplo, um conjunto de atributos para **Roupas** pode possuir informações como tamanho, cor e material, enquanto um conjunto para **Eletrônicos** pode possuir informações específicas, como voltagem ou capacidade.

Dessa forma, cada tipo de produto pode possuir um formulário de cadastro mais adequado às suas características.

### Category

A **Category** representa a organização dos produtos no catálogo e na navegação da loja.

Ela funciona como uma prateleira de supermercado, uma forma de agrupar produtos relacionados.

Neste projeto, por exemplo, foi criada a categoria **Moda Masculina** para associar e organizar o produto **Camiseta Básica Algodão**.

### Tipos de produtos

Durante o estudo da estrutura do catálogo, foram explorados os principais tipos nativos de produtos do Magento:

* **Simple:** produto individual com uma configuração específica.
* **Configurable:** produto que possui variações, como tamanho ou cor.
* **Virtual:** produto que não possui entrega física.
* **Downloadable:** produto disponibilizado para download.
* **Grouped:** agrupamento de produtos simples.
* **Bundle:** produto formado por diferentes opções ou componentes selecionáveis.

## Website, Store e Store View

No Magento, **Website**, **Store** e **Store View** representam diferentes níveis de organização da loja.

### Website

O **Website** representa a estrutura comercial mais ampla da aplicação.

É possível entender o Website como uma unidade de negócio dentro do Magento. Configurações relacionadas à operação comercial podem estar associadas a esse nível, como preços, moedas e métodos de pagamento.

### Store

A **Store**, também chamada internamente de **Store Group**, representa uma organização do catálogo dentro de um Website.

Ela está relacionada principalmente à definição da **Root Category**, que serve como ponto inicial para a estrutura de categorias disponível naquela loja.

Dessa forma, uma Store pode organizar e disponibilizar um conjunto de categorias do catálogo.

### Store View

A **Store View** representa a camada de apresentação da loja.

É nesse nível que podem existir diferenças relacionadas, por exemplo, a:

* Idioma
* Traduções
* Banners
* Conteúdos
* Tema e apresentação

Eu entendo a relação entre os três níveis da seguinte forma: o **Website** representa a estrutura comercial principal como se fosse a raiz do negócio, a **Store** organiza o catálogo e categoriza, como se fosse uma organização de estoque, enquanto a **Store View** representa a forma como esse conteúdo será apresentado para os usuários. Um ponto interessante é que o global parece redundante a Website, mas posso pesquisar mais sobre.

## Problemas enfrentados e soluções

| Situação / Dúvida                                                 | Causa Identificada                                                                                                                                       | Solução Aplicada                                                                                                                                                                                                                 |
| ----------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Dúvida entre `cache:clean`, `cache:flush` e `indexer:reindex`** | Os comandos possuem responsabilidades diferentes no Magento.                                                                                             | Foi identificado que `cache:clean` e `cache:flush` atuam sobre o armazenamento temporário, enquanto `indexer:reindex` atualiza os índices utilizados pelo Magento.                                                               |
| **Carrinho continuava disponível após apagar o `PHPSESSID`**      | O Magento pode associar a cotação (`quote`) ao `customer_id` após o login, o que não representa necessariamente o funcionamento do carrinho persistente. | O teste foi realizado utilizando a opção `Remember Me`, permitindo a criação do cookie `persistent_shopping_cart` para validar corretamente a persistência.                                                                      |
| **Cookie de persistência era apagado junto com o logout**         | A configuração `Clear Persistence on Sign Out` estava ativada.                                                                                           | A opção foi alterada para `No`, evitando que o cookie de persistência fosse removido no logout.                                                                                                                                  |

## Critérios de aceite

* [x] Criação de uma nova categoria
* [x] Criação de um produto simples associado à categoria
* [x] Validação da exibição do produto na loja
* [x] Estudo do uso do comando `bin/magento indexer:reindex`
* [x] Criação de uma CMS Page
* [x] Criação de um CMS Block
* [x] Exibição do CMS Block em uma página
* [x] Alteração de configuração e validação do efeito na loja
* [x] README explicando Website, Store e Store View
* [x] Evidências das atividades realizadas

## Evidências de sucesso

### Criação da categoria Moda Masculina

> <img width="1997" height="763" alt="image" src="https://github.com/user-attachments/assets/9a8c1c34-cab0-4ef4-bccc-db707d6f0178" />

### Criação do produto Camiseta Básica Algodão

> <img width="1884" height="905" alt="image" src="https://github.com/user-attachments/assets/8a327240-3fc4-467c-ae0d-a113232b1c01" />

> <img width="1933" height="816" alt="image" src="https://github.com/user-attachments/assets/e38d1752-064b-48e9-b8ab-1f1619a981c3" />

### Produto exibido na loja

> <img width="2021" height="2027" alt="image" src="https://github.com/user-attachments/assets/55fad63e-3e2d-4a40-b962-78f4e378441e" />

### CMS Block banner-boas-vindas

> <img width="1155" height="1185" alt="image" src="https://github.com/user-attachments/assets/3a9a9ebd-886e-452f-93c6-5d5d1d62cf48" />

### CMS Página Promocional

> <img width="1456" height="1391" alt="image" src="https://github.com/user-attachments/assets/2aabe0c2-5c38-451b-ada8-cc7f01058f30" />

### CMS Block exibido na página

> <img width="1473" height="715" alt="image" src="https://github.com/user-attachments/assets/abf4fa3b-b063-497e-8543-cfc766b5bac5" />

> <img width="1506" height="656" alt="image" src="https://github.com/user-attachments/assets/e601112a-b1f4-4199-95d5-5a139818a4d8" />

> <img width="1470" height="1610" alt="image" src="https://github.com/user-attachments/assets/57755d7c-bfec-4a51-a64f-00bf3b0322d1" />

### Configuração Persistent Shopping Cart

> <img width="1000" height="1068" alt="image" src="https://github.com/user-attachments/assets/de301fa7-48ce-4385-94b3-1eb0e242bf66" />

### Efeito da configuração no site

> <img width="1498" height="1041" alt="image" src="https://github.com/user-attachments/assets/c798c85b-c3da-4cd3-91d1-ae1b61a1e84c" />

> <img width="1812" height="1299" alt="image" src="https://github.com/user-attachments/assets/73904e67-4db7-4032-9549-3315d7dbcc6f" />

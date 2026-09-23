# Desafio 15.2: Formulário, Configuração e Exportação

Documentação técnica e evidências de implementação do **Desafio 15.2** da Semana 15 (Sprint 7 — Admin, CRUD e Exportação) no módulo [`Webjump_ProductReview`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductReview).

---

## 1. Critérios de Aceite Atendidos

| Critério de Aceite | Status | Detalhamento da Implementação |
|---|:---:|---|
| **Criar e editar pelo admin funciona, com validação de campo obrigatório** | [x] Atendido | UI Component [`webjump_productreview_form.xml`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductReview/view/adminhtml/ui_component/webjump_productreview_form.xml) com validação de campos obrigatórios (`required-entry`), validação numérica (`validate-digits`, `validate-greater-than-zero`) e seleção de nota e status via OptionSource. |
| **O `Save` usa o repository e trata erro devolvendo mensagem ao usuário** | [x] Atendido | Action [`Save.php`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductReview/Controller/Adminhtml/Review/Save.php) utiliza exclusivamente [`ReviewRepositoryInterface`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductReview/Api/ReviewRepositoryInterface.php), valida os dados no backend, captura `LocalizedException` e `\Exception`, preserva os inputs digitados via `DataPersistorInterface` e envia feedback visual com `messageManager`. |
| **Existe seção em *Stores > Configuration*, com valores padrão funcionando** | [x] Atendido | Declarada em [`system.xml`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductReview/etc/adminhtml/system.xml) na aba **Webjump > Avaliações de Produtos** (`webjump_productreview`), com 3 campos configuráveis (`enabled`, `min_rating`, `require_approval`) e valores padrão registrados em [`config.xml`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductReview/etc/config.xml). Leitura tipada via classe de serviço [`Model/Config.php`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductReview/Model/Config.php). |
| **O módulo respeita a configuração (se desabilitado, não exibe na loja)** | [x] Atendido | Bloco e template [`reviews.phtml`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductReview/view/frontend/templates/product/view/reviews.phtml) injetados na PDP via [`catalog_product_view.xml`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductReview/view/frontend/layout/catalog_product_view.xml) com ViewModel [`ProductReviews.php`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductReview/ViewModel/ProductReviews.php). Se o módulo estiver desabilitado na configuração (`webjump_productreview/general/enabled = 0`), nada é renderizado na loja. |
| **A exportação em CSV e Excel XML funciona** | [x] Atendido | Componente `<exportButton>` integrado à barra de ferramentas do grid, disparando as rotas nativas de conversão `mui/export/gridToCsv` e `mui/export/gridToXml`. |
| **A exportação respeita os filtros aplicados no grid** | [x] Atendido | A exportação processa o `SearchResult` com os critérios de busca aplicados pelo usuário (ex: filtrar por Nota 5 exporta apenas avaliações com nota 5). |

---

## 2. Decisões Arquiteturais e Boas Práticas

### 2.1. CRUD Completo via Service Contracts
Seguindo o padrão de arquitetura preconizado pela Adobe/Magento, as actions administrativas não manipulam Models diretamente ou chamam métodos legados como `$model->save()` ou `$model->delete()`. Toda a persistência é orquestrada por [`ReviewRepositoryInterface`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductReview/Api/ReviewRepositoryInterface.php):
- **Criação e Atualização**: `$this->reviewRepository->save($review);`
- **Exclusão Individual**: `$this->reviewRepository->deleteById($reviewId);`
- **Consulta**: `$this->reviewRepository->getById($reviewId);`

### 2.2. Preservação de Dados de Formulário com `DataPersistorInterface`
Em caso de falha de validação ou exceção de persistência no controller [`Save`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductReview/Controller/Adminhtml/Review/Save.php), os dados postados são salvos na sessão através de:
```php
$this->dataPersistor->set('webjump_productreview_review', $data);
```
O [`DataProvider.php`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductReview/Model/Review/DataProvider.php) do formulário recupera esses dados temporários caso existam, evitando que o usuário perca suas alterações. Após o carregamento ou após um salvamento com sucesso, o registro é limpo com `clear()`.

### 2.3. Encapsulamento de Configurações com Service Model
Em vez de espalhar chamadas a `$this->scopeConfig->getValue(...)` pelo módulo, foi criada a classe de serviço [`Config.php`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductReview/Model/Config.php), expondo métodos com tipagem estrita:
- `isEnabled($store = null): bool`
- `getMinRating($store = null): int`
- `isApprovalRequired($store = null): bool`

### 2.4. Ações no Grid e Botão de Adição
No grid [`webjump_productreview_listing.xml`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductReview/view/adminhtml/ui_component/webjump_productreview_listing.xml):
- Adicionado botão `<button name="add">` apontando para `*/*/new`.
- Adicionada coluna de ações `<actionsColumn name="actions" class="Webjump\ProductReview\Ui\Component\Listing\Column\ReviewActions">` fornecendo botões de "Editar" e "Excluir" (com diálogo nativo de confirmação de exclusão).

---

## 3. Estrutura de Arquivos

```text
src/app/code/Webjump/ProductReview/
├── Block/
│   └── Adminhtml/
│       └── Review/
│           └── Edit/
│               ├── GenericButton.php        # Contexto base para os botões do formulário
│               ├── SaveButton.php           # Botão primário Salvar Avaliação
│               ├── SaveAndContinueButton.php# Botão Salvar e Continuar Editando
│               ├── BackButton.php           # Botão Voltar para a listagem
│               └── DeleteButton.php         # Botão Excluir com confirmação modal
├── Controller/
│   └── Adminhtml/
│       └── Review/
│           ├── Index.php                    # Action do Grid de avaliações
│           ├── NewAction.php                # Action que encaminha para criação vazia
│           ├── Edit.php                     # Action que carrega o registro e monta o form
│           ├── Save.php                     # Action que valida e salva via Repository
│           ├── Delete.php                   # Action que exclui via Repository
│           ├── MassApprove.php              # Action em massa para aprovação
│           ├── MassDisapprove.php           # Action em massa para reprovação
│           └── MassDelete.php               # Action em massa para exclusão
├── Model/
│   ├── Config.php                           # Leitor tipado de configurações (ScopeConfig)
│   ├── Review/
│   │   └── DataProvider.php                 # DataProvider do form com suporte a DataPersistor
│   └── Source/
│       ├── ApprovalStatus.php               # OptionSource para Pendente / Aprovado
│       └── RatingOptions.php                # OptionSource para notas de 1 a 5 estrelas
├── Ui/
│   └── Component/
│       └── Listing/
│           └── Column/
│               └── ReviewActions.php        # Coluna com links de Editar e Excluir
├── ViewModel/
│   └── ProductReviews.php                   # ViewModel para renderização na PDP
├── etc/
│   ├── acl.xml                              # Árvore ACL incluindo resource de config
│   ├── config.xml                           # Valores padrão de configuração
│   └── adminhtml/
│       ├── menu.xml                         # Item no menu principal
│       ├── routes.xml                       # Rota administrativa
│       └── system.xml                       # Seção de configuração do módulo
└── view/
    ├── adminhtml/
    │   ├── layout/
    │   │   ├── webjump_productreview_review_index.xml # Layout do Grid
    │   │   ├── webjump_productreview_review_edit.xml  # Layout do Form de Edição
    │   │   └── webjump_productreview_review_new.xml   # Layout do Form Novo
    │   └── ui_component/
    │       ├── webjump_productreview_listing.xml      # Grid com botão Add e ActionsColumn
    │       └── webjump_productreview_form.xml         # Form UI Component com validações
    └── frontend/
        ├── layout/
        │   └── catalog_product_view.xml               # Injeção do bloco de avaliações na PDP
        └── templates/
            └── product/
                └── view/
                    └── reviews.phtml                  # Template com exibição condicional e escaping
```

---

## 4. Evidências de Validação e Testes

### 4.1. Data Provider e Data Persistor do Formulário
```text
=== TESTANDO DATA PROVIDER E SESSÃO ===
Itens no banco carregados pelo DataProvider: 4
Exemplo de dados mapeados:
{
    "review_id": "1",
    "product_id": "2041",
    "author_name": "Mariana Silva",
    "comment": "Produto excelente! O tecido é de alta qualidade e o caimento ficou perfeito.",
    "rating": "5",
    "is_approved": "1",
    "created_at": "2026-09-16 11:13:27",
    "updated_at": "2026-09-16 11:13:27"
}
DataPersistor simulado com sucesso. Dados temporários preservados e recuperados após falha.
```

### 4.2. Validações no Backend do `Save` Controller
```text
=== TESTANDO REGRAS DE VALIDAÇÃO DO SAVE CONTROLLER ===
SUCESSO: Validação barrou autor vazio -> O campo "Nome do Autor" é obrigatório.
SUCESSO: Validação barrou nota inválida (6) -> O campo "Nota" deve ser um valor entre 1 e 5.
SUCESSO: Validação barrou produto inválido (0) -> O campo "ID do Produto" deve ser um número inteiro maior que zero.
SUCESSO: Validação barrou comentário vazio -> O campo "Comentário" é obrigatório.
```

### 4.3. Operações CRUD via Service Contracts (`ReviewRepositoryInterface`)
```text
=== TESTANDO CRUD VIA REPOSITORY ===
Criado review via Repository com ID: 11
Autor: Lucas Teste Automatizado 15.2 | Nota: 5 | Aprovado: 1
Editado review ID 11 via Repository:
Novo comentário: Comentário atualizado com sucesso no teste de edição do desafio 15.2.
Nova nota: 4
Exclusão do review ID 11 via Repository: SUCESSO
CONFIRMADO: NoSuchEntityException capturada corretamente após exclusão por deleteById().
```

### 4.4. Configurações de Sistema (`system.xml` e `config.xml`)
```text
=== TESTANDO CONFIGURAÇÃO ===
Config - Habilitado por padrão? SIM (1)
Config - Nota mínima padrão: 1
Config - Exigir aprovação padrão: SIM (1)
```

### 4.5. Respeito à Configuração na Loja (PDP)
```text
=== TESTANDO VIEWMODEL NA PDP (RESPEITO À CONFIGURAÇÃO) ===
Módulo habilitado por padrão? SIM
Reviews retornados com módulo habilitado: 1 (ou conforme produtos com avaliações no catálogo)
Módulo habilitado no cenário simulado desabilitado? NAO
Reviews retornados com módulo DESABILITADO: 0 (Corretamente ocultado da loja!)
```

### 4.6. Exportação CSV e XML Respeitando Filtros
Demonstração de exportação com filtro por nota máxima (`rating = 5`):
```text
=== TESTANDO EXPORTAÇÃO CSV E XML COM FILTROS ===
Total no Grid sem filtro: 5
Total no Grid filtrado por rating = 5: 3
 -> ID: 1 | Autor: Mariana Silva | Nota: 5
 -> ID: 3 | Autor: Beatriz Souza | Nota: 5
 -> ID: 5 | Autor: Juliana Ferreira | Nota: 5

--- Conteúdo do CSV Exportado (Respeitando filtro Nota 5) ---
ID,"ID do Produto",Autor,Comentário,Nota,"Status de Aprovação","Criado em","Atualizado em"
1,2041,"Mariana Silva","Produto excelente! O tecido é de alta qualidade e o caimento ficou perfeito.",5,Aprovado,"2026-09-16 11:13:27","2026-09-16 11:13:27"
3,48,"Beatriz Souza","Superou as expectativas, acabamento impecável e cor fiel às fotos do site.",5,Aprovado,"2026-09-16 11:13:27","2026-09-16 11:13:27"
5,50,"Juliana Ferreira","Amei a compra! Chegou antes do prazo previsto e o atendimento foi nota 10.",5,Aprovado,"2026-09-16 11:13:27","2026-09-16 11:13:27"

--- Conteúdo do XML Exportado ---
<?xml version="1.0" encoding="UTF-8"?>
<items>
  <item>
    <review_id>1</review_id>
    <product_id>2041</product_id>
    <author_name>Mariana Silva</author_name>
    <rating>5</rating>
    <status>Aprovado</status>
  </item>
  <item>
    <review_id>3</review_id>
    <product_id>48</product_id>
    <author_name>Beatriz Souza</author_name>
    <rating>5</rating>
    <status>Aprovado</status>
  </item>
  <item>
    <review_id>5</review_id>
    <product_id>50</product_id>
    <author_name>Juliana Ferreira</author_name>
    <rating>5</rating>
    <status>Aprovado</status>
  </item>
</items>
```

### 4.7. Qualidade de Código (PHPCS) e Integridade do Core
- **Padrão Magento 2 (`src/vendor/bin/phpcs --standard=Magento2`):**
  `0 errors, 0 warnings` em todo o código desenvolvido no módulo `src/app/code/Webjump/ProductReview/`.
- **Integridade do diretório `vendor/`:**
  Verificado via script `.agents/skills/magento-engineer/scripts/check-vendor-changes.sh`: `OK: no changes under vendor/.`

---

## 5. Evidências de Sucesso

Registro e comprovação visual de cada critério de aceite do **Desafio 15.2**, demonstrando o funcionamento das interfaces no painel administrativo, a renderização condicional na loja (PDP) e a fidelidade da exportação de dados.

---

### 5.1. Critério 1: Criar e editar pelo admin funciona, com validação de campo obrigatório

#### Evidência 1.1 — Declaração de Validações no Form UI Component (IDE)
- **Arquivo:** [`src/app/code/Webjump/ProductReview/view/adminhtml/ui_component/webjump_productreview_form.xml`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductReview/view/adminhtml/ui_component/webjump_productreview_form.xml)
- **Comprovação:** Regras de validação client-side configuradas com mensagens em português (`required-entry` e `validate-digits`) nos campos obrigatórios (`product_id`, `author_name`, `rating` e `comment`), assegurando integridade na entrada de dados.
> <img width="1625" height="840" alt="image" src="https://github.com/user-attachments/assets/3aee34ac-5ccc-454d-946a-a0e82fa9801f" />
> <img width="1618" height="850" alt="image" src="https://github.com/user-attachments/assets/e547de57-7ab5-4e3b-89fc-2e33ec96309e" />
> <img width="1617" height="661" alt="image" src="https://github.com/user-attachments/assets/f154e582-b8ab-4dff-a722-9d4ac652702c" />

---

#### Evidência 1.2 — Validação de Campos Obrigatórios no Formulário (Admin)
- **Tela:** Admin > **Webjump > Avaliações de Produtos > Nova Avaliação**
- **Comprovação:** Ao submeter o formulário sem preencher os campos, o validador client-side bloqueia o envio e exibe as mensagens de erro em português (*"Este campo é obrigatório."*) imediatamente abaixo dos campos **ID do Produto**, **Nome do Autor**, **Nota** e **Comentário**.
> <img width="1675" height="804" alt="image" src="https://github.com/user-attachments/assets/a282f2e6-62d2-4b04-b774-594c6608d941" />

---

#### Evidência 1.3 — Criação de Nova Avaliação com Sucesso (Admin)
- **Tela:** Admin > **Webjump > Avaliações de Produtos**
- **Comprovação:** Cadastro concluído de uma nova avaliação para o produto 2041 (Camisa Básica de Algodão), redirecionamento automático para a listagem com mensagem de sucesso (*"A avaliação foi salva com sucesso."*) e o novo registro exibido no topo do Grid.
> <img width="1696" height="1215" alt="image" src="https://github.com/user-attachments/assets/1c56f790-0439-4b1f-adf4-5bef3f409dd8" />

---

#### Evidência 1.4 — Edição de Avaliação Existente (Admin)
- **Tela:** Admin > **Webjump > Avaliações de Produtos > Editar Avaliação**
- **Comprovação:** Formulário de edição carregando os dados do registro existente com título dinâmico (*"Editar Avaliação de '[Autor]'"*), atualização da nota e do comentário, e retorno ao Grid com a mensagem de confirmação e os dados devidamente atualizados.
> <img width="1701" height="1628" alt="image" src="https://github.com/user-attachments/assets/16a7672d-9537-4429-b670-116b77a6874d" />

---

### 5.2. Critério 2: O Save usa o repository e trata erro devolvendo mensagem ao usuário

#### Evidência 2.1 — Controller `Save.php` Integrado ao Repository e com Tratamento de Erros (IDE)
- **Arquivo:** [`src/app/code/Webjump/ProductReview/Controller/Adminhtml/Review/Save.php`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductReview/Controller/Adminhtml/Review/Save.php)
- **Comprovação:** O controller utiliza exclusivamente o `ReviewRepositoryInterface` para persistência, executa validação de regras de negócio em backend (`validateData`), captura exceções em bloco `try ... catch` exibindo mensagens amigáveis via `messageManager` e preserva os inputs do usuário em sessão através do `DataPersistorInterface`.
> <img width="1623" height="283" alt="image" src="https://github.com/user-attachments/assets/f7107f1e-87d8-4f2e-9932-6c24abfbe376" />
> <img width="1621" height="495" alt="image" src="https://github.com/user-attachments/assets/d5594c37-94b1-4793-bf8b-81acec80e9aa" />
> <img width="1624" height="738" alt="image" src="https://github.com/user-attachments/assets/60732642-e4b5-4be5-b83d-d90fc9f26b55" />

---

#### Evidência 2.2 — Feedback Visual de Erro com Tratamento Amigável (Admin)
- **Tela:** Admin > **Webjump > Avaliações de Produtos**
- **Comprovação:** O campo **ID do Produto** é pré-configurado nativamente como numérico (`type="number"` com `min="1"` e `step="1"`), restringindo a entrada exclusivamente a números inteiros positivos para evitar erros por parte do usuário. Caso ocorra uma tentativa de submissão com valor inválido (`0`) ou acesso direto via URL de edição com um ID inexistente no repositório, a falha é interceptada de forma graciosa pelo controller e exibida no banner de alerta do Magento (*"O campo 'ID do Produto' deve ser um número inteiro maior que zero."* / *"Esta avaliação não existe mais."*), comprovando o tratamento seguro e amigável de exceções de backend sem quebras no sistema.
> _[Inserir print aqui]_

---

### 5.3. Critério 3: Existe seção em *Stores > Configuration*, com valores padrão funcionando

#### Evidência 3.1 — Declaração em `system.xml` e Valores Padrão em `config.xml` (IDE)
- **Arquivos:** [`src/app/code/Webjump/ProductReview/etc/adminhtml/system.xml`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductReview/etc/adminhtml/system.xml) e [`src/app/code/Webjump/ProductReview/etc/config.xml`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductReview/etc/config.xml)
- **Comprovação:** Seção de configuração registrada na aba `webjump` com os campos `enabled`, `min_rating` e `require_approval` contendo `canRestore="1"`, combinados aos valores padrão pré-definidos (`1`, `1`, `1`) no XML de configuração padrão do módulo.
> <img width="1618" height="729" alt="image" src="https://github.com/user-attachments/assets/cb681fba-9cbc-4eed-9500-ac6258065192" />
> <img width="1621" height="540" alt="image" src="https://github.com/user-attachments/assets/e63ef3e6-72de-43e4-b268-06b6182ab2c4" />

---

#### Evidência 3.2 — Painel *Stores > Configuration* com Valores Padrão e Checkboxes Ativos (Admin)
- **Tela:** Admin > **Stores > Configuration > Webjump > Avaliações de Produtos**
- **Comprovação:** Exibição da tela de configurações carregando os valores pré-definidos pelo módulo, com os checkboxes de herança de sistema `[✔] Use system value` ativos ao lado de cada campo:
  - **Habilitar Avaliações na Loja:** `Sim` `[✔] Use system value`
  - **Nota Mínima para Exibição:** `1` `[✔] Use system value`
  - **Exigir Moderação / Aprovação:** `Sim` `[✔] Use system value`
> <img width="1564" height="703" alt="image" src="https://github.com/user-attachments/assets/e4223a58-8662-4004-8a9a-99d9edb4341b" />

---

### 5.4. Critério 4: O módulo respeita a configuração (se desabilitado, não exibe na loja)

#### Evidência 4.1 — Lógica Condicional no ViewModel e Template (IDE)
- **Arquivos:** [`src/app/code/Webjump/ProductReview/ViewModel/ProductReviews.php`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductReview/ViewModel/ProductReviews.php) e [`src/app/code/Webjump/ProductReview/view/frontend/templates/product/view/reviews.phtml`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductReview/view/frontend/templates/product/view/reviews.phtml)
- **Comprovação:** Método `getReviews()` consultando o status de habilitação do módulo via `Config::isEnabled()` e cláusula guarda no template `reviews.phtml`, assegurando que nada é consultado ou renderizado caso o recurso esteja desativado.
> <img width="1623" height="1162" alt="image" src="https://github.com/user-attachments/assets/05d10acd-9dcc-4b70-bd43-d4162233d9aa" />

---

#### Evidência 4.2 — Seção de Avaliações Renderizada na PDP com Módulo Habilitado (Loja)
- **URL da Loja:** [`https://magento.test/catalog/product/view/id/2041`](https://magento.test/catalog/product/view/id/2041) (Camisa Básica de Algodão)
- **Comprovação:** Com a configuração ativa, a seção **Avaliações de Clientes** é renderizada perfeitamente na página do produto, exibindo a listagem com estrelas amarelas (`★★★★☆`), autor, data e comentário.
> <img width="1603" height="789" alt="image" src="https://github.com/user-attachments/assets/e3b31ce0-ac6a-48a4-b0e3-26aed7ce23ff" />

---

#### Evidência 4.3 — Seção de Avaliações Oculta na PDP com Módulo Desabilitado (Loja)
- **URL da Loja:** [`https://magento.test/catalog/product/view/id/2041`](https://magento.test/catalog/product/view/id/2041) (Camisa Básica de Algodão)
- **Comprovação:** Com o módulo desabilitado na configuração do sistema (*Habilitar Avaliações na Loja = Não*), a seção de avaliações customizadas é completamente suprimida da página do produto no frontend, confirmando o respeito integral à configuração da loja.
> <img width="1396" height="900" alt="image" src="https://github.com/user-attachments/assets/64f35287-e50c-4c8a-9d72-7f5ca29d3bc8" />

---

### 5.5. Critérios 5 e 6: A exportação em CSV e Excel XML funciona e respeita os filtros aplicados no grid

#### Evidência 5.1 — Botão de Exportação no Listing UI Component (IDE)
- **Arquivo:** [`src/app/code/Webjump/ProductReview/view/adminhtml/ui_component/webjump_productreview_listing.xml`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductReview/view/adminhtml/ui_component/webjump_productreview_listing.xml)
- **Comprovação:** Componente `<exportButton name="export_button">` declarado no `<listingToolbar>` (linhas 80 a 86) integrado ao provedor de seleções `ids` do grid.
> <img width="1592" height="426" alt="image" src="https://github.com/user-attachments/assets/8e996986-5c8a-4d9d-897d-7007f8960887" />

---

#### Evidência 5.2 — Exportação CSV Respeitando Filtro de Nota (Admin & Planilha)
- **Tela & Arquivo:** Grid filtrado por Nota 5 e arquivo `.csv` exportado
- **Comprovação:**
  - **Grid no Admin:** Listagem filtrada exibindo apenas avaliações com nota máxima (5) e download do arquivo CSV realizado com sucesso.
  - **Arquivo CSV:** Arquivo gerado aberto em planilha/editor confirmando que **todas as linhas exportadas possuem Nota = 5**, comprovando que os filtros aplicados na interface foram integralmente repassados ao exportador.
> <img width="1647" height="1009" alt="image" src="https://github.com/user-attachments/assets/d1580817-11df-454a-937d-33c4d09fb41c" />

---

#### Evidência 5.3 — Exportação Excel XML Respeitando Filtros (Admin & Arquivo XML)
- **Tela & Arquivo:** Grid filtrado e arquivo `.xml` exportado
- **Comprovação:** Arquivo no formato Excel XML baixado via barra de ferramentas e aberto em editor, comprovando a estrutura XML bem formada e a restrição dos registros conforme os filtros aplicados no grid administrativo.
> <img width="1425" height="1043" alt="image" src="https://github.com/user-attachments/assets/3235a5f5-1fb5-4c5c-bfa9-9be5b97fae26" />



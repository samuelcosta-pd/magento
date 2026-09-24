# Desafio 15.3: A Exportação que o Cliente Realmente Queria

Documentação técnica, justificativa arquitetural, estratégia de Pull Request e evidências de implementação do **Desafio 15.3** (Desafio Extra da Sprint 7 — Admin, CRUD e Exportação) no módulo [`Webjump_ProductReview`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductReview).

---

## 1. Critérios de Aceite Atendidos

| Critério de Aceite | Status | Detalhamento da Implementação |
|---|:---:|---|
| **Na planilha, o campo aprovado sai como Sim ou Não** | [x] Atendido | O processador de exportação [`ReviewExportDataProcessor`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductReview/Model/Export/ReviewExportDataProcessor.php) converte valores booleanos `1` e `0` para as strings localizadas `"Sim"` e `"Não"`, substituindo os números brutos por valores semânticos amigáveis ao atendimento. |
| **A data sai em formato brasileiro** | [x] Atendido | Os campos `created_at` e `updated_at` são convertidos do fuso UTC do banco para o timezone da aplicação/admin e formatados estritamente no padrão brasileiro `d/m/Y H:i:s` (ex: `16/09/2026 07:13:27`) utilizando `\Magento\Framework\Stdlib\DateTime\TimezoneInterface`. |
| **A coluna com o nome do produto aparece no arquivo** | [x] Atendido | Injetada a coluna **Nome do Produto** adjacente a **ID do Produto**. A resolução do nome é feita via `ProductRepositoryInterface` com cache em memória por `product_id` e tratamento defensivo com `NoSuchEntityException` para integridade total. |
| **A exportação de pedidos e de clientes continua funcionando normalmente** | [x] Atendido | A estratégia adotada manteve as rotas e classes nativas do Magento (`mui/export/*` e `\Magento\Ui\Model\Export\MetadataProvider`) 100% intocadas. Os grids de Pedidos (`sales_order_grid`) e Clientes (`customer_listing`) continuam operando normalmente sem qualquer risco de regressão. |
| **README explica a estratégia escolhida e por que ela é segura** | [x] Atendido | Seção 2 detalha a comparação técnica entre as Estratégias A e B, comprovando por que o uso de controller e conversores dedicados é a abordagem mais segura e aderente aos princípios SOLID no Magento 2. |

---

## 2. Decisão Arquitetural e Justificativa de Segurança

O desafio propõe duas abordagens possíveis para atender às necessidades do atendimento sem quebrar os grids do núcleo:
1. **Estratégia A:** Substituir a conversão com cuidado defensivo (via plugin ou preference em classes globais do core como `MetadataProvider`).
2. **Estratégia B (Escolhida):** Criar formato de exportação próprio com controller, rotas, conversores e permissão dedicados.

### 2.1. Comparativo Técnico

```
                                  [ Requisição de Exportação ]
                                                │
                 ┌──────────────────────────────┴──────────────────────────────┐
                 ▼                                                             ▼
     [ Grids Nativos do Core ]                                   [ Grid de Avaliações Webjump ]
  (Pedidos, Clientes, Produtos)                                     (webjump_productreview)
                 │                                                             │
                 ▼                                                             ▼
       Rota: mui/export/gridToCsv                               Rota: webjump_productreview/export/gridToCsv
                 │                                                             │
                 ▼                                                             ▼
Magento\Ui\Controller\Adminhtml\Export\GridToCsv           Webjump\ProductReview\Controller\Adminhtml\Export\GridToCsv
                 │                                                             │
                 ▼                                                             ▼
   Magento\Ui\Model\Export\ConvertToCsv                      Webjump\ProductReview\Model\Export\ConvertToCsv
                 │                                                             │
                 ▼                                                             ▼
  Magento\Ui\Model\Export\MetadataProvider                  Webjump\ProductReview\Model\Export\ReviewExportDataProcessor
  (Formatos originais do core intactos)                      (Sim/Não, Data BR d/m/Y H:i:s, Nome do Produto)
```

| Dimensão de Análise | Estratégia A (Plugin/Preference Defensivo no Core) | Estratégia B (Controller e Formato Próprios — ESCOLHIDA) |
|---|---|---|
| **Blast Radius (Raio de Impacto)** | **Médio / Alto:** `MetadataProvider` é um serviço singleton/compartilhado que atende a todos os grids do Magento Admin. Qualquer exceção não capturada ou falha de tipagem no plugin quebra grids vitais da loja, como Vendas e Clientes. | **Zero:** As classes e rotas nativas do core não foram alteradas nem estendidas globalmente. Grids do núcleo permanecem fisicamente isolados da lógica de avaliações. |
| **Segurança e ACL** | A rota nativa `mui/export/*` tenta inspecionar a árvore ACL do componente via convenção de dataProvider. Se ausente, pode recorrer a fallbacks genéricos. | Os novos controllers declaram explicitamente `public const ADMIN_RESOURCE = 'Webjump_ProductReview::reviews_export';`, aplicando o princípio do menor privilégio. |
| **Formatação de Datas** | A propriedade `$dateFormat = 'M j, Y h:i:s A'` do `MetadataProvider` é configurada globalmente via DI. Alterá-la afetaria todas as exportações da loja. | O conversor dedicado formata exclusivamente as datas do módulo no padrão `d/m/Y H:i:s`, sem afetar nenhum outro componente. |
| **Resolução do Nome do Produto** | O método `MetadataProvider::getRowData()` recebe apenas `$document, $fields, $options`, sem a referência direta ao `$component`. Injetar colunas extras exigiria sincronização frágil de estado entre métodos. | O `ReviewExportDataProcessor` gerencia explicitamente os cabeçalhos e as linhas com injeção tipada de `ProductRepositoryInterface`, garantindo coesão e manutenibilidade. |
| **Princípios SOLID** | Viola o Princípio da Responsabilidade Única (mistura regras de negócio de avaliações em um componente genérico de UI do Magento). | Adere ao **Single Responsibility** e ao **Open/Closed Principle** (estende a funcionalidade via configuração do UI Component sem modificar código do core). |

---

## 3. Estrutura de Arquivos Criados e Modificados

```text
src/app/code/Webjump/ProductReview/
├── Controller/
│   └── Adminhtml/
│       └── Export/
│           ├── GridToCsv.php              # Action dedicada para exportação CSV (ACL reviews_export)
│           └── GridToXml.php              # Action dedicada para exportação Excel XML (ACL reviews_export)
├── Model/
│   └── Export/
│       ├── ConvertToCsv.php               # Conversor CSV com paginação segura e filtros nativos
│       ├── ConvertToXml.php               # Conversor Excel XML com suporte a iterador de SearchResult
│       └── ReviewExportDataProcessor.php  # Formatação de Sim/Não, Data BR e busca em cache do produto
├── i18n/
│   └── pt_BR.csv                          # Dicionário atualizado com traduções do desafio
└── view/
    └── adminhtml/
        └── ui_component/
            └── webjump_productreview_listing.xml # exportButton apontando para as novas rotas customizadas
```

---

## 4. Estratégia de Branches e Pull Request (Solução do Impedimento)

### 4.1. O Desafio das Branches Empilhadas (*Stacked Branches*)
O Desafio 15.3 depende diretamente dos componentes criados no Desafio 15.2 (grid administrativo com `exportButton`, repositório e configurações). Como a branch `exercicio/15.2-FormularioConfiguracaoExportacao` ainda está sob revisão e não foi mergeada na `main`, criar uma PR tradicional diretamente contra a `main` faria o GitHub exibir todos os arquivos da 15.2 duplicados na PR da 15.3.

### 4.2. Fluxo de Trabalho Adotado

1. **Branching a partir da 15.2:**
   A branch de trabalho foi criada com base na 15.2:
   ```bash
   git checkout -b exercicio/15.3-ExportacaoCustomizada exercicio/15.2-FormularioConfiguracaoExportacao
   ```
2. **Isolamento de Commits:**
   Apenas os arquivos estritamente novos do Desafio 15.3 foram commitados nesta branch.
3. **Abertura do Pull Request no GitHub:**
   - **Enquanto a 15.2 não for mergeada na `main`:**
     Ao abrir o PR no GitHub, define-se:
     - **Base branch:** `exercicio/15.2-FormularioConfiguracaoExportacao`
     - **Compare branch:** `exercicio/15.3-ExportacaoCustomizada`
     *Resultado:* O GitHub calcula o diff relativo exclusivamente à branch 15.2. O PR exibirá **apenas os arquivos novos da 15.3**, sem duplicar nada da 15.2!
   - **Quando a 15.2 for mergeada na `main`:**
     - Se o merge da 15.2 for realizado via *Merge Commit* (padrão utilizado neste repositório nos PRs anteriores #7 e #8), o histórico é preservado. O GitHub permite alterar a Base branch da PR 15.3 diretamente para `main` (ou o faz automaticamente se a branch 15.2 for deletada após o merge), mantendo o diff limpo.
     - Se o merge for realizado com *Squash* na `main`, basta executar um rebase local de um comando para alinhar com a nova `main`:
       ```bash
       git checkout exercicio/15.3-ExportacaoCustomizada
       git rebase --onto main exercicio/15.2-FormularioConfiguracaoExportacao
       git push origin exercicio/15.3-ExportacaoCustomizada --force-with-lease
       ```

---

## 5. Evidências de Validação e Testes Automatizados (CLI)

### 5.1. Teste Unitário do Processador de Dados (`ReviewExportDataProcessor`)
Execução do processador isolado validando cabeçalhos, resolução de catálogo e formatação:

```text
========================================================
=== TESTE 1: REVIEW EXPORT DATA PROCESSOR UNITÁRIO =====
========================================================
Cabeçalhos retornados:
  [0] ID
  [1] ID do Produto
  [2] Nome do Produto
  [3] Autor
  [4] Comentário
  [5] Nota
  [6] Status de Aprovação
  [7] Criado em
  [8] Atualizado em

-> 'Nome do Produto' está no cabeçalho? SIM [SUCESSO]

Linha Aprovada formatada:
Array
(
    [0] => 101
    [1] => 2041
    [2] => Camisa Básica de Algodão
    [3] => Teste Aprovado
    [4] => Ótimo produto!
    [5] => 5
    [6] => Sim
    [7] => 16/09/2026 10:30:00
    [8] => 16/09/2026 11:45:12
)

Linha Pendente formatada:
Array
(
    [0] => 102
    [1] => 2041
    [2] => Camisa Básica de Algodão
    [3] => Teste Pendente
    [4] => Aguardando moderação
    [5] => 3
    [6] => Não
    [7] => 20/09/2026 05:15:00
    [8] => 20/09/2026 05:15:00
)

-> Status linha aprovada é 'Sim'? SIM [SUCESSO]
-> Status linha pendente é 'Não'? SIM [SUCESSO]
-> Data criada linha aprovada formato brasileiro? 16/09/2026 10:30:00 [SUCESSO]
-> Nome do produto 2041 resolvido? 'Camisa Básica de Algodão' [SUCESSO]
```

---

### 5.2. Teste da Exportação CSV Completa com Registros Reais do Banco
Extração real do arquivo CSV gerado pelo conversor a partir dos dados do banco, comprovando o status Sim/Não, as datas no padrão brasileiro e a presença da coluna com o Nome do Produto:

```text
========================================================
=== TESTE 2: EXECUÇÃO COMPLETA DA EXPORTAÇÃO CSV =======
========================================================
CSV gerado com sucesso!
Arquivo: export/webjump_productreview_listing.csv

--- CONTEÚDO DO CSV EXPORTADO ---
ID,"ID do Produto","Nome do Produto",Autor,Comentário,Nota,"Status de Aprovação","Criado em","Atualizado em"
1,2041,"Camisa Básica de Algodão","Mariana Silva","Produto excelente! O tecido é de alta qualidade e o caimento ficou perfeito.",5,Sim,"16/09/2026 07:13:27","16/09/2026 07:13:27"
3,48,"Chaz Kangeroo Hoodie-XS-Gray","Beatriz Souza","Superou as expectativas, acabamento impecável e cor fiel às fotos do site.",5,Sim,"16/09/2026 07:13:27","16/09/2026 07:13:27"
4,49,"Chaz Kangeroo Hoodie-XS-Orange","Rodrigo Mendes","Produto razoável, atende ao básico mas o acabamento interno poderia ser melhor.",3,Sim,"16/09/2026 07:13:27","21/09/2026 10:02:28"
5,50,"Chaz Kangeroo Hoodie-S-Black","Juliana Ferreira","Amei a compra! Chegou antes do prazo previsto e o atendimento foi nota 10.",5,Sim,"16/09/2026 07:13:27","16/09/2026 07:13:27"
12,2041,"Camisa Básica de Algodão","Lucas Rocha","Ótimo produto, material resistente e entrega antes do prazo combinado.",4,Sim,"22/09/2026 08:31:01","22/09/2026 08:31:01"
13,47,"Chaz Kangeroo Hoodie-XS-Black","Camila Nogueira","Simplesmente incrível! Design muito moderno e atendeu todas as minhas necessidades.",5,Sim,"22/09/2026 08:31:01","22/09/2026 08:31:01"
14,48,"Chaz Kangeroo Hoodie-XS-Gray","Felipe Albuquerque","O produto é bonito, mas a costura veio com alguns fios soltos. Esperava mais pelo valor.",2,Sim,"22/09/2026 08:31:01","22/09/2026 08:31:01"
15,49,"Chaz Kangeroo Hoodie-XS-Orange","Fernanda Lima","Adorei a compra! Veio muito bem embalado e a cor é idêntica à do anúncio.",5,Sim,"22/09/2026 08:31:01","22/09/2026 08:31:01"
16,50,"Chaz Kangeroo Hoodie-S-Black","Gustavo Henrique","Bom custo-benefício. Uso diariamente e não apresentou nenhum defeito até o momento.",4,Sim,"22/09/2026 08:31:01","22/09/2026 08:31:01"
17,51,"Chaz Kangeroo Hoodie-S-Gray","Patrícia Antunes","Tamanho ficou um pouco justo em relação à tabela de medidas, mas o tecido é confortável.",3,Sim,"22/09/2026 08:31:01","22/09/2026 08:31:01"
19,2041,"Camisa Básica de Algodão","Samuel (teste 15.2)","Excelente acabamento e caimento. Teste de criação via Admin no desafio 15.2.",4,Não,"23/09/2026 09:28:16","23/09/2026 15:28:37"
```

---

### 5.3. Teste da Exportação Excel XML Completa
Fragmento do XML gerado pelo `ConvertToXml`:

```xml
<?xml version="1.0"?>
<?mso-application progid="Excel.Sheet"?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xmlns:x="urn:schemas-microsoft-com:office:excel"
          xmlns:x2="http://schemas.microsoft.com/office/excel/2003/xml"
          xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
          xmlns:o="urn:schemas-microsoft-com:office:office"
          xmlns:html="http://www.w3.org/TR/REC-html40"
          xmlns:c="urn:schemas-microsoft-com:office:component:spreadsheet">
  <Worksheet ss:Name="webjump_productreview_listing.xml">
    <Table>
      <Row>
        <Cell><Data ss:Type="String">ID</Data></Cell>
        <Cell><Data ss:Type="String">ID do Produto</Data></Cell>
        <Cell><Data ss:Type="String">Nome do Produto</Data></Cell>
        <Cell><Data ss:Type="String">Autor</Data></Cell>
        <Cell><Data ss:Type="String">Comentário</Data></Cell>
        <Cell><Data ss:Type="String">Nota</Data></Cell>
        <Cell><Data ss:Type="String">Status de Aprovação</Data></Cell>
        <Cell><Data ss:Type="String">Criado em</Data></Cell>
        <Cell><Data ss:Type="String">Atualizado em</Data></Cell>
      </Row>
      <Row>
        <Cell><Data ss:Type="Number">1</Data></Cell>
        <Cell><Data ss:Type="Number">2041</Data></Cell>
        <Cell><Data ss:Type="String">Camisa Básica de Algodão</Data></Cell>
        <Cell><Data ss:Type="String">Mariana Silva</Data></Cell>
        <Cell><Data ss:Type="String">Produto excelente! O tecido é de alta qualidade e o caimento ficou perfeito.</Data></Cell>
        <Cell><Data ss:Type="Number">5</Data></Cell>
        <Cell><Data ss:Type="String">Sim</Data></Cell>
        <Cell><Data ss:Type="String">16/09/2026 07:13:27</Data></Cell>
        <Cell><Data ss:Type="String">16/09/2026 07:13:27</Data></Cell>
      </Row>
      <Row>
        <Cell><Data ss:Type="Number">19</Data></Cell>
        <Cell><Data ss:Type="Number">2041</Data></Cell>
        <Cell><Data ss:Type="String">Camisa Básica de Algodão</Data></Cell>
        <Cell><Data ss:Type="String">Samuel (teste 15.2)</Data></Cell>
        <Cell><Data ss:Type="String">Excelente acabamento e caimento. Teste de criação via Admin no desafio 15.2.</Data></Cell>
        <Cell><Data ss:Type="Number">4</Data></Cell>
        <Cell><Data ss:Type="String">Não</Data></Cell>
        <Cell><Data ss:Type="String">23/09/2026 09:28:16</Data></Cell>
        <Cell><Data ss:Type="String">23/09/2026 15:28:37</Data></Cell>
      </Row>
    </Table>
  </Worksheet>
</Workbook>
```

---

### 5.4. Respeito Rigoroso a Filtros e Seleções do Grid

#### A) Filtro por Intervalo de Nota (`rating = 5`)
Ao aplicar filtro por nota 5, o conversor exporta exclusivamente os registros com nota máxima, incluindo o nome do produto e data brasileira:
```text
=== CSV COM FILTRO NOTA = 5 (from 5 to 5) ===
ID,"ID do Produto","Nome do Produto",Autor,Comentário,Nota,"Status de Aprovação","Criado em","Atualizado em"
1,2041,"Camisa Básica de Algodão","Mariana Silva","Produto excelente! O tecido é de alta qualidade e o caimento ficou perfeito.",5,Sim,"16/09/2026 07:13:27","16/09/2026 07:13:27"
3,48,"Chaz Kangeroo Hoodie-XS-Gray","Beatriz Souza","Superou as expectativas, acabamento impecável e cor fiel às fotos do site.",5,Sim,"16/09/2026 07:13:27","16/09/2026 07:13:27"
5,50,"Chaz Kangeroo Hoodie-S-Black","Juliana Ferreira","Amei a compra! Chegou antes do prazo previsto e o atendimento foi nota 10.",5,Sim,"16/09/2026 07:13:27","16/09/2026 07:13:27"
13,47,"Chaz Kangeroo Hoodie-XS-Black","Camila Nogueira","Simplesmente incrível! Design muito moderno e atendeu todas as minhas necessidades.",5,Sim,"22/09/2026 08:31:01","22/09/2026 08:31:01"
15,49,"Chaz Kangeroo Hoodie-XS-Orange","Fernanda Lima","Adorei a compra! Veio muito bem embalado e a cor é idêntica à do anúncio.",5,Sim,"22/09/2026 08:31:01","22/09/2026 08:31:01"
```

#### B) Filtro por Autor (`author_name = "Mariana Silva"`)
```text
=== CSV COM FILTRO AUTOR = Mariana Silva ===
ID,"ID do Produto","Nome do Produto",Autor,Comentário,Nota,"Status de Aprovação","Criado em","Atualizado em"
1,2041,"Camisa Básica de Algodão","Mariana Silva","Produto excelente! O tecido é de alta qualidade e o caimento ficou perfeito.",5,Sim,"16/09/2026 07:13:27","16/09/2026 07:13:27"
```

#### C) Seleção Manual por Checkboxes (`selected = [1, 5]`)
```text
=== CSV COM SELEÇÃO (IDs 1 e 5) ===
ID,"ID do Produto","Nome do Produto",Autor,Comentário,Nota,"Status de Aprovação","Criado em","Atualizado em"
1,2041,"Camisa Básica de Algodão","Mariana Silva","Produto excelente! O tecido é de alta qualidade e o caimento ficou perfeito.",5,Sim,"16/09/2026 07:13:27","16/09/2026 07:13:27"
5,50,"Chaz Kangeroo Hoodie-S-Black","Juliana Ferreira","Amei a compra! Chegou antes do prazo previsto e o atendimento foi nota 10.",5,Sim,"16/09/2026 07:13:27","16/09/2026 07:13:27"
```

---

### 5.5. Prova de Não-Regressão nos Grids Nativos (Pedidos e Clientes)
Execução direta do exportador nativo do Magento (`\Magento\Ui\Model\Export\ConvertToCsv`) nos componentes `sales_order_grid` e `customer_listing`:

```text
========================================================
=== TESTE 4: GRIDS DO NÚCLEO (PEDIDOS E CLIENTES) ======
========================================================
Instanciação de Magento\Ui\Model\Export\ConvertToCsv: OK [SUCESSO]
Instanciação de Magento\Ui\Model\Export\ConvertToXml: OK [SUCESSO]
Instanciação de Magento\Ui\Model\Export\MetadataProvider: OK [SUCESSO]

Testando exportação do grid nativo de Pedidos (sales_order_grid)...
Exportação nativa de pedidos executada com sucesso! Arquivo: export/sales_order_grid.csv
Cabeçalho de Pedidos: ID,"Purchase Point","Purchase Date","Bill-to Name","Ship-to Name","Grand Total (Base)","Grand Total (Purchased)",Status,"Billing Address","Shipping Address","Shipping Information","Customer Email","Customer Group",Subtotal,"Shipping and Handling","Customer Name","Payment Method","Total Refunded","Allocated sources","Pickup Location Code","Braintree Transaction Source","Dispute State"

Testando exportação do grid nativo de Clientes (customer_listing)...
Exportação nativa de clientes executada com sucesso! Arquivo: export/customer_listing.csv
Cabeçalho de Clientes: ID,Name,Email,Group,Phone,ZIP,Country,State/Province,"Customer Since","Web Site","Confirmed email","Account Created in","Billing Address","Shipping Address","Date of Birth","Tax VAT Number",Gender,"Street Address",City,Fax,"VAT Number",Company,"Billing Firstname","Billing Lastname","Account Lock"

Todos os testes finalizados com êxito!
```

---

### 5.6. Qualidade de Código (PHPCS) e Integridade do Core

- **PHP CodeSniffer (`phpcs --standard=Magento2 app/code/Webjump/ProductReview`):**
  `0 errors, 0 warnings` em todos os novos controllers e models criados.
- **Integridade do diretório `vendor/`:**
  Verificado via script `.agents/skills/magento-engineer/scripts/check-vendor-changes.sh`:
  `OK: no changes under vendor/.`

---

## 6. Evidências de Sucesso

Registro e comprovação visual de cada critério de aceite do **Desafio 15.3**, demonstrando o funcionamento da exportação customizada no painel administrativo, a formatação das colunas na planilha e a não-interferência nos grids nativos do núcleo.

---

### 6.1. Critério 1: Na planilha, o campo aprovado sai como Sim ou Não

#### Evidência 1.1 — Implementação da Conversão Booleana para Sim/Não (IDE)
- **Arquivo:** [`src/app/code/Webjump/ProductReview/Model/Export/ReviewExportDataProcessor.php`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductReview/Model/Export/ReviewExportDataProcessor.php#L80-L93)
- **Comprovação:** Método `getRowData()` mapeando a propriedade booleana `is_approved` da entidade: caso o valor seja `1`, converte para a string traduzida `Sim`; caso seja `0`, converte para `Não`, eliminando os valores numéricos brutos reclamados pelo atendimento.
> <!-- Inserir print da IDE: ReviewExportDataProcessor.php destacando o operador ternário $isApproved === 1 ? 'Sim' : 'Não' -->

---

#### Evidência 1.2 — Planilha Exportada Exibindo Status "Sim" e "Não" (Planilha / Editor)
- **Tela & Arquivo:** Arquivo `avaliacoes_produtos.csv` ou `.xml` baixado via **Webjump > Avaliações de Produtos > Exportar**
- **Comprovação:** A coluna **Status de Aprovação** na planilha aberta exibe expressamente **"Sim"** para os registros aprovados e **"Não"** para o registro reprovado/pendente (ex: ID 19), comprovando a semântica amigável solicitada pelo suporte.
> <!-- Inserir print da planilha/arquivo: Coluna "Status de Aprovação" contendo as células "Sim" e "Não" -->

---

### 6.2. Critério 2: A data sai em formato brasileiro

#### Evidência 2.1 — Conversão de Fuso Horário e Formatação Brasileira `d/m/Y H:i:s` (IDE)
- **Arquivo:** [`src/app/code/Webjump/ProductReview/Model/Export/ReviewExportDataProcessor.php`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductReview/Model/Export/ReviewExportDataProcessor.php#L140-L157)
- **Comprovação:** Método privado `formatDate()` utilizando o serviço `TimezoneInterface` do Magento para converter o timestamp UTC persistido no banco para o fuso horário configurado no ambiente, aplicando a formatação brasileira `d/m/Y H:i:s`.
> <!-- Inserir print da IDE: ReviewExportDataProcessor.php destacando o método formatDate() com o formato 'd/m/Y H:i:s' -->

---

#### Evidência 2.2 — Colunas de Data no Formato Brasileiro na Planilha (Planilha / Editor)
- **Tela & Arquivo:** Arquivo `avaliacoes_produtos.csv` aberto em planilha ou editor de texto
- **Comprovação:** As colunas **"Criado em"** e **"Atualizado em"** exibem as datas no padrão brasileiro `DD/MM/AAAA HH:MM:SS` (ex: `16/09/2026 07:13:27` e `23/09/2026 09:28:16`), eliminando a notação americana (`MM/DD/YYYY` ou `Sep 16, 2026`).
> <!-- Inserir print da planilha/arquivo: Colunas "Criado em" e "Atualizado em" com valores no formato DD/MM/AAAA HH:MM:SS -->

---

### 6.3. Critério 3: A coluna com o nome do produto aparece no arquivo

#### Evidência 3.1 — Injeção do Cabeçalho e Resolução via `ProductRepositoryInterface` com Cache (IDE)
- **Arquivo:** [`src/app/code/Webjump/ProductReview/Model/Export/ReviewExportDataProcessor.php`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductReview/Model/Export/ReviewExportDataProcessor.php#L50-L65) e [`ReviewExportDataProcessor.php`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductReview/Model/Export/ReviewExportDataProcessor.php#L115-L135)
- **Comprovação:** Inclusão da coluna `__('Nome do Produto')` no método `getHeaders()` e implementação do método `getProductName()`, que consulta o catálogo via `ProductRepositoryInterface` com cache em memória por ID e fallback defensivo contra `NoSuchEntityException`.
> <!-- Inserir print da IDE: ReviewExportDataProcessor.php destacando getHeaders() e getProductName() com cache -->

---

#### Evidência 3.2 — Contraste Visual: Grid no Admin sem Nome vs Planilha Exportada com o Nome do Produto (Admin & Planilha)
- **Tela 1 (Admin):** Admin > **Webjump > Avaliações de Produtos**
  - **Comprovação:** O grid administrativo visual exibe apenas a coluna **ID do Produto** (o nome do produto não está presente no grid, exatamente conforme mencionado pelo atendimento).
- **Tela 2 (Planilha):** Arquivo `avaliacoes_produtos.csv` aberto
  - **Comprovação:** A planilha exportada contém a coluna **"Nome do Produto"** posicionada entre "ID do Produto" e "Autor", exibindo os nomes reais cadastrados no catálogo (ex: *"Camisa Básica de Algodão"*, *"Chaz Kangeroo Hoodie"*).
> <!-- Inserir print 1 (Admin): Grid com as colunas ID, ID do Produto, Autor... sem o Nome do Produto -->
> <!-- Inserir print 2 (Planilha): Planilha exibindo a coluna "Nome do Produto" devidamente preenchida -->

---

### 6.4. Critério 4: A exportação de pedidos e de clientes continua funcionando normalmente

#### Evidência 4.1 — Exportação Nativa do Grid de Pedidos no Admin (Admin & Planilha)
- **Tela:** Admin > **Sales > Orders** (Vendas > Pedidos)
- **Comprovação:** O botão **Export** do grid nativo de pedidos permanece funcionando sem erros, gerando o arquivo CSV de pedidos através da rota nativa `mui/export/gridToCsv` com todos os cabeçalhos padrão do Magento intactos (`ID`, `Purchase Point`, `Grand Total`, `Status`).
> <!-- Inserir print 1 (Admin): Tela Sales > Orders acionando a exportação -->
> <!-- Inserir print 2 (Planilha): Arquivo CSV de Pedidos aberto confirmando integridade dos dados -->

---

#### Evidência 4.2 — Exportação Nativa do Grid de Clientes no Admin (Admin & Planilha)
- **Tela:** Admin > **Customers > All Customers** (Clientes > Todos os Clientes)
- **Comprovação:** O botão **Export** do grid nativo de clientes funciona normalmente, permitindo o download do arquivo CSV através da rota nativa do Magento com as colunas nativas (`ID`, `Name`, `Email`, `Group`, `ZIP`).
> <!-- Inserir print 1 (Admin): Tela Customers > All Customers acionando a exportação -->
> <!-- Inserir print 2 (Planilha): Arquivo CSV de Clientes aberto confirmando integridade dos dados -->

---

#### Evidência 4.3 — Isolamento Arquitetural das Rotas no UI Component Listing (IDE)
- **Arquivo:** [`src/app/code/Webjump/ProductReview/view/adminhtml/ui_component/webjump_productreview_listing.xml`](file:///home/samuel/Sites/magento/src/app/code/Webjump/ProductReview/view/adminhtml/ui_component/webjump_productreview_listing.xml#L80-L95)
- **Comprovação:** O `<exportButton>` foi configurado exclusivamente no XML do grid de avaliações apontando para as actions dedicadas `webjump_productreview/export/gridToCsv` e `gridToXml`. Nenhuma rota, classe ou template nativo do Magento foi interceptado, garantindo **Blast Radius Zero**.
> <!-- Inserir print da IDE: webjump_productreview_listing.xml destacando a tag exportButton com as URLs dedicadas -->

---

### 6.5. Critério 5: README explica a estratégia escolhida e por que ela é segura

#### Evidência 5.1 — Documentação Técnica com Comparativo e Análise de Segurança no README (IDE / Markdown)
- **Arquivo:** [`README-15-3.md`](file:///home/samuel/Sites/magento/README-15-3.md#L19-L57)
- **Comprovação:** Seção 2 documentando em detalhes o comparativo entre a **Estratégia A** (Plugin/Preference defensivo) e a **Estratégia B** (Controller e formato próprios), acompanhada do diagrama de fluxo de requisição, análise de segurança e justificativa de escolha baseada em isolamento de falhas e princípios SOLID.
> <!-- Inserir print do README-15-3.md: Seção 2 exibindo o diagrama de fluxo e a tabela comparativa de segurança -->

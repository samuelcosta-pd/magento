# Módulo Webjump_ProductBadge

Módulo desenvolvido para atender ao desafio **14.1 - Atributo de produto por código** no Magento 2.4.8-p1.

---

## 1. Visão Geral do Desafio

### Objetivo de Negócio
Permitir que a loja destaque produtos com selos visuais (por exemplo, **"Sustentável"**, **"Eco-Friendly"**, **"Vegano"**, **"Artesanal"**), sem necessidade de criação manual do atributo pelo painel administrativo em cada ambiente (desenvolvimento, staging, produção).

### Requisitos Técnicos
1. **Criação via Código**: O atributo deve ser criado exclusivamente via **Data Patch** (`\Magento\Framework\Setup\Patch\DataPatchInterface`), sendo executado de maneira automatizada através do comando `bin/magento setup:upgrade`.
2. **Registro e Idempotência**: O patch deve ser registrado na tabela nativa `patch_list` do Magento, garantindo que não seja reexecutado indevidamente.
3. **Admin**: O atributo deve figurar no grupo correto do formulário de produto (**Product Details** / Detalhes do Produto) com rótulo em português (**"Selo do Produto"**).
4. **Frontend (PDP)**: O selo deve ser exibido com destaque na Product Detail Page quando preenchido/marcado. Caso o produto não possua o atributo preenchido (nulo ou não selecionado), a página deve renderizar normalmente sem quebrar e sem imprimir elementos HTML vazios no DOM.
5. **Justificativa de Arquitetura**: Documentação detalhada explicando as escolhas de **Escopo**, **Tipo de Entrada** e **Uso na Listagem**.

---

## 2. Decisões Arquiteturais e Justificativas

### 2.1. Por que escolhemos o Escopo `STORE` (`ScopedAttributeInterface::SCOPE_STORE`)?

O Magento suporta três níveis de escopo para atributos de catálogo (`catalog_eav_attribute.is_global`):
- `SCOPE_GLOBAL` (Global / 1)
- `SCOPE_WEBSITE` (Website / 2)
- `SCOPE_STORE` (Store View / 0)

#### Análise das Alternativas:
* **Global (`SCOPE_GLOBAL`)**: O valor definido seria idêntico em todas as lojas, websites e visões de loja. Esse escopo é indicado para propriedades físicas intrínsecas e universais do produto (como peso, dimensões ou código de barras EAN). Para um selo de produto, o escopo Global é excessivamente engessado: caso a empresa opere em múltiplos países ou idiomas, uma loja no Brasil precisa exibir "Sustentável" enquanto uma visão em inglês precisa exibir "Sustainable", ou uma certificação ecológica pode ser válida apenas em determinado mercado/país.
* **Website (`SCOPE_WEBSITE`)**: Permite variação por website, mas ainda impede que diferentes Store Views de um mesmo website (por exemplo, multi-idiomas como PT e EN) personalizem ou traduzam o selo.
* **Store View (`SCOPE_STORE`) — Escolha Adotada**:
  - **Flexibilidade Mercadológica**: Permite que cada Store View defina qual selo exibir ou até desative o selo para aquele canal de venda específico.
  - **Fallback Inteligente**: No Magento, o escopo `Store View` herda automaticamente o valor padrão configurado no nível `Default/All Store Views`, permitindo configuração única para toda a rede e permitindo sobrescritas apenas onde necessário.
  - **Internacionalização**: Facilita a tradução dos textos e opções por idioma da Store View.

---

### 2.2. Por que escolhemos o Tipo de Entrada `select` (Dropdown)?

O enunciado instruiu a *"escolher com cuidado o tipo de entrada"* para marcar produtos com um selo *(por exemplo, "Sustentável")*.

#### Comparação de Tipos:
1. **Texto Livre (`text` / `varchar`)**:
   - *Desvantagem*: Abre margem para erros humanos e inconsistências graves no catálogo (ex: um operador digita *"sustentavel"*, outro *"Sustentável"*, outro *"Sustentavel"* e outro *"Eco"*). Isso quebra o design do selo no frontend, impede filtros padronizados na vitrine e dificulta relatórios.
2. **Booleano (`boolean` / Sim-Não)**:
   - *Desvantagem*: Atende apenas a uma flag binária fixa (ex: apenas "Sustentável"). Se amanhã o lojista desejar criar produtos com selo *"Vegano"* ou *"Artesanal"*, seria necessário criar uma nova coluna/atributo para cada selo (`is_vegan`, `is_artisan`, etc.), gerando proliferação desnecessária de atributos no banco EAV.
3. **Seleção (`select` / Dropdown com `Table` Source) — Escolha Adotada**:
   - **Extensibilidade**: Permite selecionar dentre um rol curado de selos (ex: *"Sustentável"*, *"Eco-Friendly"*, *"Vegano"*, *"Artesanal"*), com suporte a novos selos sem necessidade de migrações estruturais.
   - **Controle de Integridade**: Garante que o lojista selecione apenas opções homologadas.
   - **Caso Não Preenchido**: Por padrão, o dropdown permite o valor vazio (`-- Selecione --`), atendendo perfeitamente ao requisito *"respeitando o caso de o produto não ter o atributo preenchido"*.

---

### 2.3. Por que definimos `used_in_product_listing = true`?

- Atributos com `used_in_product_listing = true` são automaticamente carregados nas coleções de catálogo (`\Magento\Catalog\Model\ResourceModel\Product\Collection`) utilizadas nas páginas de categoria (PLP), busca e vitrines de produtos relacionados.
- Em bancos com catálogo EAV, isso evita o problema de **N+1 queries** caso a loja queira futuramente exibir os selos nos cards de produto da listagem.
- O atributo também foi indexado para os grids do painel administrativo (`is_used_in_grid = true`, `is_visible_in_grid = true`, `is_filterable_in_grid = true`), permitindo que a equipe de e-commerce filtre rapidamente todos os produtos sustentáveis no grid de produtos do admin.

---

## 3. Estrutura do Módulo

```text
app/code/Webjump/ProductBadge/
├── README.md
├── Setup/
│   └── Patch/
│       └── Data/
│           └── AddProductBadgeAttribute.php   # Data Patch que cria o atributo e opções
├── ViewModel/
│   └── Badge.php                              # ViewModel para leitura segura do selo
├── etc/
│   └── module.xml                             # Declaração do módulo com sequences
├── registration.php                           # Registro do componente no Magento
└── view/
    └── frontend/
        ├── layout/
        │   └── catalog_product_view.xml       # Inserção do bloco na PDP antes do título
        ├── templates/
        │   └── product/
        │       └── view/
        │           └── badge.phtml            # Template com escape e condicional de exibição
        └── web/
            └── css/
                └── source/
                    └── _module.less           # Estilização moderna (pílula, ícone SVG, gradiente)
```

---

## 4. Evidências de Atendimento aos Critérios de Aceite

### Critério 1: O atributo é criado ao rodar `setup:upgrade` em uma base limpa
O Data Patch `AddProductBadgeAttribute` implementa `DataPatchInterface` e `PatchRevertableInterface`. Ao rodar:
```bash
bin/magento setup:upgrade
```
O módulo é instalado e o patch adiciona o atributo `product_badge` a todos os conjuntos de atributos (`attribute sets`) existentes no catálogo, associado ao grupo `Product Details`.

### Critério 2: Aparece no admin, no grupo correto, com o rótulo em português
Consulta na base de dados confirmando configuração:
```sql
SELECT ea.attribute_code, ea.frontend_label, cea.is_global, cea.used_in_product_listing, eag.attribute_group_name
FROM eav_attribute ea
JOIN catalog_eav_attribute cea ON ea.attribute_id = cea.attribute_id
JOIN eav_entity_attribute eea ON ea.attribute_id = eea.attribute_id
JOIN eav_attribute_group eag ON eea.attribute_group_id = eag.attribute_group_id
WHERE ea.attribute_code = 'product_badge';
```
**Resultado:**
- `attribute_code`: `product_badge`
- `frontend_label`: `Selo do Produto` (em português)
- `attribute_group_name`: `Product Details` (grupo principal no admin)
- `is_global`: `0` (`SCOPE_STORE`)
- `used_in_product_listing`: `1` (`true`)

### Critério 3: O patch está registrado na tabela `patch_list`
Consulta na base de dados confirmando o registro:
```sql
SELECT patch_id, patch_name FROM patch_list WHERE patch_name LIKE '%ProductBadge%';
```
**Resultado:**
| patch_id | patch_name |
| :--- | :--- |
| `204` | `Webjump\ProductBadge\Setup\Patch\Data\AddProductBadgeAttribute` |

### Critério 4: O selo aparece na página do produto quando marcado, e nada quebra quando não está
- **Quando Marcado ("Sustentável")**:
  - Testado no produto `CAM-BAS-001` (`/camisa-basica-de-algod-o.html`).
  - O elemento `<div class="product-badge-wrapper">` é renderizado imediatamente acima do título do produto com a classe `.product-badge--sustentavel`, exibindo o ícone de folha/escudo SVG e o texto **"Sustentável"**.
- **Quando Não Preenchido / Vazio**:
  - Testado no produto `24-MB01` (`/joust-duffle-bag.html`).
  - A página retorna HTTP 200 normalmente. O template verifica `hasBadge()` via ViewModel e interrompe a execução com `return;`, sem gerar qualquer elemento ou tag vazia no DOM.

### Critério 5: Qualidade de Código e Segurança
1. **Regra estrita de integridade do core**: Nenhuma alteração foi realizada em `vendor/` (script `check-vendor-changes.sh --working` validado com sucesso).
2. **Padrões de Código**: Aprovado sem erros pelo PHP CodeSniffer oficial do Magento (`phpcs --standard=Magento2`).
3. **Escapamento e Segurança**: Utilização estrita de `$escaper->escapeHtml()` e `$escaper->escapeHtmlAttr()` no template `.phtml`.

---

## 5. Como Testar Manualmente

1. **Acessar o Admin do Magento**:
   - Vá para **Catalog > Products**.
   - Abra qualquer produto para edição (ex: `Camiseta Básica Algodão` - SKU `CAM-BAS-001`).
   - Na seção **Product Details**, localize o campo **Selo do Produto**.
   - Altere para **"Sustentável"** (ou outra opção como "Eco-Friendly", "Vegano", "Artesanal") e clique em **Save**.
2. **Visualizar na Loja**:
   - Acesse a página do produto na loja: `https://magento.test/camisa-basica-de-algod-o.html`.
   - Observe o selo verde estilizado em formato de pílula exibido acima do título do produto.
3. **Testar Produto Não Preenchido**:
   - Acesse qualquer produto com o campo não preenchido: `https://magento.test/joust-duffle-bag.html`.
   - Confirme que a página carrega perfeitamente sem o selo e sem espaço vazio.

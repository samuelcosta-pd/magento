# Webjump_Samuel - Módulo Reformulado (Desafio Extra / 13.3)

Módulo de vitrine de produtos de baixo estoque na Home Page com gerenciamento via Admin do Magento e Carrossel Dinâmico.

---

## 1. Objetivo do Módulo Reformulado

Esta versão estende a implementação original do módulo `Webjump_Samuel`, agregando **capacidades administrativas**, **reordenação de catálogo** e **apresentação de interface**:

* **Gerenciamento pelo Lojista no Admin (Atividade 13.3)**: Criação de campos customizados em *Stores > Configuration*, permitindo que o lojista altere título, subtítulo, quantidade de produtos e SKUs prioritários sem tocar em uma linha de código.
* **Valores Padrão para campos sem valor**: Fallbacks automáticos para títulos e limites de produtos; o bloco nunca quebra caso os campos do Admin estejam vazios.
* **Carrossel Dinâmico de Produtos**: Transição automática de layout:
  * Quando a quantidade de produtos for $\le 4$, o bloco renderiza em **Grid responsivo**.
  * Quando a quantidade for $> 4$, o bloco é automaticamente transformado em um **Carrossel deslizante interativo**, com paginação suave e setas para navegação.
* **Destaque de SKUs Estratégicos**: Possibilidade de informar SKUs no Admin para que itens prioritários encabecem a vitrine, mesmo quando houver outros produtos com menor estoque.
* **Total Conformidade com Boas Práticas**: Lógica de consulta e parametrização 100% contida no **ViewModel**, saídas com `$escaper`, e JavaScript modularizado via **RequireJS / AMD** compatível com o Magento.

---

## 2. Estrutura de Diretórios e Arquivos

```text
app/code/Webjump/Samuel/
├── Block/
│   └── HomeBlock.php
├── etc/
│   ├── adminhtml/
│   │   └── system.xml               # Nova seção e campos em Stores > Configuration
│   └── module.xml                   # Declaração do módulo e sequência de carga
├── registration.php                 # Registro do módulo no Magento
├── ViewModel/
│   └── Home.php                     # Regras de negócio, leitura de config e ordenação
├── view/
│   └── frontend/
│       ├── layout/
│       │   └── cms_index_index.xml   # Injeção do bloco na Home Page
│       ├── templates/
│       │   └── home.phtml           # Template híbrido (Grid <= 4 e Carrossel > 4)
│       └── web/
│           ├── css/
│           │   └── source/
│           │       └── _module.less # Estilização completa do Grid e do Carrossel
│           └── js/
│               └── webjump-samuel-carousel.js # Lógica JS do carrossel (pixels/resize)
├── README.md                        # Documentação da atividade original (13.1)
└── README-desafio-extra.md          # Documentação da versão reformulada
```

### Detalhamento das Novas Implementações

| Diretório / Arquivo | Responsabilidade na Versão Reformulada |
| :--- | :--- |
| [`etc/adminhtml/system.xml`](etc/adminhtml/system.xml) | Cria a aba própria no menu lateral (*Stores > Configuration > Webjump Samuel > Configurações do Bloco*), com os campos de texto, a lista de quantidade (select: 2, 4, 6, 8, 10) e o campo de SKUs em destaque. |
| [`ViewModel/Home.php`](ViewModel/Home.php) | Injeta `ScopeConfigInterface` para recuperar as configurações do Admin. Expõe os métodos `getSectionTitle()`, `getSectionSubtitle()`, `getProductLimit()`, `getFeaturedSkus()` e gerencia a ordenação prioritária em `prioritizeFeaturedSkus()`. |
| [`view/frontend/templates/home.phtml`](view/frontend/templates/home.phtml) | Avalia a quantidade de produtos retornados (`$hasCarousel = $totalProducts > 4`). Renderiza a estrutura de trilha e botões do carrossel inicializados via `x-magento-init`, ou fallback para grid simples. |
| [`view/frontend/web/js/webjump-samuel-carousel.js`](view/frontend/web/js/webjump-samuel-carousel.js) | Componente RequireJS que calcula e seta a largura de cada card em pixels baseando-se no `offsetWidth` do container visível, controla a visibilidade das setas e recalcula dimensões dinamicamente no redimensionamento da tela. |
| [`view/frontend/web/css/source/_module.less`](view/frontend/web/css/source/_module.less) | Define estilos da trilha horizontal com `display: flex !important; flex-wrap: nowrap !important; width: max-content;`, botões circulares de navegação com estados de hover/focus e regras responsivas. |

---

## 3. Decisões Arquiteturais

### 3.1 Gerenciamento via Admin (`system.xml` + `ScopeConfigInterface`)
* Os campos foram declarados no escopo global e por website/store view (`showInDefault="1"`, `showInWebsite="1"`, `showInStore="1"`).
* O campo `product_limit` utiliza `<type>select</type>` para restringir as opções a números pares válidos (2, 4, 6, 8, 10), prevenindo inconsistências visuais no layout.
* O ViewModel foi desenhado com constantes de fallback:
  ```php
  private const DEFAULT_TITLE    = 'Últimas Unidades em Estoque';
  private const DEFAULT_SUBTITLE = 'Produtos esgotando. Aproveite as ofertas antes que zerem os estoques!';
  private const DEFAULT_LIMIT    = 4;
  ```
  Se o lojista apagar os valores no Admin ou deixá-los em branco, o bloco preserva seu comportamento e estética ideais.

### 3.2 Eliminação do Comportamento de Quebra de Linha do Carrossel
Durante a criação de um carrossel flexbox em Magento, containers com `calc(25% - 15px)` tendem a gerar dependências circulares com o elemento pai se este não possuir largura rígida. Além disso, classes residuais de grid (como `.products-grid`) forçam subdivisões em linhas.

A solução definitiva adotada combinou:
1. **Separação de classes**: O container da trilha do carrossel usa exclusivamente `.carousel-track`, sem herdar `.products-grid`.
2. **Cálculo de largura em JavaScript**: O script calcula a largura exata em pixels de cada card no momento do carregamento:
   $$\text{cardWidth} = \frac{\text{wrapperWidth} - (\text{visibleCount} - 1) \times \text{gap}}{\text{visibleCount}}$$
3. **Compensação exata de deslocamento**: A translação horizontal (`translateX`) leva em consideração a largura da janela somada ao espaçamento (`gap`), alinhando os produtos perfeitamente na borda esquerda da vitrine a cada clique:
   $$\text{offset} = \text{currentPage} \times (\text{wrapperWidth} + \text{gap})$$

---

## 4. Critérios de Aceite Atendidos

| Critério | Status | Implementação |
| :--- | :---: | :--- |
| **Seção própria no Admin em Stores > Configuration** | Concluído | Criada em `system.xml` sob `webjump_samuel/general`. |
| **Campos editáveis no Admin** | Concluído | Título, Subtítulo, Quantidade de Produtos e SKUs destacados. |
| **Alterações no Admin refletem na loja** | Concluído | ViewModel lê via `ScopeConfigInterface` e injeta no template. |
| **Valores padrão configurados (sem quebras com campo vazio)** | Concluído | Fallback nos métodos `getSectionTitle()`, `getSectionSubtitle()` e `getProductLimit()`. |
| **Carrossel ativado quando produtos > 4** | Concluído | Template bifurca exibição para carrossel interativo se total de itens > 4. |
| **Sem quebra de linha visual para o 5º item ou superior** | Concluído | Trilha flex horizontal contínua com largura de cards em pixels via JS. |
| **Navegação com setas ocultas contextualmente** | Concluído | Seta esquerda oculta na pág. 0; seta direita oculta na última página. |
| **Priorização de SKUs destacados** | Concluído | Método `prioritizeFeaturedSkus()` posiciona itens definidos no Admin na frente. |
| **Responsividade mantida** | Concluído | Breakpoints no LESS e recálculo dinâmico no JS para mobile e tablet. |

---

## 5. Procedimentos de Validação e Teste

### 1. Limpeza de Caches e Pré-processamento
Após modificar arquivos de configuração e estáticos:
```bash
bin/magento cache:flush
```

### 2. Configurar o Bloco pelo Painel Administrativo
1. Acesse o Admin do Magento: `https://magento.test/admin`
2. Navegue até: **Stores > Configuration > Webjump Samuel > Configurações do Bloco**
3. Altere os campos:
   * **Título do bloco**: Ex: *"Ofertas Relâmpago de Fim de Estoque"*
   * **Subtítulo do bloco**: Ex: *"Aproveite os últimos itens disponíveis no depósito!"*
   * **Quantidade de produtos**: Selecione `6` ou `8` (para acionar o carrossel)
   * **SKUs em destaque**: Insira um SKU desejado (ex: `24-WG01`)
4. Clique em **Save Config**.
5. Limpe o cache de configuração: `bin/magento cache:flush`.

### 3. Validar no Frontend
1. Acesse a Home Page: `https://magento.test/`
2. Verifique se os novos textos e a quantidade de produtos configurada aparecem no bloco.
3. Observe que com mais de 4 produtos as setas de navegação estarão presentes.
4. Clique na seta `>` e certifique-se de que os produtos deslizam suavemente sem quebrar para uma segunda linha.

---

## 6. Evidências de Sucesso e Critérios de Aceite

### [x] 1. Existe uma seção nova em Stores > Configuration com os campos customizados
* **Validação:** Arquivo [`system.xml`](etc/adminhtml/system.xml) registrado na aba `general` com o id `webjump_samuel`. Os campos de título, subtítulo, seletor de limite de produtos e SKUs destacados aparecem disponíveis para o lojista.
* **O que comprovo no print:**
  * Tela do Admin do Magento no menu lateral **Stores > Configuration > General > Webjump Samuel**.
  * Exibição clara dos 4 campos: *Título do bloco*, *Subtítulo do bloco*, *Quantidade de produtos* e *SKUs em destaque*.
* **Comprovação Visual:**
> <img width="1496" height="866" alt="image" src="https://github.com/user-attachments/assets/804892c9-43b3-4dfe-b40b-f2d0bdb91810" />

### [x] 2. Alteração de valores no Admin altera os textos e limites na loja
* **Validação:** Ao preencher valores customizados no Admin e salvar a configuração, o ViewModel lê os dados via `ScopeConfigInterface` e renderiza no template da Home Page após a limpeza de cache.
* **O que comprovo no print:**
  * **Print A (Admin):** Formulário preenchido com títulos e subtítulos personalizados e a mensagem de sucesso verde *"You saved the configuration."*
  * **Print B (Frontend):** Home Page exibindo o bloco com os textos customizados preenchidos no Admin, comprovando o dinamismo.
* **Comprovação Visual:**
> <img width="3339" height="1531" alt="image" src="https://github.com/user-attachments/assets/7b73fb9c-b452-47a3-aa67-b62a291c9c12" />


### [x] 3. O valor tem padrão definido e o bloco não quebra se o campo estiver vazio
* **Validação:** Métodos `getSectionTitle()`, `getSectionSubtitle()` e `getProductLimit()` implementados no [`Home.php`](ViewModel/Home.php) com operadores ternários e constantes de fallback:
  ```php
  return trim((string) $value) ?: self::DEFAULT_TITLE;
  ```
* **O que comprovo no print:**
  * Bloco renderizando normalmente com o título *"Últimas Unidades em Estoque"* e subtítulo padrão mesmo com os campos do Admin salvos em branco.
* **Comprovação Visual:**
> <img width="3339" height="1531" alt="image" src="https://github.com/user-attachments/assets/96f14390-8112-4d83-ac42-4ce0b097466c" />

### [x] 4. Carrossel ativo e funcional para mais de 4 produtos (sem quebra de linha)
* **Validação:** Quando o limite de produtos configurado no Admin for superior a 4 (ex: 6 ou 8 itens), a vitrine é envelopada na estrutura `.carousel-wrapper`. O 5º item em diante permanece na mesma linha horizontal contínua e só é exibido ao navegar.
* **O que comprovo no print:**
  * **Print A (Sem carrossel):** Visualização inicial com os 4 produtos. As setas devem estar ocultas/inativas.
  * **Print B (Página 1 e 2 do Carrossel):** Após clicar na seta direita, os produtos seguintes (5º, 6º, etc.) aparecem perfeitamente alinhados na mesma linha horizontal. A seta esquerda agora deve estar visível.
* **Comprovação Visual:**
> **Print A**
> <img width="3150" height="792" alt="image" src="https://github.com/user-attachments/assets/f63c2362-6f8e-4f4c-85b1-355e1c93d40f" />
>
> **Print B**
> <img width="3122" height="1530" alt="image" src="https://github.com/user-attachments/assets/3b239ebd-6247-4c69-b51a-3896b4f33edf" />

### [x] 5. Priorização de SKUs configurados em destaque
* **Validação:** Ao cadastrar um ou mais SKUs no campo *SKUs em destaque*, o método `prioritizeFeaturedSkus()` do ViewModel reorganiza a lista para que esses itens apareçam nas primeiras posições da vitrine.
* **O que comprovo no print:**
  * O produto correspondente ao SKU informado no Admin posicionado como o primeiro card da esquerda no bloco da Home.
* **Comprovação Visual:**
> <img width="2914" height="669" alt="image" src="https://github.com/user-attachments/assets/cc75e62c-5718-4a23-b000-7ab5466082fb" />
> <img width="2843" height="668" alt="image" src="https://github.com/user-attachments/assets/94cd6a0a-8160-4e7a-a0ae-69e29a41c07a" />

### [x] 6. Responsividade e adaptação do Carrossel em telas menores
* **Validação:** Media queries no `_module.less` e tratamento dinâmico no `webjump-samuel-carousel.js` adaptam a quantidade de cards visíveis por página (1 ou 2 cards) e reduzem dimensões de botões e espaçamentos em resoluções mobile e tablet.
* **O que comprovo no print:**
  * Exibição do bloco emulando dispositivo móvel ou tela estreita (ex: DevTools em largura $\le 768px$), demonstrando cards proporcionais e botões perfeitamente operáveis.
* **Comprovação Visual:**
> <img width="1590" height="1535" alt="image" src="https://github.com/user-attachments/assets/45bd0456-a9e0-469f-b14b-5a2ed02731de" />

---

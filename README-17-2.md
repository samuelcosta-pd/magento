# Desafio 17.2: Modo Assombrado e Minicart (Tema Noite Assombrada)

Documentação técnica, justificativa arquitetural, fluxo de inicialização via RequireJS, mixins Knockout.js e guia cirúrgico de evidências de sucesso do **Desafio 17.2** (Sprint 8 | Semana 17 — Comportamento e Autonomia) no tema [`Webjump/noite-assombrada`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada).

---

## 1. Critérios de Aceite Atendidos

| Critério de Aceite | Status | Detalhamento da Implementação |
|---|:---:|---|
| **O modo liga e desliga, e a escolha continua valendo ao navegar para outra página** | [x] Atendido | O componente [`Magento_Theme/js/haunted-mode.js`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada/Magento_Theme/web/js/haunted-mode.js) gerencia a alternância do estado. A escolha do usuário é persistida no `localStorage` sob a chave `noite_assombrada_mode`. Ao navegar entre páginas, o script carregado globalmente via seção `deps` do RequireJS lê imediatamente o valor persistido e reaplica o modo sem intervenção manual. |
| **A troca é por classe no HTML, não por recarregar a página** | [x] Atendido | A alternância é realizada cirurgicamente manipulando a classe `haunted-mode` diretamente no elemento raiz (`document.documentElement` / `<html>`) e no `<body>` via JavaScript (`classList.toggle('haunted-mode')`). Não ocorre nenhum refresh de página (`location.reload()` não é invocado), proporcionando transição suave via CSS (`transition: background 0.35s ease`). |
| **O minicart foi alterado por mixin, com `this._super()` preservado** | [x] Atendido | Declarado em [`requirejs-config.js`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada/requirejs-config.js) através da configuração `config.mixins['Magento_Checkout/js/view/minicart']`. O arquivo [`Magento_Checkout/js/view/minicart-mixin.js`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada/Magento_Checkout/web/js/view/minicart-mixin.js) estende o protótipo original invocando expressamente `this._super()` no método `initialize`, garantindo a integridade dos dados e da lógica do core. |
| **A mensagem do minicart muda conforme a quantidade de itens** | [x] Atendido | Foi registrado um `ko.computed` reativo denominado `hauntedCartMessage`. Ele observa `this.getCartParam('summary_count')` do customer-data. Conforme o número de itens varia (0 itens = caldeirão vazio/frio; 1 item = ritual iniciado; 2 a 4 itens = poção ganhando força; 5+ itens = poção prestes a transbordar), a mensagem se adapta reativamente em tempo real no template. |
| **O carrinho continua funcionando normalmente: adicionar, remover e atualizar quantidade** | [x] Atendido | Como o mixin preservou o ciclo de vida e os métodos nativos (`this._super()`, `closeMinicart`, `getCartItems`, `initSidebar`), todas as operações assíncronas do carrinho (adicionar via PDP/PLP, alterar quantidade no input e exclusão com diálogo modal de confirmação) continuam operando normalmente sem efeitos colaterais. |
| **`README` explica por que mixin e não map** | [x] Atendido | Seção arquitetural dedicada (Seção 2.1) detalhando tecnicamente as diferenças entre `mixins` (padrão decorator/interceptor compatível com múltiplos módulos e upgrades) e `map` (substituição cega/destrutiva monousuário, análoga a preferências/rewrites legados). |

---

## 2. Decisões Arquiteturais e Padrões Magento 2

### 2.1. Por que Mixin e não Map?

A escolha entre `mixin` e `map` no ecossistema JavaScript do Magento 2 é análoga à escolha entre **Plugins (Interceptors)** e **Preferences** no ecossistema PHP.

| Característica | RequireJS `mixins` (Adotado) | RequireJS `map` (Proibido no Desafio) |
|---|---|---|
| **Padrão de Projeto** | **Decorator / Extension:** Envolve o componente original, interceptando ou adicionando comportamentos sem substituir o arquivo base. | **Replacement / Monkey Patch:** Força o RequireJS a carregar um arquivo alternativo no lugar do arquivo original em toda a aplicação. |
| **Composabilidade** | **Alta:** Múltiplos módulos e temas podem aplicar mixins sobre o mesmo componente `Magento_Checkout/js/view/minicart` em cadeia ordenada, sem que um anule o outro. | **Nula / Conflito Direto:** Apenas um arquivo pode vencer o mapeamento do RequireJS. Se dois módulos mapearem `minicart.js`, o último sobrescreve o primeiro e quebra o site. |
| **Preservação do Core (`this._super()`)** | **Total:** Ao estender o componente (`target.extend`), o mixin chama `this._super()` para invocar os métodos nativos do Magento, mantendo inicializações, eventos e dependências intactos. | **Risco Elevado:** Exige duplicar centenas de linhas de código original do core. Se o Magento atualizar o arquivo na próxima versão (ex: 2.4.9), a cópia local ficará obsoleta (*drift* de código). |
| **Segurança e Upgrades** | Compatível com atualizações e patches de segurança do Magento (`pub/static` e `vendor/`). | Frágil: quebra silenciosamente se métodos privados ou assinaturas do core forem alterados em atualizações de versão. |

> **Conclusão:** O uso de `mixins` preserva a extensibilidade limpa e cooperativa do Magento 2, garantindo que o tema Noite Assombrada acrescente comportamento sem se apossar do módulo nem degradar a manutenibilidade da loja.

---

### 2.2. Carregamento Global via `deps` no `requirejs-config.js`

O interruptor de Modo Assombrado precisa existir e funcionar em todas as páginas da loja (Home, PLP, PDP, Carrinho, Checkout, Minha Conta). Em vez de amarrar a inicialização a um container de template específico usando `data-mage-init` ou `<script type="text/x-magento-init">` repetido em diversos arquivos PHTML, utilizamos a seção **`deps`** no arquivo [`requirejs-config.js`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada/requirejs-config.js):

```javascript
var config = {
    deps: [
        'Magento_Theme/js/haunted-mode'
    ],
    config: {
        mixins: {
            'Magento_Checkout/js/view/minicart': {
                'Magento_Checkout/js/view/minicart-mixin': true
            }
        }
    }
};
```

- **Execução Automática e Universal:** Qualquer página que carregue o RequireJS (ou seja, 100% das páginas da store view) executa automaticamente o script `Magento_Theme/js/haunted-mode.js`.
- **Anti-FOUC (Flash of Unstyled Content):** O script lê o `localStorage` imediatamente na sua inicialização, antes mesmo do evento `ready` do DOM, garantindo que a classe `haunted-mode` seja injetada no elemento raiz `<html>` de forma imperceptível ao usuário.

---

### 2.3. Transição de Estilos por Classe Raiz (`html.haunted-mode`)

Para cumprir a exigência de que *"a troca é por classe no HTML, não por recarregar a página"*, todas as regras de visual ultra-escuro foram agrupadas sob o escopo `.haunted-mode` / `html.haunted-mode` no arquivo [`_extend.less`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada/web/css/source/_extend.less):
- **Cores do Modo Assombrado:** O fundo da página transita para um breu sobrenatural (`#090412`), os containers ganham bordas espectrais em verde bruxa neon (`#7CFF6B`) com sombras volumétricas (`box-shadow: 0 0 16px rgba(124, 255, 107, 0.25)`), e os textos e badges adquirem contraste reforçado.
- **Transição Suave:** Propriedades CSS `transition: background-color 0.35s ease, color 0.35s ease, border-color 0.35s ease` proporcionam uma experiência visual fluida no momento do clique, sem recarregamento de página.

---

### 2.4. Reatividade do Minicart com `ko.computed` e `customer-data`

O componente nativo do minicart (`Magento_Checkout/js/view/minicart`) consome a seção `cart` do `customerData` (`Magento_Customer/js/customer-data`). No nosso mixin:
1. Registramos `this.hauntedCartMessage = ko.computed(...)` observando `self.getCartParam('summary_count')`.
2. Como `getCartParam` acessa o observable do carrinho interno (`this.cart['summary_count']`), qualquer mutação no carrinho (adicionar produto via AJAX, alterar quantidade ou remover item) dispara a reavaliação imediata da função computada.
3. No template reativo [`content.html`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada/Magento_Checkout/web/template/minicart/content.html), o container do caldeirão exibe a mensagem temática através do binding `data-bind="text: hauntedCartMessage"`.

---

## 3. Matriz de Arquivos do Desafio

```text
src/app/design/frontend/Webjump/noite-assombrada/
├── requirejs-config.js                                               # Injeção global deps e registro do mixin minicart
├── i18n/
│   └── pt_BR.csv                                                     # Dicionário com termos e mensagens do minicart e toggle
├── Magento_Theme/
│   ├── layout/
│   │   └── default.xml                                               # Bloco do interruptor injetado no header.panel
│   ├── templates/html/header/
│   │   └── haunted-mode-toggle.phtml                                 # Template HTML do interruptor temático
│   └── web/js/
│       └── haunted-mode.js                                           # Script global: leitura de localStorage e alternância de classe
├── Magento_Checkout/
│   └── web/
│       ├── js/view/
│       │   └── minicart-mixin.js                                     # Mixin estendendo minicart com ko.computed
│       └── template/minicart/
│           └── content.html                                          # Template do minicart com banner temático do caldeirão
└── web/css/source/
    └── _extend.less                                                  # Estilos do Modo Assombrado (html.haunted-mode) e minicart
```

---

## 4. Dicionário de Tradução (`pt_BR.csv`)

| Chave Original | Tradução no Tema Noite Assombrada |
|---|---|
| `"Haunted Mode"` | `"Modo Assombrado"` |
| `"Toggle Haunted Mode"` | `"Alternar Modo Assombrado"` |
| `"Your cauldron is cold and empty... No spells added yet! 👻"` | `"Seu caldeirão está vazio e frio... Nenhum feitiço adicionado ainda! 👻"` |
| `"1 item bubbling in the cauldron... The ritual has begun! 🎃"` | `"1 item borbulhando no caldeirão... O ritual começou! 🎃"` |
| `"%1 enchanted items in the cauldron... The potion is gaining power! 🧪"` | `"%1 itens encantados no caldeirão... A poção está ganhando força! 🧪"` |
| `"%1 items in the cauldron! Beware, the spells might overflow! 🧙‍♀️⚡"` | `"%1 itens no caldeirão! Cuidado, a poção pode transbordar feitiços! 🧙‍♀️⚡"` |

---

## 5. Evidências de Sucesso

Esta seção reúne os prints comprobatórios de cada critério de aceite do desafio **17.2 - Modo assombrado e minicart**.

---

### 5.1. Critério 1: O modo liga e desliga, e a escolha continua valendo ao navegar para outra página

#### Print 1.1 — No Storefront (Modo Assombrado Ativo e Fundo Ultra Escuro)
- **Onde acessar:** Storefront em qualquer página (ex: `https://magento.test/`).
- **O que comprova:** O interruptor no cabeçalho (*"Modo Assombrado"*) em estado ativo/ligado, o tema aplicando a paleta ultra escura (breu e tons ectoplasmáticos) e o elemento `<html>` com a classe `haunted-mode`.

> <img width="1820" height="920" alt="image" src="https://github.com/user-attachments/assets/print-1-1-modo-assombrado-ativo.png" />

#### Print 1.2 — No Storefront / DevTools (Persistência no `localStorage` após Navegação)
- **Onde acessar:** Navegar para outra página (ex: de `https://magento.test/` para `https://magento.test/gear/bags.html` ou `/customer/account/login/`) e abrir o DevTools (F12 > Aba *Application* > *Local Storage* > `https://magento.test`).
- **O que comprova:** A chave `noite_assombrada_mode` com o valor `"true"` persistida no navegador e a nova página já carregando com a classe `haunted-mode` ativa no HTML sem qualquer intervenção do usuário.

> <img width="1820" height="920" alt="image" src="https://github.com/user-attachments/assets/print-1-2-localstorage-persistido.png" />

#### Print 1.3 — No Código / IDE (Script `haunted-mode.js` e Leitura de Storage)
- **Arquivo:** [`src/app/design/frontend/Webjump/noite-assombrada/Magento_Theme/web/js/haunted-mode.js`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada/Magento_Theme/web/js/haunted-mode.js)
- **O que comprova:** Implementação da leitura `localStorage.getItem(STORAGE_KEY)`, gravação `localStorage.setItem(STORAGE_KEY, ...)` e inicialização automática na carga da página.

> <img width="1820" height="920" alt="image" src="https://github.com/user-attachments/assets/print-1-3-codigo-haunted-mode-js.png" />

---

### 5.2. Critério 2: A troca é por classe no HTML, não por recarregar a página

#### Print 2.1 — No Storefront / DevTools (Inspeção de Elementos e Rede sem Refresh)
- **Onde acessar:** Storefront em `https://magento.test/`, com a aba *Elements* e a aba *Network* visíveis no DevTools ao clicar no interruptor.
- **O que comprova:** A classe `haunted-mode` sendo adicionada/removida instantaneamente na tag `<html>`/`<body>` no DOM e a aba *Network* comprovando ausência de requisição de recarregamento de documento (`document` request não disparado).

> <img width="1820" height="920" alt="image" src="https://github.com/user-attachments/assets/print-2-1-troca-classe-sem-refresh.png" />

#### Print 2.2 — No Código / IDE (Manipulação Direta de Classes e Prevenção de Reload)
- **Arquivo:** [`src/app/design/frontend/Webjump/noite-assombrada/Magento_Theme/web/js/haunted-mode.js`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada/Magento_Theme/web/js/haunted-mode.js)
- **O que comprova:** Funções `classList.add(CLASS_NAME)` e `classList.remove(CLASS_NAME)` ou `classList.toggle()` aplicadas no `document.documentElement` sem chamadas a `location.reload()`.

> <img width="1820" height="920" alt="image" src="https://github.com/user-attachments/assets/print-2-2-codigo-class-manipulation.png" />

---

### 5.3. Critério 3: O minicart foi alterado por mixin, com `this._super()` preservado

#### Print 3.1 — No Código / IDE (Declaração do Mixin no `requirejs-config.js`)
- **Arquivo:** [`src/app/design/frontend/Webjump/noite-assombrada/requirejs-config.js`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada/requirejs-config.js)
- **O que comprova:** Declaração explícita de `config.mixins['Magento_Checkout/js/view/minicart']` vinculada a `'Magento_Checkout/js/view/minicart-mixin': true`, sem uso de `map`.

> <img width="1820" height="920" alt="image" src="https://github.com/user-attachments/assets/print-3-1-requirejs-config-mixin.png" />

#### Print 3.2 — No Código / IDE (Implementação do Mixin com `this._super()`)
- **Arquivo:** [`src/app/design/frontend/Webjump/noite-assombrada/Magento_Checkout/web/js/view/minicart-mixin.js`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada/Magento_Checkout/web/js/view/minicart-mixin.js)
- **O que comprova:** A função mixin retornando `target.extend({ ... })` e a chamada obrigatória `this._super()` no método `initialize`.

> <img width="1820" height="920" alt="image" src="https://github.com/user-attachments/assets/print-3-2-codigo-minicart-mixin-super.png" />

---

### 5.4. Critério 4: A mensagem do minicart muda conforme a quantidade de itens

#### Print 4.1 — No Storefront (Minicart com 0 Itens — Caldeirão Vazio)
- **Onde acessar:** Clicar no ícone do minicart no cabeçalho com o carrinho vazio.
- **O que comprova:** O minicart exibindo a mensagem temática computada: *"Seu caldeirão está vazio e frio... Nenhum feitiço adicionado ainda! 👻"*.

> <img width="1820" height="920" alt="image" src="https://github.com/user-attachments/assets/print-4-1-minicart-vazio.png" />

#### Print 4.2 — No Storefront (Minicart com 1 Item — Ritual Iniciado)
- **Onde acessar:** Adicionar 1 produto ao carrinho e abrir o minicart no cabeçalho.
- **O que comprova:** O minicart exibindo a mensagem temática computada para 1 item: *"1 item borbulhando no caldeirão... O ritual começou! 🎃"*.

> <img width="1820" height="920" alt="image" src="https://github.com/user-attachments/assets/print-4-2-minicart-1-item.png" />

#### Print 4.3 — No Storefront (Minicart com Múltiplos Itens — Poção em Fervura)
- **Onde acessar:** Adicionar mais produtos (ex: 2 a 5 itens) ao carrinho e abrir o minicart.
- **O que comprova:** O minicart exibindo dinamicamente a mensagem reativa para múltiplos itens (ex: *"2 itens encantados no caldeirão... A poção está ganhando força! 🧪"* ou *"5 itens no caldeirão! Cuidado, a poção pode transbordar feitiços! 🧙‍♀️⚡"*).

> <img width="1820" height="920" alt="image" src="https://github.com/user-attachments/assets/print-4-3-minicart-multiplos-itens.png" />

#### Print 4.4 — No Código / IDE (`ko.computed` com as Mensagens do Caldeirão)
- **Arquivo:** [`src/app/design/frontend/Webjump/noite-assombrada/Magento_Checkout/web/js/view/minicart-mixin.js`](file:///home/samuel/Sites/magento/src/app/design/frontend/Webjump/noite-assombrada/Magento_Checkout/web/js/view/minicart-mixin.js)
- **O que comprova:** Estrutura condicional reativa dentro de `ko.computed` avaliando a quantidade `summary_count` e retornando as respectivas mensagens temáticas.

> <img width="1820" height="920" alt="image" src="https://github.com/user-attachments/assets/print-4-4-codigo-ko-computed-mensagens.png" />

---

### 5.5. Critério 5: O carrinho continua funcionando normalmente: adicionar, remover e atualizar quantidade

#### Print 5.1 — No Storefront (Atualização de Quantidade e Total no Minicart)
- **Onde acessar:** No dropdown do minicart, alterar a quantidade de um item no campo de texto e clicar no botão de atualizar (ou adicionar item pela vitrine).
- **O que comprova:** Quantidade e subtotal recalculados com sucesso via AJAX, e a mensagem do caldeirão atualizada reativamente sem travar a interface.

> <img width="1820" height="920" alt="image" src="https://github.com/user-attachments/assets/print-5-1-minicart-atualizar-quantidade.png" />

#### Print 5.2 — No Storefront (Exclusão de Item com Modal de Confirmação Nativo)
- **Onde acessar:** Clicar no ícone de lixeira / excluir item no minicart.
- **O que comprova:** Exibição do modal nativo de confirmação (*"Are you sure you would like to remove this item..."*), remoção bem-sucedida do item e retorno seguro ao estado correspondente.

> <img width="1820" height="920" alt="image" src="https://github.com/user-attachments/assets/print-5-2-minicart-exclusao-modal.png" />

---

### 5.6. Critério 6: `README` explica por que mixin e não map

#### Print 6.1 — No Código / IDE / Markdown (Seção Justificativa Arquitetural no `README-17-2.md`)
- **Arquivo:** [`README-17-2.md`](file:///home/samuel/Sites/magento/README-17-2.md) (Seção 2.1)
- **O que comprova:** Explicação detalhada dos fundamentos de engenharia de software demonstrando por que o padrão `mixin` (decorator) foi escolhido em detrimento do `map` (substituição integral), garantindo desacoplamento e compatibilidade com o Magento core.

> <img width="1820" height="920" alt="image" src="https://github.com/user-attachments/assets/print-6-1-readme-justificativa-mixin-vs-map.png" />

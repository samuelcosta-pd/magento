# Ambiente Magento no Ar - README de Instalação(Passo a passo)
 
Este documento descreve o passo a passo seguido para colocar o ambiente Magento 2.4.8-p1 no ar com o **docker-magento** (Mark Shust), os problemas enfrentados e os ajustes de segurança realizados.
 
## Sumário
 
- [1. Objetivo](#1-objetivo)
- [2. Ambiente e pré-requisitos](#2-ambiente-e-pré-requisitos)
- [3. Diagnóstico inicial da máquina](#3-diagnóstico-inicial-da-máquina)
- [4. Passo a passo da instalação](#4-passo-a-passo-da-instalação)
- [5. Problemas enfrentados e soluções](#5-problemas-enfrentados-e-soluções)
- [6. Ajustes de segurança realizados](#6-ajustes-de-segurança-realizados)
- [7. Critérios de aceite](#7-critérios-de-aceite)
- [8. Evidências de sucesso](#8-evidências-de-sucesso)
---
 
## 1. Objetivo
 
Ter o Magento rodando localmente, com admin acessível e o ambiente em **modo developer**, operando a stack via linha de comando.
 
---
 
## 2. Ambiente e pré-requisitos
 
- Projeto: `~/Documentos/WEBJUMP/sites/magento`
- Ferramenta: **docker-magento** (Mark Shust)
- Versão do Magento: **2.4.8-p1**
- Chaves do Magento Marketplace já configuradas em `~/.composer/auth.json`:
  - `username` = Public Key
  - `password` = Private Key
---
 
## 3. Diagnóstico inicial da máquina
 
Antes da instalação, foi feito um diagnóstico da máquina (sistema, CPU, RAM, disco e ferramentas necessárias).
 
| Item | Resultado | Ação |
|---|---|---|
| RAM | 16 GB | Atende ao mínimo exigido |
| CPU | 4 CPUs (recomendado 6+, mínimo 2) | Nenhuma correção; atenção ao desempenho |
| Disco | 69 GB livres (mínimo 30 GB) | Nenhuma ação |
| Docker | 29.7.2, rodando | Nenhuma instalação |
| Docker Compose | v5.5.0 | Nenhuma instalação |
| Git | 2.43.0 | Nenhuma instalação |
| VS Code | 1.129.1 | Nenhuma instalação |
| `~/Sites/magento` | Não existia | Substituída por `~/Documentos/WEBJUMP/sites/magento` |
 
Antes de iniciar, também foi verificado se já não havia um Magento instalado em outro local:
 
```bash
find ~ -type f -path "*/bin/magento"
docker ps -a
```
 
---
 
## 4. Passo a passo da instalação
 
1. **Diretório do projeto** - foi criado o diretório `~/Documentos/WEBJUMP/sites/magento`.
2. **Script `docker-magento`** - foi baixado e executado `bin/download`.
   > ⚠️ `bin/download` apenas prepara a configuração (`compose.versions.yaml`); o código-fonte do Magento ainda não é baixado nessa etapa, por isso comandos como `deploy:mode:set` ainda não existem.
3. **Autenticação do Composer** - foi verificado o `~/.composer/auth.json`, confirmando as chaves do Marketplace.
4. **Ajuste do `compose.yaml`** - foi corrigido um erro de sintaxe (indentação e variável de ambiente incompleta) e validado com `docker compose config`.
5. **Healthcheck do OpenSearch** - foi ajustado o tempo e o número de tentativas do healthcheck no `compose.yaml`.
6. **Subida dos containers** - foi executado `docker compose up -d`.
7. **Download do código-fonte** - foi executado `bin/setup`, que dispara o `composer create-project` para trazer o código para `src/`.
8. **Estrutura de arquivos** - foi garantido que o código estivesse em `src/` e corretamente montado em `/var/www/html` no container.
9. **Domínio local** - foi adicionada a entrada `127.0.0.1 magento.test` no `/etc/hosts`.
10. **Configuração do Nginx** - foi criado o arquivo `src/nginx.conf` e corrigido o upstream `fastcgi_backend`.
11. **Validação via CLI**:
```bash
    bin/magento --version
    bin/magento setup:db:status
```
 
12. **Usuário administrador** - foi criado com `bin/magento admin:user:create`.
13. **Modo developer**:
```bash
    bin/magento deploy:mode:set developer
    bin/magento deploy:mode:show
```
 
14. **Acesso** - foi acessado o site em `https://magento.test` e o admin em `https://magento.test/admin`.
---
 
## 5. Problemas enfrentados e soluções
 
| Problema | Causa | Solução |
|---|---|---|
| OpenSearch `unhealthy` | Healthcheck rígido demais para o tempo real de inicialização | Foi aumentado o tempo/tentativas do healthcheck |
| `bin/magento` não encontrado | `bin/download` só prepara a configuração, sem baixar o código | Foi executado o download real do código via `bin/setup` |
| `possible container breakout detected` | Bug conhecido do Docker/runc: alteração de arquivos em bind mount (`src/`) por fora do container deixa o daemon com referência desatualizada do mount namespace | Foi feito `docker compose down` + `sudo systemctl restart docker` antes de tentar novamente |
| Falha silenciosa no download do Composer | Chaves de autenticação ausentes/incorretas | Foi verificado o `auth.json` (Public Key/Private Key) |
| `src/` esvaziada após `bin/setup` | O script faz uma limpeza interna antes de reinstalar, e a reinstalação falhava silenciosamente | Foi investigado o conteúdo do script `bin/setup` para identificar a causa |
| `chmod: cannot access 'bin/magento'` | Estrutura de `src/` não coincidia com os caminhos esperados pelos scripts em `bin/` | Foi garantida a montagem correta do código em `/var/www/html` |
| `OCI runtime exec failed: ... outside of container mount namespace` | Conflito entre diretório de trabalho do host e volumes do Docker | Resolvido ao confirmar que os comandos no container voltaram a funcionar |
| Domínio `magento.test` não resolvia | Faltava entrada no `/etc/hosts` | Foi adicionada a entrada `127.0.0.1 magento.test` |
| Nginx retornando `404` | Arquivo `src/nginx.conf` ausente | Foi adicionado o arquivo `nginx.conf` |
| Erro no upstream `fastcgi_backend` | Configuração incorreta no `nginx.conf` recém-adicionado | Foi corrigida a definição do upstream |
| `403 Forbidden` em `https://magento.test/` | Infraestrutura criada, mas instalação ainda incompleta | Resolvido ao concluir a instalação |
| Falha ao reexecutar `onelinesetup` | Diretório já continha arquivos de tentativa anterior | Foi removida a estrutura anterior e reexecutado em diretório limpo |
 
---
 
## 6. Ajustes de segurança realizados
 
### 6.1 Credenciais protegidas do Git (`env/*.env`)
 
Os arquivos de ambiente com credenciais reais (`db.env`, `magento.env`, `rabbitmq.env` etc.) foram adicionados ao `.gitignore` e deixaram de ser versionados.
 
Para cada arquivo foi criado um `.example` correspondente (ex.: `env/db.env.example`) com valores placeholder (`CHANGE_ME`), servindo de guia para novos desenvolvedores.
 
**Configuração em uma nova máquina:**
```bash
cp env/db.env.example env/db.env
cp env/magento.env.example env/magento.env
# repetir para os demais arquivos e preencher com os valores reais
```
 
### 6.2 Portas de serviços restritas ao loopback (`127.0.0.1`)
 
Os serviços que antes aceitavam conexões de qualquer IP da rede local passaram a aceitar apenas conexões da própria máquina, sem impacto funcional para a aplicação.
 
| Serviço | Porta | Mudança |
|---|---|---|
| MySQL (MariaDB) | 3306 | `0.0.0.0` → `127.0.0.1` |
| Redis / Valkey | 6379 | `0.0.0.0` → `127.0.0.1` |
| OpenSearch | 9200, 9300 | `0.0.0.0` → `127.0.0.1` |
| RabbitMQ (AMQP + UI) | 5672, 15672 | `0.0.0.0` → `127.0.0.1` |
 
Ferramentas externas como TablePlus, DBeaver e Redis Insight continuam funcionando normalmente via `127.0.0.1`.
 
---
 
## 7. Critérios de aceite
 
- [x] Site abrindo em `https://magento.test`
- [x] Admin acessível, com usuário criado por `bin/magento admin:user:create`
- [x] `bin/magento deploy:mode:show` retornando `developer`
- [x] README com o passo a passo e os problemas enfrentados (este documento)
- [x] Print do site e do admin abertos
---
 
## 8. Evidências de sucesso

### Print do site (`/admin`)
 
<img width="1460" height="737" alt="image" src="https://github.com/user-attachments/assets/c7489933-6662-4752-8eda-b4750dcdcde1" />

### Print do painel administrativo (`https://magento.test`)
 
<img width="1460" height="745" alt="image" src="https://github.com/user-attachments/assets/d8af9ec0-81e9-4f46-a0dc-a3642b054ff9" />

### Print do resultado de `bin/magento admin:user:create`
 
<img width="888" height="543" alt="image" src="https://github.com/user-attachments/assets/0e9e0768-9255-47e1-b554-f419ae467b12" />
<img width="1454" height="735" alt="image" src="https://github.com/user-attachments/assets/fa36269a-7107-4e9e-b742-3bd9dde91bb6" />

### Print do resultado de `bin/magento deploy:mode:show` e `bin/magento setup:db:status`
 
<img width="722" height="478" alt="image" src="https://github.com/user-attachments/assets/95242955-7cdc-49ec-a651-e8f05459ee5d" />

 

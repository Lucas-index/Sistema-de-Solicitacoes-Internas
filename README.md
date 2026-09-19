# Sistema de Solicitações Internas — README

Este é o guia principal do projeto. Ele explica como rodar as três partes do sistema (manualmente ou via Docker), os comandos do dia a dia, o que cada rota faz, e termina com uma história mostrando tudo em ação.

## Visão geral

O projeto tem três partes, cada uma numa pasta própria:

| Pasta | O que é | Porta |
|---|---|---|
| `api` | Backend principal em Laravel — autenticação, chamados, aprovações, SLA | `8000` |
| `python-service` | Serviço de relatórios em Python (FastAPI) — tempo médio, volume, recorrência | `8001` |
| `frontend` | Interface em React (Vite) — telas de login, chamados e painéis | `5173` |

Existem **dois jeitos de rodar o projeto**: manualmente (três terminais, um comando por serviço) ou via **Docker Compose** (um único comando sobe tudo, incluindo o banco de dados). Os dois modos convivem, cada um com seu uso ideal — veja a seção 1.3.

---

## 1. Como iniciar

### 1.1 Modo manual (bom para desenvolver e ver mudanças em tempo real)

Três abas de terminal abertas ao mesmo tempo:

**Terminal 1 — API (Laravel)**
```
cd api
php artisan serve
```
Fica no ar em `http://127.0.0.1:8000`.

**Terminal 2 — Relatórios (Python)**
```
cd python-service
venv\Scripts\activate
uvicorn main:app --reload --port 8001
```
Fica no ar em `http://127.0.0.1:8001`.

**Terminal 3 — Front-end (React)**
```
cd frontend
npm run dev
```
Fica no ar em `http://localhost:5173`.

Para desligar qualquer um dos três, clique no terminal correspondente e aperte `Ctrl+C`.

### 1.2 Modo Docker (bom para rodar tudo de uma vez, ou mostrar o projeto pronto)

Um único terminal, na **raiz do projeto** (onde está o `docker-compose.yml`, ao lado de `api`, `python-service` e `frontend`):

```
docker compose up --build
```

Esse comando sobe **quatro** containers de uma vez: banco MySQL, API, serviço Python e front-end (compilado e servido por nginx). Na primeira vez demora alguns minutos (baixa as imagens base e instala as dependências).

- Front-end: `http://localhost:5173`
- API: `http://localhost:8000`
- Serviço Python: `http://localhost:8001`
- Banco de dados (acessível de fora, se precisar): `localhost:3307`

Para rodar em segundo plano, sem prender o terminal:
```
docker compose up -d
```

Para derrubar tudo:
```
docker compose down
```

Para reconstruir só um serviço específico (depois de editar código dele):
```
docker compose up --build api
docker compose up --build python-service
docker compose up --build frontend
```

**Antes de usar pela primeira vez:**
1. Copie `.env.example` (da raiz) para `.env`, também na raiz — alimenta as senhas do banco e a chave do serviço Python usadas pelo Compose.
2. Tenha o Docker Desktop instalado e aberto (com "Engine running" no canto inferior esquerdo).

**Um detalhe importante:** dentro do Docker, o banco se chama `db` (não `127.0.0.1`) — os containers se enxergam pelo nome do serviço, não por endereço local. Isso já vem configurado no `docker-compose.yml`.

**O banco de dados do Docker é separado do banco local.** Se você já tinha usuários/setores/categorias de teste criados rodando no modo manual, eles **não existem** no banco do Docker (é um volume novo e vazio). Recrie as contas de teste (seção 6) e os dados básicos (setor, categoria) depois de subir pela primeira vez.

### 1.3 Qual modo usar quando

| Situação | Use |
|---|---|
| Codando e testando mudanças a toda hora | Modo manual — `npm run dev` atualiza a tela sozinho, `--reload` do uvicorn também |
| Rodar o projeto "pronto", mostrar pra alguém, simular produção | Modo Docker |
| Editou algo no front-end enquanto o Docker está rodando | Precisa `docker compose up --build frontend` — o Docker serve uma versão já compilada, não atualiza sozinho |

---

## 2. Comandos principais (cheat sheet)

### Laravel (`api`)

| Comando | Para que serve |
|---|---|
| `php artisan serve` | Sobe o servidor da API (modo manual) |
| `php artisan route:list` | Lista todas as rotas existentes |
| `php artisan route:list --path=solicitacoes` | Lista só as rotas de um assunto específico |
| `php artisan migrate` | Aplica migrations pendentes no banco |
| `php artisan migrate:fresh` | Apaga **todas** as tabelas e recria vazias (cuidado, perde todos os dados) |
| `php artisan tinker` | Console interativo para consultar/editar o banco direto pelo Eloquent |
| `php artisan make:model Nome -mcr` | Cria model + migration + controller de uma vez |
| `php artisan make:migration nome_da_migration` | Cria uma migration nova |
| `php artisan solicitacoes:verificar-sla` | Roda manualmente a verificação de chamados atrasados |
| `php artisan optimize:clear` | Limpa cache de rotas, config e views (use quando algo "não atualiza" mesmo após editar o código) |
| `composer dump-autoload` | Recarrega o mapeamento de classes do PHP |

### Python (`python-service`)

| Comando | Para que serve |
|---|---|
| `python -m venv venv` | Cria o ambiente virtual (só na primeira vez) |
| `venv\Scripts\activate` | Ativa o ambiente virtual |
| `pip install -r requirements.txt` | Instala as dependências |
| `uvicorn main:app --reload --port 8001` | Sobe o serviço de relatórios (modo manual) |

### Front-end (`frontend`)

| Comando | Para que serve |
|---|---|
| `npm install` | Instala as dependências |
| `npm run dev` | Sobe o servidor de desenvolvimento (modo manual) |
| `npm run build` | Gera a versão de produção (pasta `dist`) |

### Docker (raiz do projeto)

| Comando | Para que serve |
|---|---|
| `docker compose up --build` | Sobe os 4 containers, reconstruindo as imagens |
| `docker compose up -d` | Sobe em segundo plano, sem prender o terminal |
| `docker compose down` | Derruba todos os containers |
| `docker compose down -v` | Derruba tudo **e apaga o banco de dados** (útil pra recomeçar do zero) |
| `docker compose ps` | Lista os containers e o status de cada um |
| `docker compose logs api --tail=50` | Mostra as últimas 50 linhas de log de um serviço |
| `docker compose exec api tail -n 80 storage/logs/laravel.log` | Mostra o log de erro do Laravel de dentro do container |
| `docker compose exec api php artisan tinker` | Abre o Tinker de dentro do container |
| `docker compose exec api cat .env` | Confirma quais variáveis de ambiente o container está usando de fato |

---

## 3. Como verificar se uma requisição está indo pela rota certa

1. **Confira a URL exata.** Troque qualquer coisa entre chaves (como `{id}`) pelo número real do registro.
2. **Confira o método HTTP.** `GET` é para consultar, `POST` é para criar ou executar uma ação.
3. **Rode `php artisan route:list --path=<parte-da-url>`** para confirmar que a rota existe.
4. **Confira os headers:** `Accept: application/json` e `Authorization: Bearer {token}` (para a API); `x-api-key` (para o serviço Python).
5. **Use o Tinker** para ver o dado real no banco, sem passar pela API.
6. **No navegador, use o F12 (DevTools) → aba Network**, para ver exatamente qual chamada o front-end fez.
7. **Olhe o terminal (ou `docker compose logs`) de cada serviço.** Toda requisição que chega aparece ali.
8. **Rodando via Docker:** se o comportamento parecer "antigo" mesmo após editar código, o problema costuma ser cache de configuração do Laravel (`php artisan config:clear` dentro do container) ou uma imagem que não foi reconstruída (`docker compose up --build <serviço>`).

---

## 4. Dicionário de rotas — API (Laravel)

### Contas e login

| Rota | O que faz |
|---|---|
| `POST /api/register` | Cria uma conta nova (nome, e-mail, senha e papel: solicitante, aprovador, executor ou admin). Não existe tela para isso — é feito por comando, normalmente pelo RH. |
| `POST /api/login` | Entra com uma conta já existente e devolve o token de acesso. |
| `POST /api/logout` | Invalida o token atual. |
| `GET /api/me` | Mostra os dados de quem está logado. |

### Organização

| Rota | O que faz |
|---|---|
| `POST /api/setores` | Cadastra um setor da empresa (ex: TI, RH, Manutenção). |
| `GET /api/setores` | Lista os setores cadastrados. |
| `POST /api/categorias` | Cadastra um tipo de solicitação, vinculado a um setor responsável e um prazo (SLA, padrão 24h). |
| `GET /api/categorias` | Lista as categorias cadastradas. |

### Solicitações

| Rota | O que faz |
|---|---|
| `POST /api/solicitacoes` | Abre um novo chamado. O aprovador é descoberto automaticamente pela categoria. |
| `GET /api/solicitacoes` | Lista os chamados relacionados à pessoa logada. |
| `GET /api/solicitacoes/{id}` | Detalhe completo de um chamado: status, histórico, comentários, anexos. |
| `POST /api/solicitacoes/{id}/aprovar` | Aprova o chamado. |
| `POST /api/solicitacoes/{id}/rejeitar` | Recusa o chamado (motivo obrigatório). |
| `POST /api/solicitacoes/{id}/executar` | Alguém da equipe responsável assume o chamado. |
| `POST /api/solicitacoes/{id}/concluir` | Marca o trabalho como finalizado. |
| `POST /api/solicitacoes/{id}/cancelar` | O solicitante desiste do chamado (só antes da execução começar). |
| `POST /api/solicitacoes/{id}/fechar` | O solicitante confirma e encerra de vez. |

### Comentários e anexos

| Rota | O que faz |
|---|---|
| `POST /api/solicitacoes/{id}/comentarios` | Adiciona uma mensagem ao chamado. |
| `GET /api/solicitacoes/{id}/comentarios` | Lista as mensagens do chamado. |
| `POST /api/solicitacoes/{id}/anexos` | Anexa um arquivo ao chamado. |
| `GET /api/solicitacoes/{id}/anexos` | Lista os arquivos anexados. |

### Notificações

| Rota | O que faz |
|---|---|
| `GET /api/notificacoes` | Lista os avisos automáticos da pessoa logada. |
| `POST /api/notificacoes/{id}/marcar-lida` | Marca um aviso como visto. |

### Relatórios (dentro do Laravel)

| Rota | O que faz |
|---|---|
| `GET /api/relatorios/sla-estourado` | Lista chamados que passaram do prazo de aprovação (SLA). |
| `GET /api/relatorios/pendentes` | Lista chamados aprovados esperando execução — fila de trabalho da manutenção. |

---

## 5. Dicionário de rotas — Serviço de relatórios (Python)

Todas exigem o header `x-api-key` com o valor configurado no `.env` do `python-service`.

| Rota | O que faz |
|---|---|
| `GET /relatorios/tempo-medio` | Tempo médio, em horas, para concluir um chamado, agrupado por categoria. |
| `GET /relatorios/volume` | Quantidade de chamados por setor e por status. |
| `GET /relatorios/recorrencia` | Palavras que se repetem em vários títulos de chamados, com a média de dias entre uma ocorrência e outra. |
| `GET /relatorios/exportar-excel` | Gera uma planilha `.xlsx` com todos os relatórios acima, em abas separadas. |

---

## 6. Contas de teste

| Pessoa | Papel | E-mail | Senha |
|---|---|---|---|
| Ana | Aprovadora (RH) | `ana@teste.com` | `123456` |
| João | Solicitante (TI) | `joao@teste.com` | `123456` |
| Carlos | Executor (Manutenção) | `carlos@teste.com` | `123456` |

Cada uma cai numa tela inicial diferente ao logar no front-end: Ana no painel administrativo, João em "Meus chamados", Carlos na fila de execução.

---

## 7. A história: um dia na vida do sistema

Para entender tudo isso na prática, imagine três colegas de trabalho:

- **João**, do time de **TI**, é quem faz os pedidos.
- **Ana**, do **RH**, é quem aprova ou recusa os pedidos do setor dela.
- **Carlos**, da **Manutenção**, é quem coloca a mão na massa e resolve o que foi aprovado.

### Capítulo 1 — A cadeira quebrada

João chega ao trabalho e percebe que sua cadeira está com um problema sério no encosto. Ele abre o sistema e registra um chamado: *"Cadeira quebrada, preciso de uma nova, prioridade alta"*. Nos bastidores, o sistema já sabe, pela categoria escolhida, que quem precisa dar o aval é alguém do RH — e encontra a Ana automaticamente.

Ana recebe o chamado na fila dela. Ela olha os detalhes e decide aprovar. Na hora, o sistema registra essa decisão para sempre no histórico do chamado, e dispara um aviso pro João: *"Sua solicitação foi aprovada"*.

Carlos, da manutenção, vê que tem um chamado aprovado esperando alguém pegar — ele nem precisa procurar, a fila dele já mostra só isso. Ele deixa um comentário avisando: *"Vou providenciar amanhã de manhã"*, e assume o chamado — o status muda para "em execução".

No dia seguinte, Carlos troca a cadeira, tira uma foto e anexa ao chamado como comprovante. Em seguida, marca o trabalho como concluído.

João recebe o aviso, confere que a cadeira nova está lá, e fecha o chamado de vez. Fim feliz — e tudo o que aconteceu, do início ao fim, ficou guardado no histórico daquele chamado, como um diário completo.

### Capítulo 2 — O computador que não liga mais

Duas semanas depois, o computador de João simplesmente não liga mais. Ele abre um novo chamado: *"Computador queimou, preciso de um computador novo, prioridade alta"*.

Dessa vez, Ana olha o pedido e vê que não tem verba disponível naquele momento. Ela recusa o chamado, e o sistema exige que ela escreva o motivo: *"Fora do orçamento no momento"*. João recebe a notificação explicando exatamente por que seu pedido não foi pra frente.

### Capítulo 3 — O teclado (e o mouse desconectado)

Mais tarde, João percebe que algumas teclas do teclado não respondem. Ele abre um chamado: *"Teclado com defeito, algumas teclas não funcionam"*.

Só que, minutos depois, ele descobre que na verdade era só o **mouse** que estava desconectado. Como o chamado ainda está esperando aprovação, João consegue simplesmente cancelar antes que a Ana perca tempo avaliando algo desnecessário.

### Capítulo 4 — O chamado esquecido

Num fim de semana prolongado, um chamado de prioridade baixa fica parado na fila da Ana por dias sem ninguém notar. Só que o sistema não esquece: passadas as 24 horas de prazo, ele mesmo percebe o atraso, marca o chamado como "SLA estourado" e avisa a Ana automaticamente.

### Capítulo 5 — Uma cópia idêntica em qualquer computador

Um colega de trabalho quer ver o sistema funcionando na própria máquina dele, sem instalar PHP, Python ou Node um por um. Ele só precisa do Docker instalado. Com `docker compose up --build`, o banco de dados, a API, o serviço de relatórios e o front-end sobem juntos, exatamente como foram descritos em código — sem nenhuma instalação manual, sem "na minha máquina funciona".

---

Essa é a espinha dorsal do sistema: cada ação de cada personagem vira uma chamada de API, cada chamada vira uma linha no histórico, o sistema cobra a si mesmo quando algo demora demais, e o ambiente inteiro pode ser reproduzido de forma idêntica em qualquer máquina.
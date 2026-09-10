# Sistema de Solicitações Internas — README

Este é o guia principal do projeto. Ele explica como rodar as três partes do sistema, os comandos do dia a dia, o que cada rota faz, e termina com uma história mostrando tudo em ação.

## Visão geral

O projeto tem três partes, cada uma numa pasta própria, rodando ao mesmo tempo em terminais separados:

| Pasta | O que é | Porta |
|---|---|---|
| `api` | Backend principal em Laravel — autenticação, chamados, aprovações, SLA | `8000` |
| `python-service` | Serviço de relatórios em Python (FastAPI) — tempo médio, volume, recorrência | `8001` |
| `frontend` | Interface em React (Vite) — telas de login, chamados e painéis | `5173` |

---

## 1. Como iniciar os três servidores

Você precisa de **três abas de terminal abertas ao mesmo tempo**, uma para cada serviço. Nenhuma delas pode ser fechada enquanto você estiver usando o sistema.

### Terminal 1 — API (Laravel)
```
cd api
php artisan serve
```
Fica no ar em `http://127.0.0.1:8000`.

### Terminal 2 — Relatórios (Python)
```
cd python-service
venv\Scripts\activate
uvicorn main:app --reload --port 8001
```
Fica no ar em `http://127.0.0.1:8001`. Se o ambiente virtual (`venv`) ainda não existir, veja a seção de comandos do Python mais abaixo.

### Terminal 3 — Front-end (React)
```
cd frontend
npm run dev
```
Fica no ar em `http://localhost:5173` — é esse endereço que você abre no navegador para usar o sistema.

Para desligar qualquer um dos três, clique no terminal correspondente e aperte `Ctrl+C`.

---

## 2. Comandos principais (cheat sheet)

### Laravel (`api`)

| Comando | Para que serve |
|---|---|
| `php artisan serve` | Sobe o servidor da API |
| `php artisan route:list` | Lista todas as rotas existentes |
| `php artisan route:list --path=solicitacoes` | Lista só as rotas de um assunto específico |
| `php artisan migrate` | Aplica migrations pendentes no banco |
| `php artisan migrate:fresh` | Apaga **todas** as tabelas e recria vazias (cuidado, perde todos os dados) |
| `php artisan tinker` | Abre um console interativo para consultar/editar o banco direto pelo Eloquent |
| `php artisan make:model Nome -mcr` | Cria model + migration + controller de uma vez |
| `php artisan make:migration nome_da_migration` | Cria uma migration nova |
| `php artisan solicitacoes:verificar-sla` | Roda manualmente a verificação de chamados atrasados |
| `php artisan optimize:clear` | Limpa cache de rotas, config e views (use quando algo "não atualiza" mesmo após editar o código) |
| `composer dump-autoload` | Recarrega o mapeamento de classes do PHP (use após criar um arquivo novo que não está sendo encontrado) |

### Python (`python-service`)

| Comando | Para que serve |
|---|---|
| `python -m venv venv` | Cria o ambiente virtual (só na primeira vez) |
| `venv\Scripts\activate` | Ativa o ambiente virtual (sempre que abrir um terminal novo aqui) |
| `pip install fastapi uvicorn pymysql python-dotenv` | Instala as dependências (só na primeira vez, ou se as dependências mudarem) |
| `uvicorn main:app --reload --port 8001` | Sobe o serviço de relatórios |

### Front-end (`frontend`)

| Comando | Para que serve |
|---|---|
| `npm install` | Instala as dependências (só na primeira vez, ou quando o `package.json` mudar) |
| `npm run dev` | Sobe o servidor de desenvolvimento |
| `npm run build` | Gera uma versão de produção otimizada (pasta `dist`) |

---

## 3. Como verificar se uma requisição está indo pela rota certa

1. **Confira a URL exata.** Troque qualquer coisa entre chaves (como `{id}`) pelo número real do registro — chaves nunca aparecem na URL de verdade.
2. **Confira o método HTTP.** `GET` é para consultar, `POST` é para criar ou executar uma ação.
3. **Rode `php artisan route:list --path=<parte-da-url>`** para confirmar que a rota existe e ver qual controller ela chama.
4. **Confira os headers:** `Accept: application/json` e `Authorization: Bearer {token}` (para a API); `x-api-key` (para o serviço Python).
5. **Use o Tinker** para ver o dado real no banco, sem passar pela API: `php artisan tinker`, depois `App\Models\Solicitacao::find(1)`.
6. **No navegador, use o F12 (DevTools) → aba Network**, para ver exatamente qual chamada o front-end fez e o que veio de resposta — é o primeiro lugar a olhar quando uma tela do sistema não carrega.
7. **Olhe o terminal de cada servidor.** Toda requisição que chega aparece ali — é como confirmar que a chamada realmente saiu do front-end e chegou no destino certo.

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
| `GET /api/relatorios/pendentes` | Lista chamados aprovados esperando execução — é a fila de trabalho da manutenção. |

---

## 5. Dicionário de rotas — Serviço de relatórios (Python)

Todas exigem o header `x-api-key` com o valor configurado no `.env` do `python-service`.

| Rota | O que faz |
|---|---|
| `GET /relatorios/tempo-medio` | Tempo médio, em horas, para concluir um chamado, agrupado por categoria. |
| `GET /relatorios/volume` | Quantidade de chamados por setor e por status. |
| `GET /relatorios/recorrencia` | Palavras que se repetem em vários títulos de chamados, com a média de dias entre uma ocorrência e outra — ajuda a identificar o que está "quebrando" com mais frequência. |

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

Carlos, da manutenção, vê que tem um chamado aprovado esperando alguém pegar — ele nem precisa procurar, a fila dele já mostra só isso. Ele deixa um comentário avisando: *"Vou providenciar amanhã de manhã"*, e assume o chamado — o status muda para "em execução", e o nome dele fica registrado como responsável.

No dia seguinte, Carlos troca a cadeira, tira uma foto da nova cadeira instalada e anexa ao chamado como comprovante. Em seguida, marca o trabalho como concluído.

João recebe o aviso, confere que a cadeira nova está lá, e fecha o chamado de vez. Fim feliz — e tudo o que aconteceu, do início ao fim, ficou guardado no histórico daquele chamado, como um diário completo.

### Capítulo 2 — O computador que não liga mais

Duas semanas depois, o computador de João simplesmente não liga mais. Ele abre um novo chamado: *"Computador queimou, preciso de um computador novo, prioridade alta"*.

Dessa vez, Ana olha o pedido e vê que não tem verba disponível naquele momento. Ela recusa o chamado, e o sistema exige que ela escreva o motivo: *"Fora do orçamento no momento"*. João recebe a notificação explicando exatamente por que seu pedido não foi pra frente — sem mistério, com justificativa registrada.

### Capítulo 3 — O teclado (e o mouse desconectado)

Mais tarde, João percebe que algumas teclas do teclado não respondem. Ele abre um chamado: *"Teclado com defeito, algumas teclas não funcionam"*.

Só que, minutos depois, ele descobre que na verdade era só o **mouse** que estava desconectado — e o teclado nunca teve problema nenhum. Como o chamado ainda está esperando aprovação e ninguém começou a mexer nele, João consegue simplesmente cancelar antes que a Ana perca tempo avaliando algo desnecessário.

### Capítulo 4 — O chamado esquecido

Num fim de semana prolongado, um chamado de prioridade baixa fica parado na fila da Ana por dias sem ninguém notar. Só que o sistema não esquece: passadas as 24 horas de prazo, ele mesmo percebe o atraso, marca o chamado como "SLA estourado" e avisa a Ana automaticamente. Quando ela volta a abrir o painel administrativo, o chamado já está destacado na aba de SLA — ninguém precisou lembrar dela manualmente.

---

Essa é a espinha dorsal do sistema: cada ação de cada personagem vira uma chamada de API, cada chamada vira uma linha no histórico, o sistema cobra a si mesmo quando algo demora demais, e cada papel só consegue fazer exatamente o que faz sentido para ele fazer.
# Sistema de Solicitações Internas — README

Este é o guia principal do projeto. Ele explica como rodar as três partes do sistema (manualmente ou via Docker), os comandos do dia a dia, o pipeline de classificação automática, o que cada rota faz, e termina com uma história mostrando tudo em ação.

## Visão geral

O projeto tem três partes, cada uma numa pasta própria:

| Pasta | O que é | Porta |
|---|---|---|
| `api` | Backend principal em Laravel — autenticação, chamados, aprovações, SLA | `8000` |
| `python-service` | Serviço Python (FastAPI) — relatórios e classificação automática por ML | `8001` |
| `frontend` | Interface em React (Vite) — telas de login, chamados e painéis | `5173` |

Existem **dois jeitos de rodar o projeto**: manualmente (três terminais, um comando por serviço) ou via **Docker Compose** (um único comando sobe tudo, incluindo o banco de dados).

---

## 1. Como iniciar

### 1.1 Modo manual

**Terminal 1 — API (Laravel)**
```
cd api
php artisan serve
```

**Terminal 2 — Serviço Python (relatórios + classificação)**
```
cd python-service
venv\Scripts\activate
uvicorn main:app --reload --port 8001
```

**Terminal 3 — Front-end (React)**
```
cd frontend
npm run dev
```

### 1.2 Modo Docker

```
docker compose up --build
```
Sobe os quatro containers (banco, API, serviço Python, front-end) de uma vez. Copie `.env.example` para `.env` na raiz antes da primeira vez.

```
docker compose up -d       # em segundo plano
docker compose down        # derruba tudo
docker compose down -v     # derruba tudo e apaga o banco (recomeçar do zero)
docker compose up --build api   # reconstrói só um serviço
```

### 1.3 Qual modo usar quando

| Situação | Use |
|---|---|
| Codando e testando mudanças a toda hora | Modo manual |
| Rodar o projeto "pronto", mostrar pra alguém | Modo Docker |
| Editou o front-end com o Docker rodando | Precisa `docker compose up --build frontend` |

---

## 2. Comandos principais (cheat sheet)

### Laravel (`api`)

| Comando | Para que serve |
|---|---|
| `php artisan serve` | Sobe o servidor da API |
| `php artisan route:list` | Lista todas as rotas |
| `php artisan migrate` | Aplica migrations pendentes |
| `php artisan migrate:fresh` | Apaga tudo e recria vazio |
| `php artisan tinker` | Console interativo do Eloquent |
| `php artisan solicitacoes:verificar-sla` | Roda a verificação de atraso manualmente |
| `php artisan optimize:clear` | Limpa cache de rotas/config/views |

### Python (`python-service`)

| Comando | Para que serve |
|---|---|
| `python -m venv venv` | Cria o ambiente virtual |
| `venv\Scripts\activate` | Ativa o ambiente virtual |
| `pip install -r requirements.txt` | Instala as dependências |
| `python treinar.py` | Treina (ou retreina) os modelos de classificação |
| `uvicorn main:app --reload --port 8001` | Sobe o serviço |

**Atenção:** o `main.py` carrega os arquivos `.joblib` do modelo **uma vez, na inicialização**. Depois de rodar `treinar.py`, é preciso **reiniciar o `uvicorn`** para o servidor usar o modelo novo.

### Front-end (`frontend`)

| Comando | Para que serve |
|---|---|
| `npm install` | Instala as dependências |
| `npm run dev` | Sobe o servidor de desenvolvimento |
| `npm run build` | Gera a versão de produção |

### Docker (raiz do projeto)

| Comando | Para que serve |
|---|---|
| `docker compose up --build` | Sobe os 4 containers |
| `docker compose logs api --tail=50` | Log de um serviço |
| `docker compose exec api php artisan tinker` | Tinker dentro do container |
| `docker compose exec api cat .env` | Confirma as variáveis de ambiente reais do container |

---

## 3. Como investigar um problema

1. Confira a URL exata (sem `{chaves}` literais) e o método HTTP.
2. `php artisan route:list --path=<parte-da-url>` confirma se a rota existe.
3. Confira os headers: `Accept: application/json`, `Authorization: Bearer {token}` (API), `x-api-key` (Python).
4. Use o Tinker pra ver o dado real no banco, sem passar pela API.
5. No navegador, **F12 → Network**, para ver a chamada real feita pelo front-end.
6. Se um erro não aparece em `storage/logs/laravel.log`, tente devolver a mensagem direto na resposta (`observacao` de um histórico, por exemplo) — em alguns ambientes o log não é escrito de forma confiável.
7. Se algo não atualiza após editar código: `php artisan config:clear` (Laravel) ou reiniciar o `uvicorn` (Python — ele não recarrega modelos `.joblib` sozinho, mesmo com `--reload`).

---

## 4. Pipeline de classificação automática (Machine Learning)

Chamados não pedem mais categoria/prioridade no formulário — o colaborador só escreve título e descrição, e um modelo de Machine Learning classifica automaticamente.

### 4.1 Como funciona, passo a passo

1. O chamado é salvo com status `em_classificacao` — **antes** de qualquer chamada ao serviço de ML (nada se perde se o Python cair).
2. O Laravel chama `POST /classificar` no serviço Python, mandando `ticket_id`, `title`, `description`.
3. O Python devolve `category`, `priority`, e a **confiança** de cada um (vinda de `predict_proba`, nunca inventada).
4. Se a **confiança da categoria** for ≥ 0.55, o chamado é roteado automaticamente: categoria, prioridade e aprovador preenchidos, status vira `pendente_aprovacao`.
5. Se a confiança for menor, o status vira `aguardando_classificacao_manual`, e o chamado aparece na fila de triagem do painel administrativo.
6. Se o Python estiver fora do ar ou der timeout, o chamado (que já estava salvo) também cai em `aguardando_classificacao_manual` — o ML nunca é ponto único de falha.

### 4.2 Por que o threshold é 0.55, e não 0.70

O case original sugere 0.70 como exemplo. Na prática, testamos o modelo com frases variadas e vimos que a **prioridade** costuma ter confiança bem mais baixa que a categoria (sinais de urgência no texto são mais sutis que sinais de assunto). Por isso, a decisão de rotear automaticamente usa só a **confiança da categoria** — é o que importa para o roteamento por setor — com threshold 0.55, calibrado observando o comportamento real do modelo em vez de usar um número arbitrário. A prioridade sugerida vem junto; se estiver errada, a Ana ajusta sem precisar reclassificar o assunto todo.

### 4.3 Roteamento por categoria

| Categoria | Setor | O que acontece após aprovar |
|---|---|---|
| Hardware, Software, Rede | TI | Vai para a fila de execução (Carlos) |
| Acesso | RH | Resolvido na hora da aprovação — vai direto para `concluida` |
| RH | RH | Resolvido na hora da aprovação — vai direto para `concluida` |
| Manutenção | Manutenção | Vai para a fila de execução (Carlos) |

A Ana aprova chamados de todas as categorias — só a execução pós-aprovação é que se divide (ou vai para o Carlos, ou já se resolve com a aprovação, dependendo do setor).

### 4.4 Correção humana

Um aprovador (ou admin) pode corrigir a classificação de um chamado (`POST /solicitacoes/{id}/corrigir-classificacao`). A correção é registrada preservando a previsão original do modelo — nada é sobrescrito, fica tudo auditável.

### 4.5 Retreinar o modelo

```
cd python-service
python treinar.py
```
Reinicie o `uvicorn` depois. O script já compara Logistic Regression com Naive Bayes e usa o que performar melhor, tanto para categoria quanto para prioridade.

---

## 5. Dicionário de rotas — API (Laravel)

### Contas e login

| Rota | O que faz |
|---|---|
| `POST /api/register` | Cria uma conta (feito por comando, normalmente pelo RH — não existe tela). |
| `POST /api/login` | Login, devolve token. |
| `POST /api/logout` | Invalida o token atual. |
| `GET /api/me` | Dados de quem está logado. |

### Organização

| Rota | O que faz |
|---|---|
| `POST /api/setores` / `GET /api/setores` | Cadastra / lista setores. |
| `POST /api/categorias` / `GET /api/categorias` | Cadastra / lista categorias (setor responsável + SLA). |

### Solicitações

| Rota | O que faz |
|---|---|
| `POST /api/solicitacoes` | Abre um chamado (só título e descrição — categoria/prioridade vêm da IA). |
| `GET /api/solicitacoes` | Lista os chamados da pessoa logada. |
| `GET /api/solicitacoes/{id}` | Detalhe completo: status, histórico, comentários, anexos, previsões da IA. |
| `POST /api/solicitacoes/{id}/aprovar` | Aprova. Se a categoria for de setor RH, resolve direto (`concluida`). |
| `POST /api/solicitacoes/{id}/rejeitar` | Recusa (motivo obrigatório). |
| `POST /api/solicitacoes/{id}/executar` | Executor assume o chamado. |
| `POST /api/solicitacoes/{id}/concluir` | Marca como finalizado. |
| `POST /api/solicitacoes/{id}/cancelar` | Solicitante desiste (antes da execução). |
| `POST /api/solicitacoes/{id}/fechar` | Solicitante confirma e encerra. |
| `POST /api/solicitacoes/{id}/corrigir-classificacao` | Aprovador corrige categoria/prioridade sugeridas pela IA. |

### Comentários e anexos

| Rota | O que faz |
|---|---|
| `POST` / `GET /api/solicitacoes/{id}/comentarios` | Adiciona / lista mensagens do chamado. |
| `POST` / `GET /api/solicitacoes/{id}/anexos` | Anexa / lista arquivos do chamado. |

### Notificações

| Rota | O que faz |
|---|---|
| `GET /api/notificacoes` | Avisos automáticos da pessoa logada. |
| `POST /api/notificacoes/{id}/marcar-lida` | Marca um aviso como visto. |

### Relatórios (Laravel)

| Rota | O que faz |
|---|---|
| `GET /api/relatorios/sla-estourado` | Chamados que passaram do prazo de aprovação. |
| `GET /api/relatorios/pendentes` | Fila de execução (chamados aprovados, aguardando alguém assumir). |
| `GET /api/relatorios/triagem-manual` | Fila de chamados que a IA não conseguiu classificar com confiança suficiente. |
| `GET /api/relatorios/qualidade-classificacao` | Métricas agregadas: total classificado, automático vs manual, correções, confiança média. |
| `GET /api/relatorios/correcoes` | Últimas 20 correções feitas por aprovadores. |

---

## 6. Dicionário de rotas — Serviço Python

Todas exigem o header `x-api-key`.

| Rota | O que faz |
|---|---|
| `POST /classificar` | Recebe título e descrição, devolve categoria, prioridade e confiança de cada uma. |
| `GET /relatorios/tempo-medio` | Tempo médio de resolução por categoria. |
| `GET /relatorios/volume` | Volume de chamados por setor e status. |
| `GET /relatorios/recorrencia` | Palavras que mais se repetem nos títulos, com frequência de recorrência. |
| `GET /relatorios/exportar-excel` | Planilha `.xlsx` com todos os relatórios acima, em abas. |

---

## 7. Contas de teste

| Pessoa | Papel | E-mail | Senha |
|---|---|---|---|
| Ana | Aprovadora (aprova tudo) | `ana@teste.com` | `123456` |
| João | Solicitante (TI) | `joao@teste.com` | `123456` |
| Carlos | Executor (Hardware/Software/Rede/Manutenção) | `carlos@teste.com` | `123456` |

---

## 8. A história: um dia na vida do sistema

- **João**, do time de **TI**, é quem faz os pedidos.
- **Ana** aprova todos os chamados, de qualquer setor.
- **Carlos**, da **Manutenção**, executa o que é aprovado nas categorias técnicas.

### Capítulo 1 — A cadeira quebrada

João abre um chamado. O sistema descobre sozinho, pela categoria, quem precisa aprovar. Ana aprova, Carlos assume, resolve, anexa uma foto como comprovante, marca como concluído. João fecha. Tudo registrado no histórico.

### Capítulo 2 — O computador que não liga mais

Ana recusa por falta de verba, com motivo obrigatório registrado. João entende exatamente por quê.

### Capítulo 3 — O teclado (e o mouse desconectado)

João cancela um chamado antes de alguém perder tempo avaliando algo que já não era mais problema.

### Capítulo 4 — O chamado esquecido

Um chamado de baixa prioridade passa das 24 horas sem resposta. O sistema percebe sozinho, marca como atrasado, avisa a Ana.

### Capítulo 5 — Uma cópia idêntica em qualquer computador

Um colega sobe o projeto inteiro com `docker compose up --build`, sem instalar nada manualmente.

### Capítulo 6 — O computador que não liga (dessa vez, sem seletor nenhum)

João não escolhe mais categoria nem prioridade — só escreve "Notebook não liga, tenho apresentação em 30 minutos". O sistema lê o texto, reconhece o assunto (Hardware) com confiança alta, já preenche a prioridade como alta, e manda direto pra fila da Ana — sem ele precisar pensar em qual caixinha marcar.

### Capítulo 7 — "Isso aqui não está funcionando direito"

Outro colaborador escreve um chamado vago demais pra qualquer IA adivinhar o assunto. O sistema reconhece a própria incerteza — em vez de arriscar um palpite, manda pra fila de triagem manual da Ana, que decide com um clique. Nenhuma decisão automática ruim chega a se tornar aprovação.

---

Essa é a espinha dorsal do sistema: cada ação vira uma chamada de API, cada chamada vira uma linha no histórico, o sistema cobra a si mesmo quando algo demora demais, tenta classificar sozinho o que consegue e pede ajuda quando não tem certeza — e o ambiente inteiro é reproduzível em qualquer máquina.
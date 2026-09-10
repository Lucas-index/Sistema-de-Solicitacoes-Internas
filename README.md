# Sistema de Solicitações Internas — README

Este documento explica, de um jeito simples, como rodar o projeto, como conferir se tudo está funcionando, o que cada rota da API faz, e termina com uma pequena história que mostra o sistema em ação no dia a dia de uma empresa fictícia.

---

## 1. Como iniciar o servidor

1. Abra o terminal do VS Code.
2. Entre na pasta da API (onde está o arquivo `artisan`):
   ```
   cd api
   ```
3. Suba o servidor:
   ```
   php artisan serve
   ```
4. Quando aparecer a linha `INFO Server running on [http://127.0.0.1:8000]`, o sistema está no ar.
5. Para desligar o servidor, aperte `Ctrl+C` no terminal onde ele está rodando.

**Importante:** enquanto esse terminal estiver com o servidor ligado, ele fica "ocupado" — não dá pra rodar outros comandos ali. Se precisar rodar outro comando (como migrations ou tinker), abra uma **nova aba de terminal** no VS Code, sem fechar a que está rodando o servidor.

---

## 2. Como ver todas as rotas disponíveis

Uma "rota" é cada caminho que a API entende. Para ver a lista completa de tudo que o sistema sabe fazer, rode (numa aba de terminal separada da do servidor):

```
php artisan route:list
```

Se quiser ver só as rotas relacionadas a um assunto específico, filtre pelo caminho:

```
php artisan route:list --path=solicitacoes
php artisan route:list --path=notificacoes
```

Isso é útil sempre que uma requisição der erro "rota não encontrada" — a lista mostra exatamente quais rotas existem e com qual nome de parâmetro (por exemplo, `{solicitacao}`).

---

## 3. Como verificar se uma solicitação está indo pela rota certa

Passo a passo para investigar qualquer chamada que não se comporta como esperado:

1. **Confira a URL exata.** Troque qualquer coisa entre chaves (como `{id}`) pelo número real do registro — chaves nunca devem aparecer na URL de verdade.
2. **Confira o método HTTP.** `GET` é para consultar, `POST` é para criar ou executar uma ação (aprovar, rejeitar, etc.).
3. **Rode `php artisan route:list --path=<parte-da-url>`** para confirmar que a rota existe e ver qual controller e método ela chama.
4. **Confira os headers da requisição:**
   - `Accept: application/json` (sem isso, erros de autenticação viram uma tela de erro em vez de JSON)
   - `Authorization: Bearer {token}` (sem isso, ou com o token errado, a API responde "Unauthenticated")
5. **Use o Tinker para inspecionar o banco diretamente**, se a resposta da API parecer estranha:
   ```
   php artisan tinker
   ```
   Dentro dele, por exemplo:
   ```php
   App\Models\Solicitacao::find(1)
   ```
   Isso mostra o registro real salvo no banco, sem passar pela API — útil para saber se o problema é no banco ou na camada da rota/controller.
6. **Olhe o terminal do `php artisan serve`.** Toda requisição que chega aparece ali com o caminho e o tempo de resposta — é o primeiro lugar para confirmar que a chamada realmente chegou no servidor.

---

## 4. Dicionário de rotas (explicado para quem não é da área técnica)

### Contas e login

| Rota | O que faz |
|---|---|
| `POST /api/register` | Cria uma conta nova no sistema (nome, e-mail, senha e papel: solicitante, aprovador, executor ou admin). |
| `POST /api/login` | Entra com uma conta já existente e devolve uma "chave de acesso" (token) que precisa ser usada em todas as ações seguintes. |
| `POST /api/logout` | Invalida a chave de acesso atual, como um "sair da conta". |
| `GET /api/me` | Mostra os dados da pessoa que está logada no momento. |

### Organização (setores e categorias)

| Rota | O que faz |
|---|---|
| `POST /api/setores` | Cadastra um novo setor da empresa (ex: TI, RH, Manutenção). |
| `GET /api/setores` | Lista todos os setores cadastrados. |
| `POST /api/categorias` | Cadastra um tipo de solicitação (ex: "Solicitação de equipamento"), vinculado a um setor responsável e um prazo (SLA). |
| `GET /api/categorias` | Lista todas as categorias cadastradas. |

### Solicitações (o coração do sistema)

| Rota | O que faz |
|---|---|
| `POST /api/solicitacoes` | Abre um novo chamado/pedido. O sistema já descobre sozinho quem deve aprovar, com base na categoria escolhida. |
| `GET /api/solicitacoes` | Lista os chamados que dizem respeito à pessoa logada (seja como quem pediu, quem aprova ou quem executa). |
| `GET /api/solicitacoes/{id}` | Mostra todos os detalhes de um chamado específico: status atual, quem está envolvido, histórico completo, comentários e anexos. |
| `POST /api/solicitacoes/{id}/aprovar` | O aprovador dá sinal verde para o chamado seguir em frente. |
| `POST /api/solicitacoes/{id}/rejeitar` | O aprovador recusa o chamado, obrigatoriamente explicando o motivo. |
| `POST /api/solicitacoes/{id}/executar` | Alguém da equipe responsável assume o chamado e começa a resolver. |
| `POST /api/solicitacoes/{id}/concluir` | Quem está executando avisa que o trabalho foi finalizado. |
| `POST /api/solicitacoes/{id}/cancelar` | Quem abriu o chamado desiste dele, mas só é possível antes de alguém começar a executar. |
| `POST /api/solicitacoes/{id}/fechar` | Quem abriu o chamado confirma que está tudo certo e encerra definitivamente. |

### Comentários e anexos

| Rota | O que faz |
|---|---|
| `POST /api/solicitacoes/{id}/comentarios` | Adiciona uma mensagem/observação dentro de um chamado, como um chat. |
| `GET /api/solicitacoes/{id}/comentarios` | Mostra todas as mensagens trocadas naquele chamado. |
| `POST /api/solicitacoes/{id}/anexos` | Anexa um arquivo (foto, documento, nota fiscal) a um chamado. |
| `GET /api/solicitacoes/{id}/anexos` | Lista os arquivos anexados naquele chamado. |

### Notificações

| Rota | O que faz |
|---|---|
| `GET /api/notificacoes` | Mostra os avisos automáticos que a pessoa logada recebeu (ex: "seu chamado foi aprovado"). |
| `POST /api/notificacoes/{id}/marcar-lida` | Marca um aviso como já visto. |

---

## 5. A história: um dia na vida do sistema

Para entender tudo isso na prática, imagine três colegas de trabalho:

- **João**, do time de **TI**, é quem faz os pedidos.
- **Ana**, do **RH**, é quem aprova ou recusa os pedidos do setor dela.
- **Carlos**, da **Manutenção**, é quem coloca a mão na massa e resolve o que foi aprovado.

### Capítulo 1 — A cadeira quebrada

João chega ao trabalho e percebe que sua cadeira está com um problema sério no encosto. Ele abre o sistema e registra um chamado (`POST /solicitacoes`): *"Cadeira quebrada, preciso de uma nova, prioridade alta"*. Nos bastidores, o sistema já sabe, pela categoria escolhida, que quem precisa dar o aval é alguém do RH — e encontra a Ana automaticamente.

Ana recebe o chamado na fila dela. Ela olha os detalhes e decide aprovar (`POST /solicitacoes/1/aprovar`). Na hora, o sistema registra essa decisão para sempre no histórico do chamado, e dispara um aviso pro João (`GET /notificacoes` do João já mostra: *"Sua solicitação foi aprovada"*).

Carlos, da manutenção, vê que tem um chamado aprovado esperando alguém pegar. Ele deixa um comentário avisando (`POST /solicitacoes/1/comentarios`): *"Vou providenciar amanhã de manhã"*, e assume o chamado (`POST /solicitacoes/1/executar`) — o status muda para "em execução", e o nome dele fica registrado como responsável.

No dia seguinte, Carlos troca a cadeira, tira uma foto da nova cadeira instalada e anexa ao chamado (`POST /solicitacoes/1/anexos`) como comprovante. Em seguida, marca o trabalho como concluído (`POST /solicitacoes/1/concluir`).

João recebe o aviso, confere que a cadeira nova está lá, e fecha o chamado de vez (`POST /solicitacoes/1/fechar`). Fim feliz — e tudo o que aconteceu, do início ao fim, ficou guardado no histórico daquele chamado, como um diário completo.

### Capítulo 2 — O computador que não liga mais

Duas semanas depois, o computador de João simplesmente não liga mais. Ele abre um novo chamado (`POST /solicitacoes`): *"Computador queimou, preciso de um computador novo, prioridade alta"*.

Dessa vez, Ana olha o pedido e vê que não tem verba disponível naquele momento. Ela recusa o chamado (`POST /solicitacoes/2/rejeitar`), e o sistema exige que ela escreva o motivo: *"Fora do orçamento no momento"*. João recebe a notificação explicando exatamente por que seu pedido não foi pra frente — sem mistério, com justificativa registrada.

### Capítulo 3 — O teclado (e o mouse desconectado)

Mais tarde, João percebe que algumas teclas do teclado não respondem. Ele abre um chamado (`POST /solicitacoes`): *"Teclado com defeito, algumas teclas não funcionam"*.

Só que, minutos depois, ele descobre que na verdade era só o **mouse** que estava desconectado — e o teclado nunca teve problema nenhum. Como o chamado ainda está esperando aprovação e ninguém começou a mexer nele, João consegue simplesmente cancelar (`POST /solicitacoes/3/cancelar`) antes que a Ana perca tempo avaliando algo desnecessário.

Ele confere de novo o chamado (`GET /solicitacoes/3`) e confirma: status "cancelada", sem confusão nenhuma no meio do caminho.

---

Essa é a espinha dorsal do sistema: cada ação de cada personagem vira uma chamada de API, cada chamada vira uma linha no histórico, e cada papel (solicitante, aprovador, executor) só consegue fazer exatamente o que faz sentido para ele fazer.
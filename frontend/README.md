# Front-end — Sistema de Solicitações Internas

Interface em React (Vite) para colaboradores abrirem chamados, aprovadores avaliarem, a manutenção executar, e o painel administrativo acompanhar tudo — incluindo os relatórios do serviço Python.

## Como instalar e rodar

1. Extraia esta pasta `frontend` dentro da raiz do seu projeto, ao lado das pastas `api` e `python-service`.
2. Abra um terminal dentro dela:
   ```
   cd frontend
   npm install
   ```
3. Copie o arquivo de exemplo de variáveis de ambiente:
   ```
   copy .env.example .env
   ```
   (no Windows; se usar outro terminal, pode ser `cp .env.example .env`)
4. Confira se os valores dentro do `.env` batem com o que você já usa no back-end (principalmente `VITE_PYTHON_API_KEY`, que precisa ser **idêntico** ao `SERVICE_API_KEY` do `.env` do `python-service`).
5. Suba o servidor de desenvolvimento:
   ```
   npm run dev
   ```
6. Acesse `http://localhost:5173` no navegador.

**Importante:** para o front-end funcionar, os outros dois servidores também precisam estar rodando ao mesmo tempo, cada um no seu terminal:
- `php artisan serve` (API Laravel, porta 8000)
- `uvicorn main:app --reload --port 8001` (serviço de relatórios Python)

## Ajustes necessários nos outros dois serviços (CORS)

Como o navegador vai fazer chamadas de `localhost:5173` para `localhost:8000` e `localhost:8001`, os dois precisam **liberar essa origem** explicitamente — senão o navegador bloqueia as respostas por segurança (erro de CORS no console).

### No Laravel (`api`)

Abra `config/cors.php` e confirme que `allowed_origins` está como `['*']` (libera qualquer origem — mais simples para desenvolvimento) ou, se preferir mais restrito:
```php
'allowed_origins' => ['http://localhost:5173'],
```

### No Python (`python-service`)

Abra `main.py` e adicione, logo após a criação do `app = FastAPI(...)`:
```python
from fastapi.middleware.cors import CORSMiddleware

app.add_middleware(
    CORSMiddleware,
    allow_origins=["http://localhost:5173", "http://127.0.0.1:5173"],
    allow_methods=["*"],
    allow_headers=["*"],
)
```
Reinicie o `uvicorn` depois de salvar.

## Como o sistema decide o que cada pessoa vê

Não existe tela de cadastro — como combinado, novas contas só são criadas via `POST /api/register` (linha de comando/Thunder Client), tipicamente pelo RH.

Depois do login, o sistema direciona cada pessoa pela página inicial de acordo com o campo `papel` da conta:

- **`solicitante`** → cai em "Meus chamados", pode abrir chamados novos, comentar, anexar, cancelar (antes da aprovação) e fechar (depois de concluído).
- **`executor`** → cai na "Fila de execução", vendo só os chamados já aprovados esperando alguém assumir.
- **`aprovador`** e **`admin`** → caem no "Painel administrativo", com abas de aprovações pendentes, monitoramento geral, SLA estourado e os relatórios do serviço Python.

Todo mundo, independente do papel, também consegue abrir os próprios chamados e ver "Meus chamados" pelo menu lateral — a barra lateral só acrescenta os itens extras conforme o papel.

## Estrutura de pastas

```
src/
  api/client.js        → instâncias do axios para a API Laravel e o serviço Python
  context/AuthContext.jsx → login, logout e dados do usuário logado
  components/          → Layout (barra lateral), sino de notificações, badge de status, card de chamado
  pages/                → Login, Meus chamados, Novo chamado, Detalhe do chamado, Fila de execução, Painel administrativo
  constants.js          → traduções de status, prioridade e papel para português
  styles.css            → todo o visual (paleta neutra escura, sem dependências externas de CSS)
```

## Limitação conhecida

A chave do serviço Python (`VITE_PYTHON_API_KEY`) fica visível no código do navegador, porque é assim que variáveis `VITE_*` funcionam — elas são embutidas no JavaScript que roda no cliente. Para uso interno, numa rede local, isso é aceitável; não é uma configuração adequada para expor esse sistema na internet pública sem repensar essa autenticação.

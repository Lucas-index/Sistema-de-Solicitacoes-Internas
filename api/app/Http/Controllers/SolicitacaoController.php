<?php

namespace App\Http\Controllers;

use App\Models\Categoria;
use App\Models\HistoricoStatus;
use App\Models\Notificacao;
use App\Models\Solicitacao;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;  

class SolicitacaoController extends Controller
{
    public function pendentesExecucao(Request $request)
{
    abort_unless(
        in_array($request->user()->papel, ['executor', 'admin']),
        403,
        'Apenas executores podem ver esta fila.'
    );

    return Solicitacao::with(['categoria', 'usuario', 'aprovador'])
        ->where('status', 'aprovada')
        ->latest()
        ->get();
}

    public function index(Request $request)
    {
        return Solicitacao::with(['categoria', 'usuario', 'aprovador', 'executor'])
            ->where('usuario_id', $request->user()->id)
            ->orWhere('aprovador_id', $request->user()->id)
            ->orWhere('executor_id', $request->user()->id)
            ->latest()
            ->paginate(20);
    }

    public function slaEstourado()
{
    return Solicitacao::with(['categoria', 'usuario', 'aprovador'])
        ->where('sla_estourado', true)
        ->latest()
        ->get();
}

    public function store(Request $request)
    {
        $data = $request->validate([
            'categoria_id' => 'required|exists:categorias,id',
            'titulo' => 'required|string|max:255',
            'descricao' => 'required|string',
            'prioridade' => 'required|in:baixa,media,alta',
        ]);

        $categoria = Categoria::findOrFail($data['categoria_id']);

        $aprovador = User::where('setor_id', $categoria->setor_responsavel_id)
            ->where('papel', 'aprovador')
            ->first();

        $solicitacao = Solicitacao::create([
            ...$data,
            'usuario_id' => $request->user()->id,
            'status' => 'pendente_aprovacao',
            'aprovador_id' => $aprovador?->id,
        ]);

        HistoricoStatus::create([
            'solicitacao_id' => $solicitacao->id,
            'status_anterior' => null,
            'status_novo' => 'pendente_aprovacao',
            'usuario_id' => $request->user()->id,
        ]);

        return response()->json($solicitacao->load('categoria', 'aprovador'), 201);
    }

    public function show(Solicitacao $solicitacao)
    {
        return $solicitacao->load('categoria', 'usuario', 'aprovador', 'executor', 'historico', 'comentarios', 'anexos');
    }

    public function aprovar(Request $request, Solicitacao $solicitacao)
{
    $this->authorize('aprovarOuRejeitar', $solicitacao);

    return DB::transaction(function () use ($request, $solicitacao) {
        $statusAnterior = $solicitacao->status;
        $solicitacao->update(['status' => 'aprovada']);

        HistoricoStatus::create([
            'solicitacao_id' => $solicitacao->id,
            'status_anterior' => $statusAnterior,
            'status_novo' => 'aprovada',
            'usuario_id' => $request->user()->id,
        ]);

        $this->notificar($solicitacao->usuario_id, "Sua solicitação #{$solicitacao->id} foi aprovada.");

        return $solicitacao->fresh();
    });
}

    public function rejeitar(Request $request, Solicitacao $solicitacao)
    {
        $this->authorize('aprovarOuRejeitar', $solicitacao);

        $data = $request->validate([
            'motivo' => 'required|string',
        ]);

        $statusAnterior = $solicitacao->status;
        $solicitacao->update(['status' => 'rejeitada']);

        HistoricoStatus::create([
            'solicitacao_id' => $solicitacao->id,
            'status_anterior' => $statusAnterior,
            'status_novo' => 'rejeitada',
            'usuario_id' => $request->user()->id,
            'observacao' => $data['motivo'],
        ]);

        $this->notificar($solicitacao->usuario_id, "Sua solicitação #{$solicitacao->id} foi rejeitada: {$data['motivo']}");

        return $solicitacao->fresh();
    }

    public function executar(Request $request, Solicitacao $solicitacao)
    {
        $this->authorize('executar', $solicitacao);

        $statusAnterior = $solicitacao->status;
        $solicitacao->update([
            'status' => 'em_execucao',
            'executor_id' => $request->user()->id,
        ]);

        HistoricoStatus::create([
            'solicitacao_id' => $solicitacao->id,
            'status_anterior' => $statusAnterior,
            'status_novo' => 'em_execucao',
            'usuario_id' => $request->user()->id,
        ]);

        $this->notificar($solicitacao->usuario_id, "Sua solicitação #{$solicitacao->id} entrou em execução.");

        return $solicitacao->fresh();
    }

    public function concluir(Request $request, Solicitacao $solicitacao)
    {
        $this->authorize('concluir', $solicitacao);

        $statusAnterior = $solicitacao->status;
        $solicitacao->update(['status' => 'concluida']);

        HistoricoStatus::create([
            'solicitacao_id' => $solicitacao->id,
            'status_anterior' => $statusAnterior,
            'status_novo' => 'concluida',
            'usuario_id' => $request->user()->id,
        ]);

        $this->notificar($solicitacao->usuario_id, "Sua solicitação #{$solicitacao->id} foi concluída. Avalie e feche quando quiser.");

        return $solicitacao->fresh();
    }

    public function cancelar(Request $request, Solicitacao $solicitacao)
    {
        $this->authorize('cancelar', $solicitacao);

        $statusAnterior = $solicitacao->status;
        $solicitacao->update(['status' => 'cancelada']);

        HistoricoStatus::create([
            'solicitacao_id' => $solicitacao->id,
            'status_anterior' => $statusAnterior,
            'status_novo' => 'cancelada',
            'usuario_id' => $request->user()->id,
        ]);

        $this->notificar($solicitacao->aprovador_id, "Solicitação #{$solicitacao->id} foi cancelada pelo solicitante.");

        return $solicitacao->fresh();
    }

    public function fechar(Request $request, Solicitacao $solicitacao)
    {
        $this->authorize('fechar', $solicitacao);

        $statusAnterior = $solicitacao->status;
        $solicitacao->update(['status' => 'fechada']);

        HistoricoStatus::create([
            'solicitacao_id' => $solicitacao->id,
            'status_anterior' => $statusAnterior,
            'status_novo' => 'fechada',
            'usuario_id' => $request->user()->id,
        ]);

        return $solicitacao->fresh();
    }

    private function notificar(?int $usuarioId, string $mensagem): void
    {
        if (! $usuarioId) return;

        Notificacao::create([
            'usuario_id' => $usuarioId,
            'mensagem' => $mensagem,
        ]);
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\TicketPrediction;
use Illuminate\Support\Facades\Http;
use App\Models\Categoria;
use App\Models\HistoricoStatus;
use App\Models\Notificacao;
use App\Models\Solicitacao;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; 
use App\Models\TicketCorrection; 

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

public function corrigirClassificacao(Request $request, Solicitacao $solicitacao)
{
    abort_unless(
        in_array($request->user()->papel, ['aprovador', 'admin']),
        403,
        'Apenas aprovadores podem corrigir a classificação.'
    );

    $data = $request->validate([
        'categoria_id' => 'required|exists:categorias,id',
        'prioridade' => 'required|in:baixa,media,alta',
    ]);

    $categoriaNova = Categoria::findOrFail($data['categoria_id']);
    $ultimaPredicao = TicketPrediction::where('solicitacao_id', $solicitacao->id)->latest()->first();

    return DB::transaction(function () use ($request, $solicitacao, $data, $categoriaNova, $ultimaPredicao) {
        TicketCorrection::create([
            'solicitacao_id' => $solicitacao->id,
            'original_category' => $ultimaPredicao?->predicted_category ?? 'não classificado',
            'corrected_category' => $categoriaNova->nome,
            'original_priority' => $ultimaPredicao?->predicted_priority ?? 'não classificado',
            'corrected_priority' => $data['prioridade'],
            'corrected_by' => $request->user()->id,
        ]);

        $aprovador = User::where('setor_id', $categoriaNova->setor_responsavel_id)
            ->where('papel', 'aprovador')->first();

        $statusAnterior = $solicitacao->status;
        $solicitacao->update([
            'categoria_id' => $categoriaNova->id,
            'prioridade' => $data['prioridade'],
            'aprovador_id' => $aprovador?->id,
            'classificacao_manual' => true,
            'status' => 'pendente_aprovacao',
        ]);

        HistoricoStatus::create([
            'solicitacao_id' => $solicitacao->id,
            'status_anterior' => $statusAnterior,
            'status_novo' => 'pendente_aprovacao',
            'usuario_id' => $request->user()->id,
            'observacao' => "Classificação corrigida manualmente para {$categoriaNova->nome} / {$data['prioridade']}",
        ]);

        return $solicitacao->fresh()->load('categoria', 'aprovador');
    });
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
        'titulo' => 'required|string|max:255',
        'descricao' => 'required|string',
    ]);

    $solicitacao = Solicitacao::create([
        'usuario_id' => $request->user()->id,
        'titulo' => $data['titulo'],
        'descricao' => $data['descricao'],
        'status' => 'em_classificacao',
    ]);

    HistoricoStatus::create([
        'solicitacao_id' => $solicitacao->id,
        'status_anterior' => null,
        'status_novo' => 'em_classificacao',
        'usuario_id' => $request->user()->id,
    ]);

    $this->classificarChamado($solicitacao);

    return response()->json($solicitacao->fresh()->load('categoria', 'aprovador'), 201);
}

private function classificarChamado(Solicitacao $solicitacao): void
{
    try {
        $resposta = Http::withHeaders([
            'x-api-key' => config('services.python_ml.key'),
        ])->timeout(5)->post(config('services.python_ml.url') . '/classificar', [
            'ticket_id' => $solicitacao->id,
            'title' => $solicitacao->titulo,
            'description' => $solicitacao->descricao,
        ]);

        if (! $resposta->successful()) {
            throw new \Exception('Serviço de classificação respondeu com erro: ' . $resposta->status());
        }

        $predicao = $resposta->json();
        $categoria = Categoria::where('nome', $predicao['category'])->first();

        TicketPrediction::create([
            'solicitacao_id' => $solicitacao->id,
            'predicted_category' => $predicao['category'],
            'predicted_priority' => $predicao['priority'],
            'category_confidence' => $predicao['category_confidence'],
            'priority_confidence' => $predicao['priority_confidence'],
            'model_version' => $predicao['model_version'],
            'processing_time_ms' => $predicao['processing_time_ms'],
        ]);

        $confiancaMinima = min($predicao['category_confidence'], $predicao['priority_confidence']);

        if ($categoria && $confiancaMinima >= 0.70) {
            $aprovador = User::where('setor_id', $categoria->setor_responsavel_id)
                ->where('papel', 'aprovador')->first();

            $statusAnterior = $solicitacao->status;
            $solicitacao->update([
                'categoria_id' => $categoria->id,
                'prioridade' => $predicao['priority'],
                'aprovador_id' => $aprovador?->id,
                'model_version' => $predicao['model_version'],
                'status' => 'pendente_aprovacao',
            ]);

            HistoricoStatus::create([
                'solicitacao_id' => $solicitacao->id,
                'status_anterior' => $statusAnterior,
                'status_novo' => 'pendente_aprovacao',
                'usuario_id' => $solicitacao->usuario_id,
                'observacao' => "Classificado automaticamente: {$predicao['category']} / {$predicao['priority']} (confiança " . round($confiancaMinima, 2) . ")",
            ]);
        } else {
            $statusAnterior = $solicitacao->status;
            $solicitacao->update([
                'model_version' => $predicao['model_version'],
                'status' => 'aguardando_classificacao_manual',
            ]);

            HistoricoStatus::create([
                'solicitacao_id' => $solicitacao->id,
                'status_anterior' => $statusAnterior,
                'status_novo' => 'aguardando_classificacao_manual',
                'usuario_id' => $solicitacao->usuario_id,
                'observacao' => "Confiança insuficiente para classificar automaticamente (" . round($confiancaMinima, 2) . ")",
            ]);
        }
    } catch (\Throwable $e) {
        \Log::error('Falha ao classificar chamado: ' . $e->getMessage());
        $statusAnterior = $solicitacao->status;
        $solicitacao->update(['status' => 'aguardando_classificacao_manual']);

        HistoricoStatus::create([
            'solicitacao_id' => $solicitacao->id,
            'status_anterior' => $statusAnterior,
            'status_novo' => 'aguardando_classificacao_manual',
            'usuario_id' => $solicitacao->usuario_id,
            'observacao' => 'Serviço de classificação indisponível, encaminhado para triagem manual.',
        ]);
    }
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
    'status_novo' => 'aguardando_classificacao_manual',
    'usuario_id' => $solicitacao->usuario_id,
    'observacao' => 'Serviço de classificação indisponível, encaminhado para triagem manual.',
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
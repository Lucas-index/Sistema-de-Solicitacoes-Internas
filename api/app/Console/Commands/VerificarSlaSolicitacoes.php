<?php

namespace App\Console\Commands;

use App\Models\Notificacao;
use App\Models\Solicitacao;
use Illuminate\Console\Command;

class VerificarSlaSolicitacoes extends Command
{
    protected $signature = 'solicitacoes:verificar-sla';

    protected $description = 'Verifica solicitações pendentes de aprovação que estouraram o SLA e notifica o aprovador';

    public function handle(): void
    {
        $solicitacoes = Solicitacao::with('categoria')
            ->where('status', 'pendente_aprovacao')
            ->where('sla_estourado', false)
            ->get();

        $contador = 0;

        foreach ($solicitacoes as $solicitacao) {
            $prazo = $solicitacao->created_at->addHours($solicitacao->categoria->sla_horas);

            if (now()->greaterThan($prazo)) {
                $solicitacao->update(['sla_estourado' => true]);

                if ($solicitacao->aprovador_id) {
                    Notificacao::create([
                        'usuario_id' => $solicitacao->aprovador_id,
                        'mensagem' => "A solicitação #{$solicitacao->id} está com o SLA estourado e ainda aguarda sua aprovação.",
                    ]);
                }

                $this->info("Solicitação #{$solicitacao->id} marcada como atrasada.");
                $contador++;
            }
        }

        $this->info("Verificação concluída. {$contador} solicitação(ões) marcada(s) como atrasada(s).");
    }
}
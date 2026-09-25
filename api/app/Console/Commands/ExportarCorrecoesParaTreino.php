<?php

namespace App\Console\Commands;

use App\Models\TicketCorrection;
use Illuminate\Console\Command;

class ExportarCorrecoesParaTreino extends Command
{
    protected $signature = 'ia:exportar-correcoes {--minimo=5 : Número mínimo de correções para exportar}';

    protected $description = 'Exporta as correções humanas acumuladas para um CSV de retreino';

    public function handle(): void
    {
        $minimo = (int) $this->option('minimo');
        $total = TicketCorrection::count();

        if ($total < $minimo) {
            $this->warn("Existem apenas {$total} correção(ões). Mínimo recomendado: {$minimo}. Use --minimo=1 para forçar.");
            return;
        }

        $correcoes = TicketCorrection::with('solicitacao:id,titulo,descricao')->get();

        $linhas = [];
        foreach ($correcoes as $correcao) {
            if (!$correcao->solicitacao) continue;

            $linhas[] = [
                'titulo' => $correcao->solicitacao->titulo,
                'descricao' => $correcao->solicitacao->descricao,
                'categoria' => $correcao->corrected_category,
                'prioridade' => $correcao->corrected_priority,
            ];
        }

        $caminho = base_path('../python-service/correcoes_exportadas.csv');
        $fp = fopen($caminho, 'w');
        fputcsv($fp, ['titulo', 'descricao', 'categoria', 'prioridade']);
        foreach ($linhas as $linha) {
            fputcsv($fp, $linha);
        }
        fclose($fp);

        $this->info("Exportadas {$total} correção(ões) para correcoes_exportadas.csv");
        $this->info("Agora rode: python retreinar.py --versao=ticket-classifier-v2");
    }
}
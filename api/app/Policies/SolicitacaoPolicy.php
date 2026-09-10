<?php

namespace App\Policies;

use App\Models\Solicitacao;
use App\Models\User;

class SolicitacaoPolicy
{
    public function aprovarOuRejeitar(User $user, Solicitacao $solicitacao): bool
    {
        return $solicitacao->status === 'pendente_aprovacao'
            && ($user->id === $solicitacao->aprovador_id || $user->papel === 'admin');
    }

    public function executar(User $user, Solicitacao $solicitacao): bool
    {
        return $solicitacao->status === 'aprovada'
            && ($user->papel === 'executor' || $user->papel === 'admin');
    }

    public function concluir(User $user, Solicitacao $solicitacao): bool
    {
        return $solicitacao->status === 'em_execucao'
            && ($user->id === $solicitacao->executor_id || $user->papel === 'admin');
    }

    public function cancelar(User $user, Solicitacao $solicitacao): bool
    {
        return in_array($solicitacao->status, ['criada', 'pendente_aprovacao'])
            && $user->id === $solicitacao->usuario_id;
    }

    public function fechar(User $user, Solicitacao $solicitacao): bool
{
    return $solicitacao->status === 'concluida'
        && $user->id === $solicitacao->usuario_id;
}

}
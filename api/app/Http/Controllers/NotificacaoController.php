<?php

namespace App\Http\Controllers;

use App\Models\Notificacao;
use Illuminate\Http\Request;

class NotificacaoController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()->notificacoes()->latest()->get();
    }

    public function marcarLida(Request $request, Notificacao $notificacao)
    {
        abort_if($notificacao->usuario_id !== $request->user()->id, 403);

        $notificacao->update(['lida' => true]);

        return $notificacao;
    }
}
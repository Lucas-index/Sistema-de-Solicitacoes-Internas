<?php

namespace App\Http\Controllers;

use App\Models\Comentario;
use App\Models\Solicitacao;
use Illuminate\Http\Request;

class ComentarioController extends Controller
{
    public function index(Solicitacao $solicitacao)
    {
        return $solicitacao->comentarios()->with('usuario')->latest()->get();
    }

    public function store(Request $request, Solicitacao $solicitacao)
    {
        $data = $request->validate(['texto' => 'required|string']);

        $comentario = Comentario::create([
            'solicitacao_id' => $solicitacao->id,
            'usuario_id' => $request->user()->id,
            'texto' => $data['texto'],
        ]);

        return response()->json($comentario->load('usuario'), 201);
    }
}
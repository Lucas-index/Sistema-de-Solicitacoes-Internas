<?php

namespace App\Http\Controllers;

use App\Models\Anexo;
use App\Models\Solicitacao;
use Illuminate\Http\Request;

class AnexoController extends Controller
{
    public function index(Solicitacao $solicitacao)
    {
        return $solicitacao->anexos;
    }

    public function store(Request $request, Solicitacao $solicitacao)
    {
        $request->validate(['arquivo' => 'required|file|max:10240']);

        $path = $request->file('arquivo')->store('anexos', 'public');

        $anexo = Anexo::create([
            'solicitacao_id' => $solicitacao->id,
            'caminho_arquivo' => $path,
        ]);

        return response()->json($anexo, 201);
    }
}
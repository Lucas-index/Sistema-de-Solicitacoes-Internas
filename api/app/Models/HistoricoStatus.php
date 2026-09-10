<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoricoStatus extends Model
{
    protected $table = 'historico_status';
    protected $fillable = [
    'solicitacao_id', 'status_anterior', 'status_novo', 'usuario_id', 'observacao', 'mensagem',
];

public function solicitacao() { return $this->belongsTo(Solicitacao::class); }
public function usuario() { return $this->belongsTo(User::class); }
}

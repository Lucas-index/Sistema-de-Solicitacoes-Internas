<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Solicitacao extends Model
{
    protected $table = 'solicitacoes';

protected $fillable = [
    'usuario_id', 'categoria_id', 'titulo', 'descricao',
    'prioridade', 'status', 'aprovador_id', 'executor_id', 'sla_estourado',
];

public function usuario() { return $this->belongsTo(User::class, 'usuario_id'); }
public function categoria() { return $this->belongsTo(Categoria::class); }
public function aprovador() { return $this->belongsTo(User::class, 'aprovador_id'); }
public function executor() { return $this->belongsTo(User::class, 'executor_id'); }
public function historico() { return $this->hasMany(HistoricoStatus::class); }
public function comentarios() { return $this->hasMany(Comentario::class); }
public function anexos() { return $this->hasMany(Anexo::class); }
}

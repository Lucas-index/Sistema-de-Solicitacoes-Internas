<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comentario extends Model
{
    protected $fillable = ['solicitacao_id', 'usuario_id', 'texto'];

public function solicitacao() { return $this->belongsTo(Solicitacao::class); }
public function usuario() { return $this->belongsTo(User::class); }
}

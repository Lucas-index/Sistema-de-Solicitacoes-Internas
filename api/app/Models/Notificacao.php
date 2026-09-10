<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notificacao extends Model
{
    protected $table = 'notificacoes';
    protected $fillable = ['usuario_id', 'mensagem', 'lida'];

public function usuario() { return $this->belongsTo(User::class); }
}

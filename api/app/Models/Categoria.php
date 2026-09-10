<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Categoria extends Model
{
    protected $fillable = ['nome', 'setor_responsavel_id', 'sla_horas'];

public function setorResponsavel() { return $this->belongsTo(Setor::class, 'setor_responsavel_id'); }
public function solicitacoes() { return $this->hasMany(Solicitacao::class); }
}

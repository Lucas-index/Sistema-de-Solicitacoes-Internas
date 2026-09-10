<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'papel', 'setor_id',
    ];

    public function setor() { return $this->belongsTo(Setor::class); }
    public function solicitacoes() { return $this->hasMany(Solicitacao::class, 'usuario_id'); }
    public function aprovacoes() { return $this->hasMany(Solicitacao::class, 'aprovador_id'); }
    public function execucoes() { return $this->hasMany(Solicitacao::class, 'executor_id'); }
    public function notificacoes() { return $this->hasMany(Notificacao::class, 'usuario_id'); }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setor extends Model
{
    protected $table = 'setores';
    protected $fillable = ['nome'];

public function usuarios() { return $this->hasMany(User::class); }
public function categorias() { return $this->hasMany(Categoria::class, 'setor_responsavel_id'); }
}

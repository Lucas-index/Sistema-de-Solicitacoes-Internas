<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketCorrection extends Model
{
    protected $fillable = [
    'solicitacao_id', 'original_category', 'corrected_category',
    'original_priority', 'corrected_priority', 'corrected_by',
];

public function solicitacao() { return $this->belongsTo(Solicitacao::class); }
public function corretor() { return $this->belongsTo(User::class, 'corrected_by'); }
}

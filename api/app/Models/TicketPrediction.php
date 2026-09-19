<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketPrediction extends Model
{
    protected $fillable = [
    'solicitacao_id', 'predicted_category', 'predicted_priority',
    'category_confidence', 'priority_confidence', 'model_version', 'processing_time_ms',
];

public function solicitacao() { return $this->belongsTo(Solicitacao::class); }
}

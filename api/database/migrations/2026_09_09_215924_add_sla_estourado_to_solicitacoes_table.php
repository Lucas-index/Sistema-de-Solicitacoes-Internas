<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $fillable = [
    'usuario_id', 'categoria_id', 'titulo', 'descricao',
    'prioridade', 'status', 'aprovador_id', 'executor_id', 'sla_estourado',
];

    public function up(): void
{
    Schema::table('solicitacoes', function (Blueprint $table) {
        $table->boolean('sla_estourado')->default(false);
    });
}

public function down(): void
{
    Schema::table('solicitacoes', function (Blueprint $table) {
        $table->dropColumn('sla_estourado');
    });
}
};

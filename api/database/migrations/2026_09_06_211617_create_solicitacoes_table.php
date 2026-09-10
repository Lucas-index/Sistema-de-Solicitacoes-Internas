<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('solicitacoes', function (Blueprint $table) {
    $table->id();
    $table->foreignId('usuario_id')->constrained('users');
    $table->foreignId('categoria_id')->constrained('categorias');
    $table->string('titulo');
    $table->text('descricao');
    $table->string('prioridade')->default('media'); // baixa | media | alta
    $table->string('status')->default('criada'); // criada | pendente_aprovacao | aprovada | rejeitada | em_execucao | concluida | fechada
    $table->foreignId('aprovador_id')->nullable()->constrained('users');
    $table->foreignId('executor_id')->nullable()->constrained('users');
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitacoes');
    }
};

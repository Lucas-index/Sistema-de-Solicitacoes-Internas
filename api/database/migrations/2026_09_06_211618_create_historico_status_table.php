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
        Schema::create('historico_status', function (Blueprint $table) {
    $table->id();
    $table->foreignId('solicitacao_id')->constrained('solicitacoes');
    $table->string('status_anterior')->nullable();
    $table->string('status_novo');
    $table->foreignId('usuario_id')->constrained('users');
    $table->text('observacao')->nullable();
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historico_status');
    }
};

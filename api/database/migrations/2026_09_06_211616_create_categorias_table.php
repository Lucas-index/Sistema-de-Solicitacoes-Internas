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
       Schema::create('categorias', function (Blueprint $table) {
    $table->id();
    $table->string('nome');
    $table->foreignId('setor_responsavel_id')->constrained('setores');
    $table->unsignedInteger('sla_horas')->default(24);
    $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};

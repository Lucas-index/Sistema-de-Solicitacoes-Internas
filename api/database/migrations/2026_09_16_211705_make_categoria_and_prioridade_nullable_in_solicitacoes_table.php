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
    Schema::table('solicitacoes', function (Blueprint $table) {
        $table->dropForeign(['categoria_id']);
    });
    Schema::table('solicitacoes', function (Blueprint $table) {
        $table->unsignedBigInteger('categoria_id')->nullable()->change();
        $table->string('prioridade')->nullable()->change();
    });
    Schema::table('solicitacoes', function (Blueprint $table) {
        $table->foreign('categoria_id')->references('id')->on('categorias');
    });
}

public function down(): void
{
    Schema::table('solicitacoes', function (Blueprint $table) {
        $table->dropForeign(['categoria_id']);
        $table->unsignedBigInteger('categoria_id')->nullable(false)->change();
        $table->string('prioridade')->nullable(false)->change();
        $table->foreign('categoria_id')->references('id')->on('categorias');
    });
}
};

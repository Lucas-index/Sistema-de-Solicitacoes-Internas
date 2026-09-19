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
    Schema::create('ticket_corrections', function (Blueprint $table) {
        $table->id();
        $table->foreignId('solicitacao_id')->constrained('solicitacoes');
        $table->string('original_category');
        $table->string('corrected_category');
        $table->string('original_priority');
        $table->string('corrected_priority');
        $table->foreignId('corrected_by')->constrained('users');
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('ticket_corrections');
}
};

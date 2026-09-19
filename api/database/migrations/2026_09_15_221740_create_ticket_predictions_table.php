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
    Schema::create('ticket_predictions', function (Blueprint $table) {
        $table->id();
        $table->foreignId('solicitacao_id')->constrained('solicitacoes');
        $table->string('predicted_category');
        $table->string('predicted_priority');
        $table->decimal('category_confidence', 4, 3);
        $table->decimal('priority_confidence', 4, 3);
        $table->string('model_version');
        $table->unsignedInteger('processing_time_ms');
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('ticket_predictions');
}
};

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
    Schema::create('model_registry', function (Blueprint $table) {
        $table->id();
        $table->string('version')->unique();
        $table->string('algorithm');
        $table->timestamp('trained_at');
        $table->json('metrics_json')->nullable();
        $table->boolean('active')->default(false);
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('model_registry');
}
};

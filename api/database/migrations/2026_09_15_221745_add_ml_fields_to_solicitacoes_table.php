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
        $table->string('model_version')->nullable()->after('sla_estourado');
        $table->boolean('classificacao_manual')->default(false)->after('model_version');
    });
}

public function down(): void
{
    Schema::table('solicitacoes', function (Blueprint $table) {
        $table->dropColumn(['model_version', 'classificacao_manual']);
    });
}
};

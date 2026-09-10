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
    Schema::table('users', function (Blueprint $table) {
        $table->string('papel')->default('solicitante'); // solicitante | aprovador | executor | admin
        $table->foreignId('setor_id')->nullable()->constrained('setores');
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropForeign(['setor_id']);
        $table->dropColumn(['papel', 'setor_id']);
    });
}
};

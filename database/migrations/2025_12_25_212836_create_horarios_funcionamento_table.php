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
        Schema::create('horarios_funcionamento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estabelecimento_id')->constrained('estabelecimentos')->onDelete('cascade');
            $table->unsignedTinyInteger('dia_semana'); // 0 a 6
            $table->time('hora_abertura');
            $table->time('hora_fechamento');
            $table->timestamps();

            // Índice para performance, já que um dia terá múltiplos registros
            $table->index(['estabelecimento_id', 'dia_semana']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hoarios_funcionamento');
    }
};

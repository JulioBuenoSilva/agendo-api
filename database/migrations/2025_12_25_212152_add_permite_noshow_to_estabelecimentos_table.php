<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona a opção de no-show opcional.
     * O padrão é 'false' para garantir que o comportamento restritivo 
     * seja o padrão até que o dono do estabelecimento decida mudar.
     */
    public function up(): void
    {
        Schema::table('estabelecimentos', function (Blueprint $table) {
            $table->boolean('permite_noshow')
                  ->default(false)
                  ->after('nome'); // Ajuste o 'after' conforme suas colunas reais
        });
    }

    /**
     * Reverte a alteração.
     */
    public function down(): void
    {
        Schema::table('estabelecimentos', function (Blueprint $table) {
            $table->dropColumn('permite_noshow');
        });
    }
};
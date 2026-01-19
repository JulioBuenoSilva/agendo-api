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
        Schema::create('estabelecimentos', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('identificador')->unique();
            $table->string('endereco');
            $table->enum('ramo', ['beleza', 'saude', 'terapia', 'outros']);
            $table->string('fuso_horario')->default('America/Sao_Paulo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estabelecimentos');
    }
};

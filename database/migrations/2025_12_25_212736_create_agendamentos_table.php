<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agendamentos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('estabelecimento_id')
                ->constrained('estabelecimentos');

            // cliente é opcional
            $table->foreignId('cliente_id')
                ->nullable()
                ->constrained('users');

            // nome livre para cliente não cadastrado
            $table->string('cliente_nome')->nullable();

            $table->foreignId('profissional_id')
                ->constrained('users');

            $table->foreignId('servico_id')
                ->constrained('servicos');

            $table->dateTime('inicio_horario'); // UTC
            $table->dateTime('fim_horario');    // UTC

            $table->string('status')->default('pendente');
            $table->check(
                "status IN ('pendente','confirmado','cancelado','faltou')"
            );

            // 🔥 NOVOS CAMPOS
            $table->foreignId('status_alterado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('status_autor_tipo')->nullable();
            $table->check(
                "status_autor_tipo IN ('cliente','profissional','sistema')"
            );

            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index(
                ['profissional_id', 'inicio_horario'],
                'idx_profissional_horario'
            );

            // garante que existe cliente_id OU cliente_nome
            $table->check(
                '(cliente_id IS NOT NULL OR cliente_nome IS NOT NULL)'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agendamentos');
    }
};

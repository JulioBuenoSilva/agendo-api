<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agendamentos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('estabelecimento_id')
                ->constrained('estabelecimentos');

            $table->foreignId('cliente_id')
                ->nullable()
                ->constrained('users');

            $table->string('cliente_nome')->nullable();

            $table->foreignId('profissional_id')
                ->constrained('users');

            $table->foreignId('servico_id')
                ->constrained('servicos');

            $table->dateTime('inicio_horario'); // UTC
            $table->dateTime('fim_horario');    // UTC

            $table->string('status')->default('pendente');

            $table->foreignId('status_alterado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('status_autor_tipo')->nullable();

            $table->text('observacoes')->nullable();
            $table->timestamps();

            $table->index(
                ['profissional_id', 'inicio_horario'],
                'idx_profissional_horario'
            );
        });

        // CHECKS adicionados manualmente (PostgreSQL)

        DB::statement("
            ALTER TABLE agendamentos
            ADD CONSTRAINT agendamentos_status_check
            CHECK (status IN ('pendente','confirmado','cancelado','faltou'))
        ");

        DB::statement("
            ALTER TABLE agendamentos
            ADD CONSTRAINT agendamentos_status_autor_tipo_check
            CHECK (status_autor_tipo IS NULL OR status_autor_tipo IN ('cliente','profissional','sistema'))
        ");

        DB::statement("
            ALTER TABLE agendamentos
            ADD CONSTRAINT agendamentos_cliente_check
            CHECK (cliente_id IS NOT NULL OR cliente_nome IS NOT NULL)
        ");
    }

    public function down(): void
    {
        Schema::dropIfExists('agendamentos');
    }
};

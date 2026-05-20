<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('email')->unique();
            $table->string('telefone')->nullable();

            $table->enum('tipo', ['cliente', 'profissional', 'admin'])->index();

            // 👇 agora nasce inativo (fluxo de aprovação)
            $table->boolean('ativo')->default(false);

            $table->foreignId('estabelecimento_id')
                ->nullable()
                ->constrained('estabelecimentos')
                ->nullOnDelete();

            $table->timestamp('email_verified_at')->nullable();$table->string('foto_path')->nullable();

            // 👇 login tradicional ou social
            $table->string('password')->nullable();
            $table->string('google_id')->nullable()->unique();
            $table->boolean('is_admin_estabelecimento')->default(false);


            $table->rememberToken();
            $table->timestamps();
        }); 

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};

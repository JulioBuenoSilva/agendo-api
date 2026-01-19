<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_lembretes_config', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->integer('minutos_antes');
            $table->timestamps();

            $table->unique('user_id'); // um config por usuário
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_lembretes_config');
    }
};

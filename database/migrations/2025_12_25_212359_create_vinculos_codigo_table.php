<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vinculo_codigos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // O profissional
            $table->foreignId('estabelecimento_id')->constrained()->onDelete('cascade');
            $table->string('codigo', 6);
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('vinculo_codigos');
    }
};
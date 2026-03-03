use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bloqueios_agenda', function (Blueprint $table) {
            $table->id();

            $table->foreignId('estabelecimento_id')
                ->constrained('estabelecimentos')
                ->cascadeOnDelete();

            $table->foreignId('profissional_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->dateTime('inicio');
            $table->dateTime('fim');

            $table->string('motivo')->nullable();
            $table->boolean('dia_inteiro')->default(false);

            $table->timestamps();

            $table->index(['profissional_id', 'inicio', 'fim']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bloqueios_agenda');
    }
};

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Estabelecimento;
use App\Models\Servico;
use App\Models\HorarioFuncionamento;


class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Usuário admin de teste
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'tipo' => 'admin',
            'ativo' => true,
        ]);

        // 1. Criar o Estabelecimento
        $barbearia = Estabelecimento::create([
            'nome' => 'Barbearia Atlas',
            'identificador' => 'barbearia-atlas',
            'ramo' => 'beleza',
            'fuso_horario' => 'America/Sao_Paulo'
        ]);

        // 2. Criar o Profissional vinculado à barbearia
        $profissional = User::create([
            'name' => 'João Barbeiro',
            'email' => 'joao@barba.com',
            'password' => Hash::make('password'),
            'tipo' => 'profissional',
            'estabelecimento_id' => $barbearia->id,
            'ativo' => true
        ]);

        // 3. Criar um Cliente para testes
        $cliente = User::create([
            'name' => 'Cliente de Teste',
            'email' => 'cliente@teste.com',
            'password' => Hash::make('password'),
            'tipo' => 'cliente',
            'ativo' => true
        ]);

        // 4. Criar um Serviço (Corte de Cabelo - 30 min)
        $corte = Servico::create([
            'estabelecimento_id' => $barbearia->id,
            'nome' => 'Corte Degradê',
            'duracao_minutos' => 30,
            'preco' => 50.00,
            'ativo' => true
        ]);

        // 5. Criar Horário de Funcionamento (Segunda a Sexta, 08h às 18h)
        for ($i = 1; $i <= 5; $i++) {
            HorarioFuncionamento::create([
                'estabelecimento_id' => $barbearia->id,
                'dia_semana' => $i,
                'hora_abertura' => '08:00:00',
                'hora_fechamento' => '18:00:00'
            ]);
        }
    }
}

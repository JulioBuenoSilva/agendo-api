<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB; 
use App\Models\Agendamento;        
use App\Notifications\AgendamentoAtualizado; 
use Carbon\Carbon;

class EnviarLembretesAgendamento extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:enviar-lembretes-agendamento';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envia lembretes de agendamento';

    public function handle()
    {
        $configs = DB::table('user_lembretes_config')->get();

        foreach ($configs as $config) {
            $dataAlvo = now()->addMinutes($config->minutos_antes)->format('Y-m-d H:i:00');

            // Buscamos apenas os agendamentos que ainda estão como 'pendente' ou 'confirmado'
            // mas que precisam dessa confirmação de última hora do cliente.
            $agendamentos = Agendamento::where('inicio_horario', $dataAlvo)
                ->where('cliente_id', $config->user_id)
                ->whereIn('status', ['pendente', 'confirmado']) 
                ->get();

            foreach ($agendamentos as $agendamento) {
                // Alteramos o status para indicar que estamos esperando o cliente clicar
                $agendamento->update(['status' => 'aguardando_confirmacao']);

                $agendamento->cliente->notify(new AgendamentoAtualizado(
                    $agendamento,
                    "Você confirma sua presença em " . $this->formatarTempo($config->minutos_antes) . "? Clique aqui para confirmar."
                ));
            }
        }
    }
    
    // Método auxiliar para deixar a mensagem bonita
    private function formatarTempo($minutos)
    {
        if ($minutos < 60) return "$minutos minutos";
        if ($minutos == 60) return "1 hora";
        if ($minutos < 1440) return floor($minutos / 60) . " horas";
        if ($minutos == 1440) return "1 dia";
        return floor($minutos / 1440) . " dias";
    }
}

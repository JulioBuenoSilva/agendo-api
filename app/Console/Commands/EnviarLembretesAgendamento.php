<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Agendamento;
use App\Models\UserLembreteConfig;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use App\Notifications\LembreteAgendamentoNotification;

class EnviarLembretesAgendamento extends Command
{
    protected $signature = 'agendo:enviar-lembretes';
    protected $description = 'Verifica e envia pushes de lembrete baseados na config do usuário';

    public function handle()
    {
        $agora = Carbon::now()->setSeconds(0); // Ignoramos segundos para bater o minuto exato

        // 1. Buscamos todas as configs de lembrete existentes
        $configs = UserLembreteConfig::all();

        foreach ($configs as $config) {
            // Calculamos o horário que o agendamento deve ter para que o lembrete saia AGORA
            // Ex: Se o user quer 2h antes e agora são 14h, buscamos agendamentos das 16h.
            $horarioAlvo = $agora->copy()->addMinutes($config->minutos_antes);

            $agendamentos = Agendamento::where('cliente_id', $config->user_id)
                ->where('status', 'confirmado')
                ->orWhere('status', 'pendente')
                ->whereBetween('data_hora', [
                    $horarioAlvo->copy()->startOfMinute(),
                    $horarioAlvo->copy()->endOfMinute()
                ])
                ->with(['profissional', 'servico'])
                ->get();

            foreach ($agendamentos as $agendamento) {
                // Dispara a notificação (Firebase + DB)
                $agendamento->user->notify(new LembreteAgendamentoNotification($agendamento));
                
                $this->info("Lembrete enviado para {$agendamento->user->name} sobre o agendamento ID: {$agendamento->id}");
            }
        }
    }
}
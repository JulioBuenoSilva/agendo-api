<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Agendamento;
use App\Models\UserLembreteConfig;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use App\Notifications\LembreteAgendamentoNotification;
use Illuminate\Support\Facades\Log;

class EnviarLembretesAgendamento extends Command
{
    protected $signature = 'agendo:enviar-lembretes';
    protected $description = 'Verifica e envia pushes de lembrete baseados na config do usuário';

    public function handle()
    {
        $agora = Carbon::now()->setSeconds(0); // Ignoramos segundos para bater o minuto exato

        Log::info("=== INÍCIO DO PROCESSAMENTO DE LEMBRETES ===");
        Log::info("Hora atual do servidor: " . $agora->toDateTimeString());
        Log::info("Timezone configurada: " . config('app.timezone'));

        // 1. Buscamos todas as configs de lembrete existentes
        $configs = UserLembreteConfig::all();

        if ($configs->isEmpty()) {
            Log::warning("Nenhuma configuração de lembrete encontrada na tabela user_lembretes_config.");
            return;
        }

        foreach ($configs as $config) {
            // Calculamos o horário que o agendamento deve ter para que o lembrete saia AGORA
            // Ex: Se o user quer 2h antes e agora são 14h, buscamos agendamentos das 16h.
            $horarioAlvo = $agora->copy()->addMinutes($config->minutos_antes);
        
            // Log para conferir se o cálculo do alvo está batendo com o que está no seu banco
            Log::debug("Checando config: User {$config->user_id} quer aviso {$config->minutos_antes}min antes. Buscando agendamentos em: {$horarioAlvo->toDateTimeString()}");

            $agendamentos = Agendamento::where('cliente_id', $config->user_id)
                ->where(function ($query) {
                    $query->where('status', 'confirmado')
                        ->orWhere('status', 'pendente');
                })
                ->whereBetween('inicio_horario', [
                    $horarioAlvo->copy()->startOfMinute(),
                    $horarioAlvo->copy()->endOfMinute()
                ])
                ->with(['profissional', 'servico'])
                ->get();

            $count = $agendamentos->count();
            Log::info("Para o user {$config->user_id}, encontrei {$count} agendamentos às {$horarioAlvo}");
            foreach ($agendamentos as $agendamento) {
                try {
                    if (!$agendamento->cliente) {
                        Log::error("ERRO: Agendamento {$agendamento->id} não possui um cliente associado (Relationship 'cliente' retornou null).");
                        continue;
                    }

                    if (empty($agendamento->cliente->fcm_token)) {
                        Log::warning("AVISO: Usuário {$agendamento->cliente->id} não possui fcm_token cadastrado. Pulando envio.");
                        continue;
                    }

                    $agendamento->cliente->notify(new LembreteAgendamentoNotification($agendamento));
                    
                    Log::info("NOTIFICAÇÃO DISPARADA: ID {$agendamento->id} | Cliente: {$agendamento->cliente->name} | Token: " . substr($agendamento->cliente->fcm_token, 0, 10) . "...");
                    $this->info("Lembrete enviado para {$agendamento->cliente->name}");

                } catch (\Exception $e) {
                    Log::error("FALHA CRÍTICA ao enviar para agendamento {$agendamento->id}: " . $e->getMessage());
                }
            }
        }
    }
}
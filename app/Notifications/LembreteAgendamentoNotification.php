<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;
use App\Models\Agendamento;
use Carbon\Carbon;

class LembreteAgendamentoNotification extends Notification
{
    public function __construct(protected Agendamento $agendamento)
    {
    }

    public function via($notifiable)
    {
        // Usando o canal da biblioteca que você postou
        return [FcmChannel::class, 'database'];
    }

    public function toFcm($notifiable): FcmMessage
    {
        // Correção: Usar inicio_horario em vez de data_hora
        $hora = Carbon::parse($this->agendamento->inicio_horario)->format('H:i');
        $servico = $this->agendamento->servico->nome;
        $profissional = $this->agendamento->profissional->name;

        return FcmMessage::create()
            ->name("LembreteAgendamento")
            ->notification(FcmNotification::create()
                ->title("⏰ Hora do seu compromisso!")
                ->body("Olá, {$notifiable->name}! Não esqueça: {$servico} às {$hora} com {$profissional}.")
            )
            ->data([
                'agendamento_id' => (string) $this->agendamento->id,
                // Mantemos aqui para compatibilidade com o listener de background
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK', 
            ])
            ->android([
                'priority' => 'high', // Prioridade movida para o nível correto do Android
                'notification' => [
                    'channel_id' => 'high_importance_channel',
                    'sound' => 'default',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK', // Essencial para o push aparecer no Android
                    'sticky' => false,
                    'visibility' => 'public'
                ],
            ]);
    }

    public function toArray($notifiable)
    {
        return [
            'agendamento_id' => $this->agendamento->id,
            'mensagem' => "Lembrete de agendamento: {$this->agendamento->servico->nome} às " . Carbon::parse($this->agendamento->inicio_horario)->format('H:i'),
        ];
    }
}
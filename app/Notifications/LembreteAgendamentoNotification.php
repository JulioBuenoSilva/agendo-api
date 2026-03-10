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
        $hora = Carbon::parse($this->agendamento->data_hora)->format('H:i');
        $servico = $this->agendamento->servico->nome;
        $profissional = $this->agendamento->profissional->name;

        // 1. Criamos o recurso de notificação visual (Título e Corpo)
        $fcmNotification = FcmNotification::create()
            ->title("⏰ Hora do seu compromisso!")
            ->body("Olá, {$notifiable->name}! Não esqueça: {$servico} às {$hora} com {$profissional}.");

        // 2. Retornamos o FcmMessage conforme a classe que você enviou
        return FcmMessage::create()
            ->name("LembreteAgendamento") // Opcional, para analytics no Firebase
            ->notification($fcmNotification)
            ->data([
                'agendamento_id' => (string) $this->agendamento->id,
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK', // Importante para o Flutter abrir a tela certa
            ])
            ->android([
                'notification' => [
                    'channel_id' => 'high_importance_channel',
                    'priority' => 'high',
                    'sound' => 'default',
                ],
            ]);
    }

    public function toArray($notifiable)
    {
        return [
            'agendamento_id' => $this->agendamento->id,
            'mensagem' => "Lembrete de agendamento: {$this->agendamento->servico->nome} às " . Carbon::parse($this->agendamento->data_hora)->format('H:i'),
        ];
    }
}
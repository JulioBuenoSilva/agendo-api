<?php 
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class AgendamentoAtualizado extends Notification
{
    use Queueable;

    private $agendamento;
    private $mensagem;

    public function __construct($agendamento, $mensagem)
    {
        $this->agendamento = $agendamento;
        $this->mensagem = $mensagem;
    }

    // Agora enviamos via Banco E via Firebase (Fcm)
    public function via($notifiable)
    {
        return ['database', FcmChannel::class];
    }

    public function toFcm($notifiable): FcmMessage
    {
        return FcmMessage::create()
            ->notification(
                FcmNotification::create(
                    'Atualização no Agendo',
                    $this->mensagem
                )
            )
            ->data([
                'agendamento_id' => (string) $this->agendamento->id,
            ])
            ->android([
                'priority' => 'high',
                'notification' => [
                    'channel_id' => 'high_importance_channel',
                    'sound' => 'default',
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ],
            ]);
    }

    public function toArray($notifiable)
    {
        return [
            'agendamento_id' => $this->agendamento->id,
            'mensagem' => $this->mensagem,
            'inicio' => $this->agendamento->inicio_horario->format('d/m/Y H:i'),
            'status' => $this->agendamento->status,
        ];
    }
}
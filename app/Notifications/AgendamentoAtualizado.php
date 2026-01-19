<?php 
namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

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

    // Define que a notificação será salva no Banco de Dados
    public function via($notifiable)
    {
        return ['database'];
    }

    // O que será salvo no JSON do banco
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
<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Estabelecimento;


class SolicitacaoVinculoProfissional extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public Estabelecimento $estabelecimento;
    public $codigo;    
    
    /**
     * Create a new message instance.
     */
    public function __construct(User $user, Estabelecimento $estabelecimento, $codigo)
    {
        $this->user = $user;
        $this->estabelecimento = $estabelecimento;
        $this->codigo = $codigo;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Solicitacao Vinculo Profissional',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.novo_profissional',
            with: [
                'profissionalNome' => $this->user->name,
                'emailProfissional' => $this->user->email,
                'codigoAprovacao' => $this->codigo,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

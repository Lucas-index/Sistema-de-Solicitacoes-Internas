<?php

namespace App\Mail;

use App\Models\Notificacao;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificacaoChamado extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $mensagem,
        public string $nomeDestinatario,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Nova atualização no seu chamado — Empresa',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.notificacao-chamado',
        );
    }
}
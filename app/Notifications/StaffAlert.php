<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerta interno para a equipe (nunca enviado ao cliente).
 */
class StaffAlert extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $subject, public string $body, public ?string $url = null, public string $category = 'geral') {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->subject('[Gestão] '.$this->subject)->line($this->body);

        return $this->url ? $mail->action('Abrir no painel', $this->url) : $mail;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['subject' => $this->subject, 'body' => $this->body, 'url' => $this->url, 'category' => $this->category];
    }
}

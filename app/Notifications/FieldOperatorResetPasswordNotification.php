<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FieldOperatorResetPasswordNotification extends Notification
{
    public function __construct(
        private readonly string $resetUrl,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expire = config('auth.passwords.users.expire');

        return (new MailMessage)
            ->subject('Restablecer contraseña — Visitantes')
            ->greeting('Hola')
            ->line('Recibió este correo porque solicitó restablecer la contraseña de su cuenta.')
            ->action('Restablecer contraseña', $this->resetUrl)
            ->line("Este enlace expirará en {$expire} minutos.")
            ->line('Si no solicitó el cambio, puede ignorar este mensaje.');
    }
}

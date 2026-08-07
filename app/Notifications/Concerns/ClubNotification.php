<?php

declare(strict_types=1);

namespace App\Notifications\Concerns;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * Shared shape for every club notification.
 *
 * All of them go to mail *and* the database: mail so members hear about it
 * without logging in, database so there is an in-app record when an email is
 * missed, filtered or sent to one of the placeholder addresses.
 *
 * Implementers supply the subject, the body lines and where to send the reader.
 */
trait ClubNotification
{
    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject($this->subjectLine())
            ->greeting(__('Hello'));

        foreach ($this->bodyLines() as $line) {
            $message->line($line);
        }

        return $message
            ->action($this->actionLabel(), $this->actionUrl())
            ->salutation(__('— Musuwa Nation Investment Club'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'subject' => $this->subjectLine(),
            'body' => implode(' ', $this->bodyLines()),
            'url' => $this->actionUrl(),
        ];
    }

    abstract public function subjectLine(): string;

    /**
     * @return list<string>
     */
    abstract public function bodyLines(): array;

    abstract public function actionUrl(): string;

    abstract public function actionLabel(): string;
}

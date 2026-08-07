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
     * A rejected send is retried rather than lost.
     *
     * Club-wide notifications go to the whole roll at once, and mail providers
     * throttle bursts — Mailtrap's sandbox rejects anything past about one a
     * second. Those rejections are transient, so failing on the first attempt
     * would silently drop a member's email for a reason that fixes itself a few
     * seconds later.
     *
     * Laravel reads both of these with `property_exists`, so `backoff` has to
     * be a property and not a method.
     */
    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [10, 30, 60, 120];

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

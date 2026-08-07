<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;

final readonly class MarkNotificationsRead
{
    /**
     * Marks one notification read, or every unread one the member has.
     *
     * Scoped to the user in both cases. A notification id is a UUID and so not
     * guessable, but "not guessable" is not the same as "not authorised", and
     * this scoping is the only thing standing between one member and another
     * member's inbox.
     */
    public function handle(User $user, ?string $notificationId = null): int
    {
        $query = $user->unreadNotifications();

        if ($notificationId !== null) {
            $query->whereKey($notificationId);
        }

        return $query->update(['read_at' => now()]);
    }
}

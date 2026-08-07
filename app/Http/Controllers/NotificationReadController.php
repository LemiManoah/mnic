<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\MarkNotificationsRead;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

final readonly class NotificationReadController
{
    /**
     * Mark a single notification read.
     */
    public function update(
        string $notification,
        #[CurrentUser] User $user,
        MarkNotificationsRead $action,
    ): RedirectResponse {
        $action->handle($user, $notification);

        return back();
    }

    /**
     * Mark everything read.
     */
    public function store(
        #[CurrentUser] User $user,
        MarkNotificationsRead $action,
    ): RedirectResponse {
        $action->handle($user);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('All notifications marked as read.'),
        ]);

        return back();
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

final readonly class NotificationController
{
    public function index(Request $request, #[CurrentUser] User $user): Response
    {
        // No policy: a member's own inbox is theirs by definition, and the
        // query is scoped to them rather than filtered after the fact.
        $unreadOnly = $request->boolean('unread');

        $notifications = $user->notifications()
            ->when($unreadOnly, fn ($query) => $query->whereNull('read_at'))
            ->paginate(20)
            ->withQueryString()
            ->through(fn (DatabaseNotification $notification): array => [
                'id' => $notification->id,
                'subject' => $notification->data['subject'] ?? '',
                'body' => $notification->data['body'] ?? '',
                'url' => $notification->data['url'] ?? null,
                'read_at' => $notification->read_at?->toDateTimeString(),
                'created_at' => $notification->created_at?->diffForHumans(),
            ]);

        return Inertia::render('notification/index', [
            'notifications' => $notifications,
            'filters' => ['unread' => $unreadOnly],
            'unreadCount' => $user->unreadNotifications()->count(),
        ]);
    }
}

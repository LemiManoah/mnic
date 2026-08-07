<?php

declare(strict_types=1);

use App\Enums\ClubRole;
use App\Models\User;
use App\Notifications\MeetingScheduled;
use Illuminate\Support\Str;

/**
 * Writes a notification row directly.
 *
 * Notifications are faked suite-wide (see tests/Pest.php), so `$user->notify()`
 * would record an assertion and write nothing — and this page reads the table.
 * The `data` shape below is the one `ClubNotification::toArray` produces; that
 * contract is asserted separately in NotificationsTest.
 */
function notifyOnce(User $user): void
{
    $user->notifications()->create([
        'id' => (string) Str::uuid(),
        'type' => MeetingScheduled::class,
        'data' => [
            'subject' => 'Meeting: Half-year review',
            'body' => 'MTG-2026-002 is set for next Tuesday.',
            'url' => '/meetings',
        ],
        'read_at' => null,
    ]);
}

it('lists the signed-in member notifications', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    notifyOnce($actor->user);

    $response = $this->actingAs($actor->user)->get(route('notification.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('notification/index')
            ->has('notifications.data', 1)
            ->where('unreadCount', 1)
            ->where('filters.unread', false)
            ->where('notifications.data.0.read_at', null));
});

it('never shows one member another member inbox', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $other = memberWithRole(ClubRole::Member);

    notifyOnce($other->user);

    $this->actingAs($actor->user)
        ->get(route('notification.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('notifications.data', 0)
            ->where('unreadCount', 0));
});

it('filters down to unread only', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    notifyOnce($actor->user);
    notifyOnce($actor->user);

    $actor->user?->unreadNotifications()->limit(1)->update(['read_at' => now()]);

    $this->actingAs($actor->user)
        ->get(route('notification.index', ['unread' => 1]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('notifications.data', 1)
            ->where('filters.unread', true));
});

it('marks a single notification read', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    notifyOnce($actor->user);

    $notification = $actor->user?->notifications()->firstOrFail();

    $this->actingAs($actor->user)
        ->from(route('notification.index'))
        ->put(route('notification-read.update', $notification))
        ->assertRedirect(route('notification.index'));

    expect($actor->user?->unreadNotifications()->count())->toBe(0);
});

it('marks everything read at once', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    notifyOnce($actor->user);
    notifyOnce($actor->user);

    $this->actingAs($actor->user)
        ->from(route('notification.index'))
        ->post(route('notification-read.store'))
        ->assertRedirect(route('notification.index'));

    expect($actor->user?->unreadNotifications()->count())->toBe(0);
});

it('cannot mark another member notification read', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $other = memberWithRole(ClubRole::Member);

    notifyOnce($other->user);
    $notification = $other->user?->notifications()->firstOrFail();

    $this->actingAs($actor->user)
        ->from(route('notification.index'))
        ->put(route('notification-read.update', $notification))
        ->assertRedirect(route('notification.index'));

    // The scoping in MarkNotificationsRead is what stops this, not the fact
    // that the id happens to be a UUID.
    expect($other->user?->unreadNotifications()->count())->toBe(1);
});

it('shares the unread count on every response', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    notifyOnce($actor->user);

    $this->actingAs($actor->user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('auth.unread_notifications', 1));
});

it('shows an empty inbox without falling over', function (): void {
    $actor = memberWithRole(ClubRole::Member);

    $this->actingAs($actor->user)
        ->get(route('notification.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('notifications.data', 0)
            ->where('unreadCount', 0));
});

<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\MemberStatus;
use App\Models\Member;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification as Notifier;

/**
 * Sends a notification to members, skipping anyone with no login.
 *
 * Members exist without user accounts — the roster is the club's record, and a
 * login is granted separately. Notifying is therefore always best-effort: a
 * member with no account simply does not hear about it, and that must never be
 * an error, or recording a payment for somebody without a login would fail.
 */
final readonly class NotifyMembers
{
    /**
     * @param  Collection<int, Member>|list<Member>  $members
     */
    public function handle(Collection|array $members, Notification $notification): void
    {
        $recipients = Collection::make($members)
            ->map(fn (Member $member): ?User => $member->user)
            ->filter()
            ->unique('id')
            ->values();

        if ($recipients->isEmpty()) {
            return;
        }

        Notifier::send($recipients, $notification);
    }

    /**
     * Everyone currently on the active roll — the audience for club-wide news
     * like a new month opening or a meeting being called.
     */
    public function toActiveMembers(Notification $notification): void
    {
        $this->handle(
            Member::query()
                ->where('status', MemberStatus::Active->value)
                ->with('user')
                ->get(),
            $notification,
        );
    }
}

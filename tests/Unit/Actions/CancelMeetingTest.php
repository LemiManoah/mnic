<?php

declare(strict_types=1);

use App\Actions\CancelMeeting;
use App\Enums\MeetingStatus;
use App\Models\AuditLog;
use App\Models\Meeting;
use App\Models\Member;

it('cancels a scheduled meeting and records why', function (): void {
    $actor = Member::factory()->create();
    $meeting = Meeting::factory()->create(['status' => MeetingStatus::Scheduled]);

    $cancelled = resolve(CancelMeeting::class)
        ->handle($meeting, 'Venue double-booked.', $actor, '127.0.0.1');

    expect($cancelled->status)->toBe(MeetingStatus::Cancelled)
        ->and($cancelled->cancellation_reason)->toBe('Venue double-booked.');

    expect(AuditLog::query()
        ->where('auditable_id', $meeting->id)
        ->where('event', 'meeting.cancelled')
        ->exists())->toBeTrue();
});

it('refuses to cancel a meeting that has already been held', function (): void {
    $meeting = Meeting::factory()->create(['status' => MeetingStatus::Completed]);

    resolve(CancelMeeting::class)->handle($meeting, 'Too late.');
})->throws(InvalidArgumentException::class);

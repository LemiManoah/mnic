<?php

declare(strict_types=1);

use App\Actions\ConfirmMinutes;
use App\Actions\PublishMinutes;
use App\Actions\RecordMeetingAttendance;
use App\Actions\ScheduleMeeting;
use App\Enums\AttendanceStatus;
use App\Enums\MeetingStatus;
use App\Models\AuditLog;
use App\Models\Meeting;
use App\Models\MeetingAttendance;
use App\Models\Member;
use App\Models\Minute;

it('schedules a meeting and audits it', function (): void {
    $actor = Member::factory()->create();

    $meeting = resolve(ScheduleMeeting::class)->handle([
        'reference' => 'MTG-2026-001',
        'title' => 'Founding meeting',
        'scheduled_for' => now()->addWeek()->toDateTimeString(),
    ], $actor, '127.0.0.1');

    expect($meeting->status)->toBe(MeetingStatus::Scheduled)
        ->and($meeting->scheduled_by_member_id)->toBe($actor->id);

    expect(AuditLog::query()->where('auditable_id', $meeting->id)
        ->where('event', 'meeting.scheduled')->exists())->toBeTrue();
});

it('records attendance and marks the meeting completed', function (): void {
    $meeting = Meeting::factory()->create();
    $present = Member::factory()->create();
    $absent = Member::factory()->create();

    resolve(RecordMeetingAttendance::class)->handle($meeting, [
        $present->id => AttendanceStatus::Present->value,
        $absent->id => AttendanceStatus::Absent->value,
    ], Member::factory()->create(), '127.0.0.1');

    expect($meeting->fresh()?->status)->toBe(MeetingStatus::Completed)
        ->and($meeting->presentCount())->toBe(1);

    expect(MeetingAttendance::query()->where('meeting_id', $meeting->id)->count())->toBe(2);
});

it('overwrites a previous attendance entry rather than duplicating it', function (): void {
    $meeting = Meeting::factory()->create();
    $member = Member::factory()->create();

    $action = resolve(RecordMeetingAttendance::class);

    $action->handle($meeting, [$member->id => AttendanceStatus::Absent->value]);
    $action->handle($meeting, [$member->id => AttendanceStatus::Present->value]);

    expect(MeetingAttendance::query()->where('meeting_id', $meeting->id)->count())->toBe(1)
        ->and($meeting->presentCount())->toBe(1);
});

it('refuses to record attendance for a cancelled meeting', function (): void {
    $meeting = Meeting::factory()->cancelled()->create();

    resolve(RecordMeetingAttendance::class)
        ->handle($meeting, [Member::factory()->create()->id => AttendanceStatus::Present->value]);
})->throws(InvalidArgumentException::class);

it('publishes minutes as incrementing versions', function (): void {
    $meeting = Meeting::factory()->create();
    $actor = Member::factory()->create();

    $action = resolve(PublishMinutes::class);

    $first = $action->handle($meeting, 'First draft', $actor, '127.0.0.1');
    $second = $action->handle($meeting, 'Second draft', $actor, '127.0.0.1');

    expect($first->version)->toBe(1)
        ->and($second->version)->toBe(2)
        ->and($first->fresh()?->body)->toBe('First draft');

    expect(Minute::query()->where('meeting_id', $meeting->id)->count())->toBe(2);
});

it('confirms the minutes and confirms the meeting', function (): void {
    $meeting = Meeting::factory()->create();
    $minute = Minute::factory()->create(['meeting_id' => $meeting->id]);
    $confirmer = Member::factory()->create();

    $confirmed = resolve(ConfirmMinutes::class)->handle($minute, $confirmer, '127.0.0.1');

    expect($confirmed->isConfirmed())->toBeTrue()
        ->and($confirmed->confirmed_by_member_id)->toBe($confirmer->id)
        ->and($meeting->fresh()?->status)->toBe(MeetingStatus::Confirmed);

    expect(AuditLog::query()->where('auditable_id', $minute->id)
        ->where('event', 'minutes.confirmed')->exists())->toBeTrue();
});

it('refuses to replace confirmed minutes', function (): void {
    $meeting = Meeting::factory()->create();
    Minute::factory()->confirmed()->create(['meeting_id' => $meeting->id]);

    resolve(PublishMinutes::class)->handle($meeting, 'Sneaky edit');
})->throws(InvalidArgumentException::class);

it('refuses to confirm minutes twice', function (): void {
    $minute = Minute::factory()->confirmed()->create();

    resolve(ConfirmMinutes::class)->handle($minute, Member::factory()->create());
})->throws(InvalidArgumentException::class);

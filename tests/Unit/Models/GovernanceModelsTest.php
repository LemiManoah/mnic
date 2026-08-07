<?php

declare(strict_types=1);

use App\Enums\AttendanceStatus;
use App\Enums\MeetingStatus;
use App\Enums\ProposalStatus;
use App\Enums\VoteChoice;
use App\Models\ActionItem;
use App\Models\Meeting;
use App\Models\MeetingAttendance;
use App\Models\Member;
use App\Models\Minute;
use App\Models\Proposal;
use App\Models\ProposalEligibleVoter;
use App\Models\Vote;

test('meeting to array', function (): void {
    $meeting = Meeting::factory()->create()->refresh();

    expect(array_keys($meeting->toArray()))
        ->toBe([
            'id',
            'reference',
            'title',
            'scheduled_for',
            'location',
            'agenda',
            'status',
            'scheduled_by_member_id',
            'created_at',
            'updated_at',
            'cancellation_reason',
        ]);
});

it('relates a meeting to its attendance, minutes, proposals and actions', function (): void {
    $scheduler = Member::factory()->create();
    $meeting = Meeting::factory()->create(['scheduled_by_member_id' => $scheduler->id]);

    MeetingAttendance::factory()->create(['meeting_id' => $meeting->id]);
    Minute::factory()->create(['meeting_id' => $meeting->id]);
    Proposal::factory()->create(['meeting_id' => $meeting->id]);
    ActionItem::factory()->create(['meeting_id' => $meeting->id]);

    expect($meeting->attendances)->toHaveCount(1)
        ->and($meeting->minutes)->toHaveCount(1)
        ->and($meeting->proposals)->toHaveCount(1)
        ->and($meeting->actionItems)->toHaveCount(1)
        ->and($meeting->scheduledByMember->is($scheduler))->toBeTrue();
});

it('returns the highest numbered minute version', function (): void {
    $meeting = Meeting::factory()->create();

    Minute::factory()->create(['meeting_id' => $meeting->id, 'version' => 1]);
    $latest = Minute::factory()->create(['meeting_id' => $meeting->id, 'version' => 2]);

    expect($meeting->latestMinute()?->is($latest))->toBeTrue();
});

it('returns null when a meeting has no minutes', function (): void {
    expect(Meeting::factory()->create()->latestMinute())->toBeNull();
});

it('relates attendance and minutes back to their meeting', function (): void {
    $meeting = Meeting::factory()->create();
    $member = Member::factory()->create();

    $attendance = MeetingAttendance::factory()->create([
        'meeting_id' => $meeting->id,
        'member_id' => $member->id,
    ]);
    $minute = Minute::factory()->create(['meeting_id' => $meeting->id]);

    expect($attendance->meeting->is($meeting))->toBeTrue()
        ->and($attendance->member->is($member))->toBeTrue()
        ->and($minute->meeting->is($meeting))->toBeTrue();
});

test('proposal to array and relations', function (): void {
    $creator = Member::factory()->create();
    $meeting = Meeting::factory()->create();
    $proposal = Proposal::factory()->create([
        'meeting_id' => $meeting->id,
        'created_by_member_id' => $creator->id,
    ]);

    $member = Member::factory()->create();
    Vote::factory()->create([
        'proposal_id' => $proposal->id,
        'member_id' => $member->id,
        'choice' => VoteChoice::Against,
    ]);
    ProposalEligibleVoter::factory()->create(['proposal_id' => $proposal->id]);

    expect($proposal->meeting->is($meeting))->toBeTrue()
        ->and($proposal->createdByMember->is($creator))->toBeTrue()
        ->and($proposal->votes)->toHaveCount(1)
        ->and($proposal->eligibleVoters)->toHaveCount(1)
        ->and($proposal->countChoice(VoteChoice::Against))->toBe(1)
        ->and($proposal->countChoice(VoteChoice::For))->toBe(0);
});

it('relates a vote and an eligible voter back to their proposal', function (): void {
    $vote = Vote::factory()->create();
    $eligible = ProposalEligibleVoter::factory()->create();

    expect($vote->proposal)->not->toBeNull()
        ->and($vote->member)->not->toBeNull()
        ->and($eligible->proposal)->not->toBeNull()
        ->and($eligible->member)->not->toBeNull();
});

it('relates an action item to its meeting and owner', function (): void {
    $owner = Member::factory()->create();
    $meeting = Meeting::factory()->create();

    $item = ActionItem::factory()->create([
        'meeting_id' => $meeting->id,
        'owner_member_id' => $owner->id,
    ]);

    expect($item->meeting->is($meeting))->toBeTrue()
        ->and($item->ownerMember->is($owner))->toBeTrue();
});

it('labels every governance enum case', function (): void {
    foreach (MeetingStatus::cases() as $case) {
        expect($case->label())->not->toBe('');
    }

    foreach (AttendanceStatus::cases() as $case) {
        expect($case->label())->not->toBe('');
    }

    foreach (ProposalStatus::cases() as $case) {
        expect($case->label())->not->toBe('');
    }

    foreach (VoteChoice::cases() as $case) {
        expect($case->label())->not->toBe('');
    }
});

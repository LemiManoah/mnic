<?php

declare(strict_types=1);

use App\Actions\ConfirmMinutes;
use App\Enums\ClubRole;
use App\Models\Meeting;
use App\Models\Member;
use App\Models\Minute;

/**
 * Publishes minutes on a meeting and confirms them, so the correction path has
 * something official to supersede.
 */
function meetingWithConfirmedMinutes(): Meeting
{
    $meeting = Meeting::factory()->create();

    $minute = Minute::factory()->create([
        'meeting_id' => $meeting->id,
        'version' => 1,
        'body' => 'Original record.',
    ]);

    resolve(ConfirmMinutes::class)->handle($minute, Member::factory()->create());

    return $meeting;
}

it('lets the secretary publish a correction as a new version', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);
    $meeting = meetingWithConfirmedMinutes();

    $response = $this->actingAs($actor->user)
        ->post(route('meeting-minutes-correction.store', $meeting), [
            'body' => 'Corrected record.',
            'reason' => 'The collection figure was wrong.',
        ]);

    $response->assertRedirectToRoute('meeting.show', $meeting);

    $latest = $meeting->fresh()?->latestMinute();

    expect($latest?->version)->toBe(2)
        ->and($latest?->body)->toBe('Corrected record.')
        ->and($latest?->correction_reason)->toBe('The collection figure was wrong.')
        ->and($latest?->isConfirmed())->toBeFalse();
});

it('requires both the corrected text and a reason', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);
    $meeting = meetingWithConfirmedMinutes();

    $this->actingAs($actor->user)
        ->post(route('meeting-minutes-correction.store', $meeting), [])
        ->assertSessionHasErrors(['body', 'reason']);
});

it('denies a plain member from correcting minutes', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $meeting = meetingWithConfirmedMinutes();

    $this->actingAs($actor->user)
        ->post(route('meeting-minutes-correction.store', $meeting), [
            'body' => 'Rewritten.',
            'reason' => 'Because I say so.',
        ])
        ->assertForbidden();
});

it('does not offer a correction until the minutes are confirmed', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);
    $meeting = Meeting::factory()->create();

    Minute::factory()->create(['meeting_id' => $meeting->id, 'version' => 1]);

    $this->actingAs($actor->user)
        ->get(route('meeting.show', $meeting))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('canCorrectMinutes', false));
});

it('offers a correction once the minutes are confirmed', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);
    $meeting = meetingWithConfirmedMinutes();

    $this->actingAs($actor->user)
        ->get(route('meeting.show', $meeting))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('canCorrectMinutes', true));
});

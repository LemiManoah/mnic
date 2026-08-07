<?php

declare(strict_types=1);

use App\Enums\ClubRole;
use App\Enums\MeetingStatus;
use App\Models\Meeting;

it('lets the secretary cancel a scheduled meeting', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);
    $meeting = Meeting::factory()->create(['status' => MeetingStatus::Scheduled]);

    $response = $this->actingAs($actor->user)
        ->put(route('meeting-cancellation.update', $meeting), [
            'reason' => 'Venue double-booked.',
        ]);

    $response->assertRedirectToRoute('meeting.show', $meeting);

    expect($meeting->fresh()?->status)->toBe(MeetingStatus::Cancelled)
        ->and($meeting->fresh()?->cancellation_reason)->toBe('Venue double-booked.');
});

it('requires a reason to cancel', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);
    $meeting = Meeting::factory()->create(['status' => MeetingStatus::Scheduled]);

    $this->actingAs($actor->user)
        ->put(route('meeting-cancellation.update', $meeting), [])
        ->assertSessionHasErrors('reason');
});

it('denies a plain member from cancelling a meeting', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $meeting = Meeting::factory()->create(['status' => MeetingStatus::Scheduled]);

    $this->actingAs($actor->user)
        ->put(route('meeting-cancellation.update', $meeting), ['reason' => 'No.'])
        ->assertForbidden();
});

it('refuses to cancel a meeting that has already been held', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);
    $meeting = Meeting::factory()->create(['status' => MeetingStatus::Completed]);

    $this->actingAs($actor->user)
        ->put(route('meeting-cancellation.update', $meeting), ['reason' => 'Too late.'])
        ->assertForbidden();
});

it('does not offer cancellation on a meeting that has been held', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);
    $meeting = Meeting::factory()->create(['status' => MeetingStatus::Confirmed]);

    $this->actingAs($actor->user)
        ->get(route('meeting.show', $meeting))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('canCancel', false));
});

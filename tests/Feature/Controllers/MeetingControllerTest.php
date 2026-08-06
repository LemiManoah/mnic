<?php

declare(strict_types=1);

use App\Enums\AttendanceStatus;
use App\Enums\ClubRole;
use App\Enums\MeetingStatus;
use App\Models\Meeting;
use App\Models\Member;
use App\Models\Minute;

it('lists meetings for any authenticated member', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    Meeting::factory()->create();

    $response = $this->actingAs($actor->user)->get(route('meeting.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('meeting/index')
            ->where('canSchedule', false)
            ->has('meetings.data', 1));
});

it('allows a secretary to schedule a meeting', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);

    $response = $this->actingAs($actor->user)->post(route('meeting.store'), [
        'reference' => 'MTG-2026-010',
        'title' => 'Quarterly review',
        'scheduled_for' => now()->addWeek()->toDateTimeString(),
    ]);

    $response->assertRedirectToRoute('meeting.index');

    expect(Meeting::query()->where('reference', 'MTG-2026-010')->exists())->toBeTrue();
});

it('denies a plain member from scheduling a meeting', function (): void {
    $actor = memberWithRole(ClubRole::Member);

    $response = $this->actingAs($actor->user)->post(route('meeting.store'), [
        'reference' => 'MTG-2026-011',
        'title' => 'Unauthorised',
        'scheduled_for' => now()->addWeek()->toDateTimeString(),
    ]);

    $response->assertForbidden();
});

it('rejects a duplicate meeting reference', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);
    Meeting::factory()->create(['reference' => 'MTG-DUP']);

    $response = $this->actingAs($actor->user)->post(route('meeting.store'), [
        'reference' => 'MTG-DUP',
        'title' => 'Clash',
        'scheduled_for' => now()->addWeek()->toDateTimeString(),
    ]);

    $response->assertSessionHasErrors('reference');
});

it('shows a meeting with attendance, minutes and decisions', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);
    $meeting = Meeting::factory()->create();
    Minute::factory()->create(['meeting_id' => $meeting->id, 'body' => 'Discussion notes']);

    $response = $this->actingAs($actor->user)->get(route('meeting.show', $meeting));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('meeting/show')
            ->where('canManageMinutes', true)
            ->where('minutes.body', 'Discussion notes')
            ->has('activeMembers'));
});

it('allows a secretary to record attendance', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);
    $meeting = Meeting::factory()->create();
    $member = Member::factory()->create();

    $response = $this->actingAs($actor->user)
        ->put(route('meeting-attendance.update', $meeting), [
            'attendance' => [$member->id => AttendanceStatus::Present->value],
        ]);

    $response->assertRedirectToRoute('meeting.show', $meeting);

    expect($meeting->fresh()?->status)->toBe(MeetingStatus::Completed);
});

it('denies a plain member from recording attendance', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $meeting = Meeting::factory()->create();

    $response = $this->actingAs($actor->user)
        ->put(route('meeting-attendance.update', $meeting), [
            'attendance' => [Member::factory()->create()->id => AttendanceStatus::Present->value],
        ]);

    $response->assertForbidden();
});

it('rejects an unknown attendance status', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);
    $meeting = Meeting::factory()->create();

    $response = $this->actingAs($actor->user)
        ->put(route('meeting-attendance.update', $meeting), [
            'attendance' => [Member::factory()->create()->id => 'teleported'],
        ]);

    $response->assertSessionHasErrors();
});

it('allows a secretary to publish and confirm minutes', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);
    $meeting = Meeting::factory()->create();

    $this->actingAs($actor->user)
        ->post(route('meeting-minutes.store', $meeting), ['body' => 'Agreed the budget'])
        ->assertRedirectToRoute('meeting.show', $meeting);

    $this->actingAs($actor->user)
        ->put(route('meeting-minutes.update', $meeting))
        ->assertRedirectToRoute('meeting.show', $meeting);

    expect($meeting->fresh()?->status)->toBe(MeetingStatus::Confirmed)
        ->and($meeting->latestMinute()?->isConfirmed())->toBeTrue();
});

it('denies a treasurer from publishing minutes', function (): void {
    $actor = memberWithRole(ClubRole::Treasurer);
    $meeting = Meeting::factory()->create();

    $response = $this->actingAs($actor->user)
        ->post(route('meeting-minutes.store', $meeting), ['body' => 'Not my job']);

    $response->assertForbidden();
});

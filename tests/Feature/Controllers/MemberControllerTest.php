<?php

declare(strict_types=1);

use App\Enums\ClubRole;
use App\Enums\MemberStatus;
use App\Models\AuditLog;
use App\Models\ContributionPeriod;
use App\Models\Member;
use App\Models\MemberObligation;
use App\Models\MembershipStatusHistory;
use App\Models\Payment;

it('lists members for any authenticated member', function (): void {
    $actor = memberWithRole(ClubRole::Member);

    $response = $this->actingAs($actor->user)->get(route('member.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('member/index'));
});

it('shows a member profile to any authenticated member', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $member = memberWithRole(ClubRole::Treasurer, ['status' => MemberStatus::Active]);

    MembershipStatusHistory::factory()->create(['member_id' => $member->id]);

    $response = $this->actingAs($actor->user)->get(route('member.show', $member));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('member/show')
            ->where('member.id', $member->id)
            ->where('currentRole', ClubRole::Treasurer->value)
            ->where('canUpdate', false)
            ->where('canViewActivity', false)
            ->has('statusHistories', 1)
            ->where('auditLogs', []));
});

it('includes activity and edit access for a secretary viewing a member', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);
    $member = Member::factory()->create();

    AuditLog::factory()->create([
        'event' => 'member.created',
        'auditable_type' => Member::class,
        'auditable_id' => $member->id,
    ]);

    $response = $this->actingAs($actor->user)->get(route('member.show', $member));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('member/show')
            ->where('canUpdate', true)
            ->where('canViewActivity', true)
            ->has('auditLogs', 1));
});

it('shows the contribution ledger on a member profile', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $member = Member::factory()->create();
    $period = ContributionPeriod::factory()->forMonth(2026, 9)->create();

    MemberObligation::factory()->create([
        'contribution_period_id' => $period->id,
        'member_id' => $member->id,
        'amount' => 60000,
        'amount_paid' => 25000,
    ]);

    Payment::factory()->create([
        'member_id' => $member->id,
        'reference' => 'MM-LEDGER',
    ]);

    $response = $this->actingAs($actor->user)->get(route('member.show', $member));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('obligations', 1)
            ->where('obligations.0.period', '2026-09')
            ->where('obligations.0.outstanding', 35000)
            ->has('payments', 1)
            ->where('payments.0.reference', 'MM-LEDGER'));
});

it('shows the referring member name on a member profile', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $referrer = Member::factory()->create(['full_name' => 'Referring Member']);
    $member = Member::factory()->create(['referred_by_member_id' => $referrer->id]);

    $response = $this->actingAs($actor->user)->get(route('member.show', $member));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->where('referredByName', 'Referring Member'));
});

it('allows a secretary to view the edit member page', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);
    $member = memberWithRole(ClubRole::Member, ['status' => MemberStatus::Active]);

    $response = $this->actingAs($actor->user)->get(route('member.edit', $member));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('member/edit')
            ->where('canAssignRole', false)
            ->where('currentRole', ClubRole::Member->value)
            ->has('roleOptions', count(ClubRole::cases()))
            ->has('statusOptions', 2));
});

it('allows a secretary to create a member', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);

    $response = $this->actingAs($actor->user)->post(route('member.store'), [
        'member_number' => 'MN-0100',
        'full_name' => 'New Member',
        'phone' => '+256700000000',
        'joined_at' => now()->toDateString(),
    ]);

    $response->assertRedirectToRoute('member.index');

    $member = Member::query()->where('member_number', 'MN-0100')->first();

    expect($member)->not->toBeNull()
        ->and($member?->status)->toBe(MemberStatus::Prospective)
        ->and($member?->is_pioneer)->toBeFalse();
});

it('denies plain members from storing members', function (): void {
    $actor = memberWithRole(ClubRole::Member);

    $response = $this->actingAs($actor->user)->post(route('member.store'), [
        'member_number' => 'MN-0100',
        'full_name' => 'New Member',
        'phone' => '+256700000000',
        'joined_at' => now()->toDateString(),
    ]);

    $response->assertForbidden();
});

it('requires a unique member number when storing', function (): void {
    Member::factory()->create(['member_number' => 'MN-0100']);
    $actor = memberWithRole(ClubRole::Secretary);

    $response = $this->actingAs($actor->user)->post(route('member.store'), [
        'member_number' => 'MN-0100',
        'full_name' => 'New Member',
        'phone' => '+256700000000',
        'joined_at' => now()->toDateString(),
    ]);

    $response->assertSessionHasErrors('member_number');
});

it('allows a secretary to update a member', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);
    $member = Member::factory()->create(['full_name' => 'Old Name']);

    $response = $this->actingAs($actor->user)->put(route('member.update', $member), [
        'member_number' => $member->member_number,
        'full_name' => 'New Name',
        'phone' => $member->phone,
    ]);

    $response->assertRedirectToRoute('member.index');

    expect($member->fresh()?->full_name)->toBe('New Name');
});

it('denies plain members from updating members', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $member = Member::factory()->create();

    $response = $this->actingAs($actor->user)->put(route('member.update', $member), [
        'member_number' => $member->member_number,
        'full_name' => 'New Name',
        'phone' => $member->phone,
    ]);

    $response->assertForbidden();
});

it('creates pioneer and ordinary members from explicit checkbox values', function (bool $isPioneer): void {
    $actor = memberWithRole(ClubRole::Secretary);

    $this->actingAs($actor->user)->post(route('member.store'), [
        'member_number' => 'MN-0100',
        'full_name' => 'New Member',
        'phone' => '+256700000000',
        'joined_at' => now()->toDateString(),
        'is_pioneer' => $isPioneer,
    ])->assertSessionHasNoErrors()->assertRedirectToRoute('member.index');

    expect(Member::query()->where('member_number', 'MN-0100')->firstOrFail()->is_pioneer)->toBe($isPioneer);
})->with([true, false]);

it('allows pioneer status to be checked and unchecked', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);
    $member = Member::factory()->create();

    foreach ([true, false] as $isPioneer) {
        $this->actingAs($actor->user)->put(route('member.update', $member), [
            'member_number' => $member->member_number,
            'full_name' => $member->full_name,
            'phone' => $member->phone,
            'is_pioneer' => $isPioneer,
        ])->assertSessionHasNoErrors()->assertRedirectToRoute('member.index');

        expect($member->fresh()->is_pioneer)->toBe($isPioneer);
    }
});

it('rejects invalid pioneer values when creating or updating a member', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);
    $member = Member::factory()->create();
    $attributes = [
        'member_number' => 'MN-0100',
        'full_name' => 'New Member',
        'phone' => '+256700000000',
        'joined_at' => now()->toDateString(),
        'is_pioneer' => 'invalid',
    ];

    $this->actingAs($actor->user)->post(route('member.store'), $attributes)->assertSessionHasErrors('is_pioneer');
    $this->actingAs($actor->user)->put(route('member.update', $member), $attributes)->assertSessionHasErrors('is_pioneer');

    expect($member->fresh()->is_pioneer)->toBeFalse();
});

it('keeps pioneer status when an update omits the flag', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);
    $member = Member::factory()->create(['is_pioneer' => true]);

    $this->actingAs($actor->user)->put(route('member.update', $member), [
        'member_number' => $member->member_number,
        'full_name' => $member->full_name,
        'phone' => $member->phone,
    ])->assertSessionHasNoErrors();

    expect($member->fresh()->is_pioneer)->toBeTrue();
});

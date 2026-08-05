<?php

declare(strict_types=1);

use App\Enums\ClubRole;
use App\Enums\MemberStatus;
use App\Models\Member;

it('lists members for any authenticated member', function (): void {
    $actor = memberWithRole(ClubRole::Member);

    $response = $this->actingAs($actor->user)->get(route('member.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('member/index'));
});

it('denies plain members from creating members', function (): void {
    $actor = memberWithRole(ClubRole::Member);

    $response = $this->actingAs($actor->user)->get(route('member.create'));

    $response->assertForbidden();
});

it('allows a secretary to view the create member page', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);

    $response = $this->actingAs($actor->user)->get(route('member.create'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('member/create'));
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
        ->and($member?->status)->toBe(MemberStatus::Prospective);
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

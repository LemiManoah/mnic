<?php

declare(strict_types=1);

use App\Enums\ActionItemStatus;
use App\Enums\ClubRole;
use App\Models\ActionItem;
use App\Models\Member;

it('lists action items for any authenticated member', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    ActionItem::factory()->create();

    $response = $this->actingAs($actor->user)->get(route('action-item.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('action-item/index')
            ->where('canCreate', false)
            ->has('actionItems.data', 1)
            ->where('actionItems.data.0.can_update', false)
            ->has('statusOptions', count(ActionItemStatus::cases())));
});

it('allows a secretary to assign an action', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);
    $owner = Member::factory()->create();

    $response = $this->actingAs($actor->user)->post(route('action-item.store'), [
        'title' => 'Register the club',
        'owner_member_id' => $owner->id,
        'due_on' => now()->addWeek()->toDateString(),
    ]);

    $response->assertRedirectToRoute('action-item.index');

    expect(ActionItem::query()->where('title', 'Register the club')->exists())->toBeTrue();
});

it('denies a plain member from assigning an action', function (): void {
    $actor = memberWithRole(ClubRole::Member);

    $response = $this->actingAs($actor->user)->post(route('action-item.store'), [
        'title' => 'Unauthorised',
    ]);

    $response->assertForbidden();
});

it('lets the owner update their own action', function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $item = ActionItem::factory()->create(['owner_member_id' => $actor->id]);

    $response = $this->actingAs($actor->user)->put(route('action-item.update', $item), [
        'status' => ActionItemStatus::Completed->value,
    ]);

    $response->assertRedirectToRoute('action-item.index');

    expect($item->fresh()?->status)->toBe(ActionItemStatus::Completed);
});

it("stops a member updating somebody else's action", function (): void {
    $actor = memberWithRole(ClubRole::Member);
    $item = ActionItem::factory()->create([
        'owner_member_id' => Member::factory()->create()->id,
    ]);

    $response = $this->actingAs($actor->user)->put(route('action-item.update', $item), [
        'status' => ActionItemStatus::Completed->value,
    ]);

    $response->assertForbidden();
});

it('lets an officer update any action', function (): void {
    $actor = memberWithRole(ClubRole::Secretary);
    $item = ActionItem::factory()->create([
        'owner_member_id' => Member::factory()->create()->id,
    ]);

    $response = $this->actingAs($actor->user)->put(route('action-item.update', $item), [
        'status' => ActionItemStatus::Blocked->value,
    ]);

    $response->assertRedirectToRoute('action-item.index');

    expect($item->fresh()?->status)->toBe(ActionItemStatus::Blocked);
});

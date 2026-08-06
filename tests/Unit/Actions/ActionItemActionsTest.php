<?php

declare(strict_types=1);

use App\Actions\CreateActionItem;
use App\Actions\UpdateActionItemStatus;
use App\Enums\ActionItemStatus;
use App\Models\ActionItem;
use App\Models\AuditLog;
use App\Models\Member;

it('creates an open action item', function (): void {
    $owner = Member::factory()->create();
    $actor = Member::factory()->create();

    $item = resolve(CreateActionItem::class)->handle([
        'title' => 'Open the club bank account',
        'owner_member_id' => $owner->id,
        'due_on' => now()->addWeek()->toDateString(),
    ], $actor, '127.0.0.1');

    expect($item)->toBeInstanceOf(ActionItem::class)
        ->and($item->status)->toBe(ActionItemStatus::Open)
        ->and($item->owner_member_id)->toBe($owner->id);

    expect(AuditLog::query()->where('auditable_id', $item->id)
        ->where('event', 'action_item.created')->exists())->toBeTrue();
});

it('stamps completion time when an action is completed', function (): void {
    $item = ActionItem::factory()->create();

    $updated = resolve(UpdateActionItemStatus::class)
        ->handle($item, ActionItemStatus::Completed, Member::factory()->create(), '127.0.0.1');

    expect($updated->status)->toBe(ActionItemStatus::Completed)
        ->and($updated->completed_at)->not->toBeNull();
});

it('clears completion time when an action moves back off completed', function (): void {
    $item = ActionItem::factory()->create([
        'status' => ActionItemStatus::Completed,
        'completed_at' => now(),
    ]);

    $updated = resolve(UpdateActionItemStatus::class)->handle($item, ActionItemStatus::InProgress);

    expect($updated->status)->toBe(ActionItemStatus::InProgress)
        ->and($updated->completed_at)->toBeNull();
});

it('labels every action item status', function (): void {
    foreach (ActionItemStatus::cases() as $status) {
        expect($status->label())->not->toBe('');
    }
});

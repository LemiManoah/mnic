<?php

declare(strict_types=1);

use App\Actions\TransferPosition;
use App\Enums\ClubPosition;
use App\Models\AuditLog;
use App\Models\Member;
use App\Models\PositionHolding;
use App\Models\Proposal;

it('transfers a position and preserves the previous holder history', function (): void {
    $outgoing = Member::factory()->create(['position' => ClubPosition::Treasurer]);
    $successor = Member::factory()->create();
    $actor = Member::factory()->create();
    $proposal = Proposal::factory()->create();

    $current = PositionHolding::factory()->create([
        'member_id' => $outgoing->id,
        'position' => ClubPosition::Treasurer,
        'held_from' => '2026-01-01',
        'held_to' => null,
    ]);

    $holding = resolve(TransferPosition::class)->handle(
        ClubPosition::Treasurer,
        $successor,
        '2026-08-06',
        $actor,
        $proposal,
        'Election passed',
        '127.0.0.1',
    );

    expect($current->fresh()?->held_to?->toDateString())->toBe('2026-08-06')
        ->and($outgoing->fresh()?->position)->toBeNull()
        ->and($successor->fresh()?->position)->toBe(ClubPosition::Treasurer)
        ->and($holding->member_id)->toBe($successor->id)
        ->and($holding->elected_via_proposal_id)->toBe($proposal->id);

    expect(AuditLog::query()->where('auditable_id', $holding->id)->where('event', 'position.transferred')->exists())
        ->toBeTrue();
});

it('refuses to transfer a position to the member who already holds it', function (): void {
    $member = Member::factory()->create(['position' => ClubPosition::Chairperson]);

    PositionHolding::factory()->create([
        'member_id' => $member->id,
        'position' => ClubPosition::Chairperson,
        'held_to' => null,
    ]);

    resolve(TransferPosition::class)->handle(
        ClubPosition::Chairperson,
        $member,
        '2026-08-06',
    );
})->throws(InvalidArgumentException::class);

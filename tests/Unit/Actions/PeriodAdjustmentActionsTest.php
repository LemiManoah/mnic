<?php

declare(strict_types=1);

use App\Actions\ApprovePeriodAdjustment;
use App\Actions\RejectPeriodAdjustment;
use App\Actions\RequestPeriodAdjustment;
use App\Enums\AdjustmentStatus;
use App\Models\AuditLog;
use App\Models\ContributionPeriod;
use App\Models\Member;
use App\Models\PeriodAdjustment;

it('raises an adjustment against a closed month', function (): void {
    $period = ContributionPeriod::factory()->closed()->create();
    $treasurer = Member::factory()->create();

    $adjustment = resolve(RequestPeriodAdjustment::class)
        ->handle($period, -60000, 'A payment was counted twice.', $treasurer, '127.0.0.1');

    expect($adjustment->status)->toBe(AdjustmentStatus::Pending)
        ->and($adjustment->amount)->toBe(-60000)
        ->and($adjustment->requested_by_member_id)->toBe($treasurer->id);

    expect(AuditLog::query()
        ->where('auditable_id', $adjustment->id)
        ->where('event', 'period_adjustment.requested')
        ->exists())->toBeTrue();
});

it('refuses to adjust a month that is still open', function (): void {
    // An open month can be corrected at source, so routing it through a
    // two-signature process would make the process feel like a formality.
    resolve(RequestPeriodAdjustment::class)->handle(
        ContributionPeriod::factory()->create(),
        -60000,
        'Nope.',
        Member::factory()->create(),
    );
})->throws(InvalidArgumentException::class);

it('refuses an adjustment of zero', function (): void {
    resolve(RequestPeriodAdjustment::class)->handle(
        ContributionPeriod::factory()->closed()->create(),
        0,
        'Corrects nothing.',
        Member::factory()->create(),
    );
})->throws(InvalidArgumentException::class);

it('approves an adjustment raised by somebody else', function (): void {
    $requester = Member::factory()->create();
    $approver = Member::factory()->create();

    $adjustment = PeriodAdjustment::factory()->create([
        'requested_by_member_id' => $requester->id,
    ]);

    $approved = resolve(ApprovePeriodAdjustment::class)->handle($adjustment, $approver);

    expect($approved->status)->toBe(AdjustmentStatus::Approved)
        ->and($approved->reviewed_by_member_id)->toBe($approver->id)
        ->and($approved->reviewed_at)->not->toBeNull();
});

it('refuses to let the requester approve their own adjustment', function (): void {
    $requester = Member::factory()->create();

    $adjustment = PeriodAdjustment::factory()->create([
        'requested_by_member_id' => $requester->id,
    ]);

    resolve(ApprovePeriodAdjustment::class)->handle($adjustment, $requester);
})->throws(InvalidArgumentException::class);

it('refuses to approve an adjustment that was already decided', function (): void {
    resolve(ApprovePeriodAdjustment::class)
        ->handle(PeriodAdjustment::factory()->approved()->create(), Member::factory()->create());
})->throws(InvalidArgumentException::class);

it('rejects an adjustment with a reason', function (): void {
    $requester = Member::factory()->create();
    $approver = Member::factory()->create();

    $adjustment = PeriodAdjustment::factory()->create([
        'requested_by_member_id' => $requester->id,
    ]);

    $rejected = resolve(RejectPeriodAdjustment::class)
        ->handle($adjustment, $approver, 'The original figure was right.');

    expect($rejected->status)->toBe(AdjustmentStatus::Rejected)
        ->and($rejected->rejection_reason)->toBe('The original figure was right.');
});

it('refuses to let the requester reject their own adjustment', function (): void {
    $requester = Member::factory()->create();

    $adjustment = PeriodAdjustment::factory()->create([
        'requested_by_member_id' => $requester->id,
    ]);

    resolve(RejectPeriodAdjustment::class)->handle($adjustment, $requester, 'No.');
})->throws(InvalidArgumentException::class);

it('refuses to reject an adjustment that was already decided', function (): void {
    resolve(RejectPeriodAdjustment::class)
        ->handle(PeriodAdjustment::factory()->rejected()->create(), Member::factory()->create(), 'No.');
})->throws(InvalidArgumentException::class);

it('relates an adjustment to its period and both officers', function (): void {
    $period = ContributionPeriod::factory()->closed()->create();
    $requester = Member::factory()->create();
    $reviewer = Member::factory()->create();

    $adjustment = PeriodAdjustment::factory()->approved()->create([
        'contribution_period_id' => $period->id,
        'requested_by_member_id' => $requester->id,
        'reviewed_by_member_id' => $reviewer->id,
    ]);

    expect($adjustment->contributionPeriod->is($period))->toBeTrue()
        ->and($adjustment->requestedByMember?->is($requester))->toBeTrue()
        ->and($adjustment->reviewedByMember?->is($reviewer))->toBeTrue()
        ->and($adjustment->isPending())->toBeFalse();
});

it('labels every adjustment status', function (): void {
    foreach (AdjustmentStatus::cases() as $status) {
        expect($status->label())->not->toBe('');
    }
});

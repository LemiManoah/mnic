<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AdjustmentStatus;
use Carbon\CarbonInterface;
use Database\Factories\PeriodAdjustmentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A correcting entry against a month that has already been closed.
 *
 * The locked reconciliation is never touched. An adjustment sits beside it,
 * carrying its own reason and its own pair of signatures, so the figures the
 * club signed off stay exactly as they were signed off and the correction is
 * visible as a correction rather than a quiet edit.
 *
 * @property-read string $id
 * @property-read string $contribution_period_id
 * @property-read int $amount
 * @property-read string $reason
 * @property-read AdjustmentStatus $status
 * @property-read string|null $requested_by_member_id
 * @property-read CarbonInterface $requested_at
 * @property-read string|null $reviewed_by_member_id
 * @property-read CarbonInterface|null $reviewed_at
 * @property-read string|null $rejection_reason
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class PeriodAdjustment extends Model
{
    /** @use HasFactory<PeriodAdjustmentFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'contribution_period_id' => 'string',
            'amount' => 'integer',
            'reason' => 'string',
            'status' => AdjustmentStatus::class,
            'requested_by_member_id' => 'string',
            'requested_at' => 'datetime',
            'reviewed_by_member_id' => 'string',
            'reviewed_at' => 'datetime',
            'rejection_reason' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ContributionPeriod, $this>
     */
    public function contributionPeriod(): BelongsTo
    {
        return $this->belongsTo(ContributionPeriod::class);
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function requestedByMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'requested_by_member_id');
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function reviewedByMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'reviewed_by_member_id');
    }

    public function isPending(): bool
    {
        return $this->status === AdjustmentStatus::Pending;
    }
}

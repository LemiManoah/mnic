<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ObligationStatus;
use Carbon\CarbonInterface;
use Database\Factories\MemberObligationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read string $id
 * @property-read string $contribution_period_id
 * @property-read string $member_id
 * @property-read int $amount
 * @property-read int $amount_paid
 * @property-read ObligationStatus $status
 * @property-read string|null $adjusted_by_member_id
 * @property-read CarbonInterface|null $adjusted_at
 * @property-read string|null $adjustment_reason
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class MemberObligation extends Model
{
    /** @use HasFactory<MemberObligationFactory> */
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
            'member_id' => 'string',
            'amount' => 'integer',
            'amount_paid' => 'integer',
            'status' => ObligationStatus::class,
            'adjusted_by_member_id' => 'string',
            'adjusted_at' => 'datetime',
            'adjustment_reason' => 'string',
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
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * @return HasMany<PaymentAllocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function adjustedByMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'adjusted_by_member_id');
    }

    public function outstanding(): int
    {
        if (! $this->status->isSettleable()) {
            return 0;
        }

        return max($this->amount - $this->amount_paid, 0);
    }
}

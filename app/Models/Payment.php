<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Carbon\CarbonInterface;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read string $id
 * @property-read string|null $contribution_period_id
 * @property-read string $member_id
 * @property-read int $withdrawal_fee_amount
 * @property-read int|null $contribution_due_amount
 * @property-read int $amount
 * @property-read int $unapplied_amount
 * @property-read CarbonInterface $paid_on
 * @property-read PaymentMethod $method
 * @property-read string $reference
 * @property-read string|null $notes
 * @property-read PaymentStatus $status
 * @property-read string|null $recorded_by_member_id
 * @property-read string|null $reviewed_by_member_id
 * @property-read CarbonInterface|null $reviewed_at
 * @property-read string|null $rejection_reason
 * @property-read string|null $reversed_by_member_id
 * @property-read CarbonInterface|null $reversed_at
 * @property-read string|null $reversal_reason
 * @property-read string|null $reversal_requested_by_member_id
 * @property-read CarbonInterface|null $reversal_requested_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'member_id' => 'string',
            'contribution_period_id' => 'string',
            'amount' => 'integer',
            'withdrawal_fee_amount' => 'integer',
            'contribution_due_amount' => 'integer',
            'unapplied_amount' => 'integer',
            'paid_on' => 'date',
            'method' => PaymentMethod::class,
            'reference' => 'string',
            'notes' => 'string',
            'status' => PaymentStatus::class,
            'recorded_by_member_id' => 'string',
            'reviewed_by_member_id' => 'string',
            'reviewed_at' => 'datetime',
            'rejection_reason' => 'string',
            'reversed_by_member_id' => 'string',
            'reversed_at' => 'datetime',
            'reversal_reason' => 'string',
            'reversal_requested_by_member_id' => 'string',
            'reversal_requested_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ContributionPeriod, $this> */
    public function contributionPeriod(): BelongsTo
    {
        return $this->belongsTo(ContributionPeriod::class);
    }

    public function contributionAmount(): int
    {
        return $this->amount - $this->withdrawal_fee_amount;
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function recordedByMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'recorded_by_member_id');
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function reviewedByMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'reviewed_by_member_id');
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function reversedByMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'reversed_by_member_id');
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function reversalRequestedByMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'reversal_requested_by_member_id');
    }

    /**
     * @return HasMany<PaymentAllocation, $this>
     */
    public function allocations(): HasMany
    {
        return $this->hasMany(PaymentAllocation::class);
    }

    /**
     * @return HasMany<PaymentEvidence, $this>
     */
    public function evidence(): HasMany
    {
        return $this->hasMany(PaymentEvidence::class);
    }
}

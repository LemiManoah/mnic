<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ObligationStatus;
use Carbon\CarbonInterface;
use Database\Factories\MemberObligationFactory;
use Illuminate\Database\Eloquent\Builder;
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
     * Obligations still owing after the grace period closed.
     *
     * Deliberately a plain static query rather than an Eloquent scope. The
     * three tools in this repo cannot agree on scopes: larastan does not
     * resolve `#[Scope]`-attributed methods, Rector insists `scopeX` methods be
     * protected, and the Pest strict preset forbids protected methods on
     * models. A named query method sidesteps all three and reads no worse.
     *
     * @return Builder<self>
     */
    public static function overdueQuery(): Builder
    {
        return self::query()
            ->whereIn('status', [ObligationStatus::Unpaid->value, ObligationStatus::PartiallyPaid->value])
            ->whereHas('contributionPeriod', fn (Builder $period): Builder => $period
                ->whereDate('grace_ends_on', '<', today()));
    }

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
     * Overdue is derived from the period's grace date rather than stored as a
     * status of its own.
     *
     * A stored flag would need a sweep to set it and another to unset it when
     * somebody pays, and it would be wrong in between. Deriving it means the
     * answer is always current and there is no migration to run when the club
     * changes the grace day — the effective-dated setting already handles that.
     */
    public function isOverdue(): bool
    {
        if (! $this->status->isSettleable()) {
            return false;
        }

        return $this->contributionPeriod?->grace_ends_on->isBefore(today()) ?? false;
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

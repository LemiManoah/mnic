<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ReconciliationStatus;
use Carbon\CarbonInterface;
use Database\Factories\ReconciliationFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read string $id
 * @property-read string $contribution_period_id
 * @property-read string|null $external_account_id
 * @property-read int $opening_balance
 * @property-read int $statement_closing_balance
 * @property-read int $expected_closing_balance
 * @property-read int $difference
 * @property-read ReconciliationStatus $status
 * @property-read string|null $notes
 * @property-read string|null $prepared_by_member_id
 * @property-read CarbonInterface|null $submitted_at
 * @property-read string|null $confirmed_by_member_id
 * @property-read CarbonInterface|null $confirmed_at
 * @property-read CarbonInterface|null $locked_at
 * @property-read string|null $rejection_reason
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class Reconciliation extends Model
{
    /** @use HasFactory<ReconciliationFactory> */
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
            'external_account_id' => 'string',
            'opening_balance' => 'integer',
            'statement_closing_balance' => 'integer',
            'expected_closing_balance' => 'integer',
            'difference' => 'integer',
            'status' => ReconciliationStatus::class,
            'notes' => 'string',
            'prepared_by_member_id' => 'string',
            'submitted_at' => 'datetime',
            'confirmed_by_member_id' => 'string',
            'confirmed_at' => 'datetime',
            'locked_at' => 'datetime',
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
     * @return BelongsTo<ExternalAccount, $this>
     */
    public function externalAccount(): BelongsTo
    {
        return $this->belongsTo(ExternalAccount::class);
    }

    /**
     * @return HasMany<ReconciliationItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ReconciliationItem::class);
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function preparedByMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'prepared_by_member_id');
    }

    public function isLocked(): bool
    {
        return $this->status === ReconciliationStatus::Locked;
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExpenseCategory;
use App\Enums\ExpenseStatus;
use Carbon\CarbonInterface;
use Database\Factories\ExpenseFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read string $id
 * @property-read string $reference
 * @property-read string $purpose
 * @property-read ExpenseCategory $category
 * @property-read string $payee
 * @property-read int $amount
 * @property-read CarbonInterface $incurred_on
 * @property-read ExpenseStatus $status
 * @property-read string|null $resolution_reference
 * @property-read string|null $external_account_id
 * @property-read string|null $payment_reference
 * @property-read CarbonInterface|null $paid_on
 * @property-read string|null $requested_by_member_id
 * @property-read string|null $approved_by_member_id
 * @property-read CarbonInterface|null $approved_at
 * @property-read string|null $verified_by_member_id
 * @property-read CarbonInterface|null $verified_at
 * @property-read string|null $rejection_reason
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class Expense extends Model
{
    /** @use HasFactory<ExpenseFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'reference' => 'string',
            'purpose' => 'string',
            'category' => ExpenseCategory::class,
            'payee' => 'string',
            'amount' => 'integer',
            'incurred_on' => 'date',
            'status' => ExpenseStatus::class,
            'resolution_reference' => 'string',
            'external_account_id' => 'string',
            'payment_reference' => 'string',
            'paid_on' => 'date',
            'requested_by_member_id' => 'string',
            'approved_by_member_id' => 'string',
            'approved_at' => 'datetime',
            'verified_by_member_id' => 'string',
            'verified_at' => 'datetime',
            'rejection_reason' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
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
    public function approvedByMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'approved_by_member_id');
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function verifiedByMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'verified_by_member_id');
    }

    /**
     * @return BelongsTo<ExternalAccount, $this>
     */
    public function externalAccount(): BelongsTo
    {
        return $this->belongsTo(ExternalAccount::class);
    }

    /**
     * @return HasMany<ExpenseEvidence, $this>
     */
    public function evidence(): HasMany
    {
        return $this->hasMany(ExpenseEvidence::class);
    }
}

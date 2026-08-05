<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContributionPeriodStatus;
use Carbon\CarbonInterface;
use Database\Factories\ContributionPeriodFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read string $id
 * @property-read int $year
 * @property-read int $month
 * @property-read int $amount
 * @property-read CarbonInterface $due_date
 * @property-read CarbonInterface $grace_ends_on
 * @property-read ContributionPeriodStatus $status
 * @property-read string|null $opened_by_member_id
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class ContributionPeriod extends Model
{
    /** @use HasFactory<ContributionPeriodFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'year' => 'integer',
            'month' => 'integer',
            'amount' => 'integer',
            'due_date' => 'date',
            'grace_ends_on' => 'date',
            'status' => ContributionPeriodStatus::class,
            'opened_by_member_id' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<MemberObligation, $this>
     */
    public function obligations(): HasMany
    {
        return $this->hasMany(MemberObligation::class);
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function openedByMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'opened_by_member_id');
    }

    public function label(): string
    {
        return sprintf('%04d-%02d', $this->year, $this->month);
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MemberStatus;
use Carbon\CarbonInterface;
use Database\Factories\MembershipStatusHistoryFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read string $id
 * @property-read string $member_id
 * @property-read MemberStatus|null $from_status
 * @property-read MemberStatus $to_status
 * @property-read string $reason
 * @property-read CarbonInterface $effective_date
 * @property-read string|null $resolution_reference
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class MembershipStatusHistory extends Model
{
    /** @use HasFactory<MembershipStatusHistoryFactory> */
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
            'from_status' => MemberStatus::class,
            'to_status' => MemberStatus::class,
            'reason' => 'string',
            'effective_date' => 'date',
            'resolution_reference' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}

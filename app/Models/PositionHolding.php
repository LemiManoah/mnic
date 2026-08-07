<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ClubPosition;
use Carbon\CarbonInterface;
use Database\Factories\PositionHoldingFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read string $id
 * @property-read string $member_id
 * @property-read ClubPosition $position
 * @property-read CarbonInterface $held_from
 * @property-read CarbonInterface|null $held_to
 * @property-read string|null $elected_via_position_poll_id
 * @property-read string|null $appointed_by_member_id
 * @property-read string|null $transfer_reason
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class PositionHolding extends Model
{
    /** @use HasFactory<PositionHoldingFactory> */
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
            'position' => ClubPosition::class,
            'held_from' => 'date',
            'held_to' => 'date',
            'elected_via_position_poll_id' => 'string',
            'appointed_by_member_id' => 'string',
            'transfer_reason' => 'string',
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

    /**
     * @return BelongsTo<PositionPoll, $this>
     */
    public function electedViaPositionPoll(): BelongsTo
    {
        return $this->belongsTo(PositionPoll::class, 'elected_via_position_poll_id');
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function appointedByMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'appointed_by_member_id');
    }
}

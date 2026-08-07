<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\PositionPollVoteFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read string $id
 * @property-read string $position_poll_id
 * @property-read string $member_id
 * @property-read string $position_poll_candidate_id
 * @property-read CarbonInterface $cast_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class PositionPollVote extends Model
{
    /** @use HasFactory<PositionPollVoteFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'position_poll_id' => 'string',
            'member_id' => 'string',
            'position_poll_candidate_id' => 'string',
            'cast_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<PositionPoll, $this>
     */
    public function positionPoll(): BelongsTo
    {
        return $this->belongsTo(PositionPoll::class);
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * @return BelongsTo<PositionPollCandidate, $this>
     */
    public function candidate(): BelongsTo
    {
        return $this->belongsTo(PositionPollCandidate::class, 'position_poll_candidate_id');
    }
}

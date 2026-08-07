<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\PositionPollCandidateFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read string $id
 * @property-read string $position_poll_id
 * @property-read string $member_id
 * @property-read string|null $nominated_by_member_id
 * @property-read string|null $manifesto
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class PositionPollCandidate extends Model
{
    /** @use HasFactory<PositionPollCandidateFactory> */
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
            'nominated_by_member_id' => 'string',
            'manifesto' => 'string',
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
     * @return BelongsTo<Member, $this>
     */
    public function nominatedByMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'nominated_by_member_id');
    }

    /**
     * @return HasMany<PositionPollVote, $this>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(PositionPollVote::class);
    }
}

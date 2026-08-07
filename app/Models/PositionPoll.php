<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ClubPosition;
use App\Enums\PositionPollStatus;
use Carbon\CarbonInterface;
use Database\Factories\PositionPollFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An election for one office, decided by plurality.
 *
 * @property-read string $id
 * @property-read ClubPosition $position
 * @property-read string $title
 * @property-read string|null $description
 * @property-read PositionPollStatus $status
 * @property-read CarbonInterface|null $opened_at
 * @property-read CarbonInterface|null $closes_at
 * @property-read CarbonInterface|null $closed_at
 * @property-read int $eligible_voter_count
 * @property-read int $quorum_required
 * @property-read string|null $winning_candidate_id
 * @property-read string|null $outcome_note
 * @property-read string|null $created_by_member_id
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class PositionPoll extends Model
{
    /** @use HasFactory<PositionPollFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'position' => ClubPosition::class,
            'title' => 'string',
            'description' => 'string',
            'status' => PositionPollStatus::class,
            'opened_at' => 'datetime',
            'closes_at' => 'datetime',
            'closed_at' => 'datetime',
            'eligible_voter_count' => 'integer',
            'quorum_required' => 'integer',
            'winning_candidate_id' => 'string',
            'outcome_note' => 'string',
            'created_by_member_id' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<PositionPollCandidate, $this>
     */
    public function candidates(): HasMany
    {
        return $this->hasMany(PositionPollCandidate::class);
    }

    /**
     * @return HasMany<PositionPollEligibleVoter, $this>
     */
    public function eligibleVoters(): HasMany
    {
        return $this->hasMany(PositionPollEligibleVoter::class);
    }

    /**
     * @return HasMany<PositionPollVote, $this>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(PositionPollVote::class);
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function createdByMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'created_by_member_id');
    }

    /**
     * @return BelongsTo<PositionPollCandidate, $this>
     */
    public function winningCandidate(): BelongsTo
    {
        return $this->belongsTo(PositionPollCandidate::class, 'winning_candidate_id');
    }

    public function isOpen(): bool
    {
        return $this->status === PositionPollStatus::Open;
    }

    /**
     * Whether the result is settled, one way or the other.
     */
    public function isClosed(): bool
    {
        return $this->status === PositionPollStatus::Decided
            || $this->status === PositionPollStatus::Failed;
    }
}

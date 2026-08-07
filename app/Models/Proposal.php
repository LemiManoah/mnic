<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProposalStatus;
use App\Enums\VoteChoice;
use Carbon\CarbonInterface;
use Database\Factories\ProposalFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read string $id
 * @property-read string|null $meeting_id
 * @property-read string $title
 * @property-read string $description
 * @property-read ProposalStatus $status
 * @property-read CarbonInterface|null $opened_at
 * @property-read CarbonInterface|null $closes_at
 * @property-read CarbonInterface|null $closed_at
 * @property-read int $eligible_voter_count
 * @property-read int $quorum_required
 * @property-read int $approval_percent
 * @property-read string|null $outcome_note
 * @property-read string|null $created_by_member_id
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class Proposal extends Model
{
    /** @use HasFactory<ProposalFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'meeting_id' => 'string',
            'title' => 'string',
            'description' => 'string',
            'status' => ProposalStatus::class,
            'opened_at' => 'datetime',
            'closes_at' => 'datetime',
            'closed_at' => 'datetime',
            'eligible_voter_count' => 'integer',
            'quorum_required' => 'integer',
            'approval_percent' => 'integer',
            'outcome_note' => 'string',
            'created_by_member_id' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Meeting, $this>
     */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    /**
     * @return HasMany<Vote, $this>
     */
    public function votes(): HasMany
    {
        return $this->hasMany(Vote::class);
    }

    /**
     * @return HasMany<ProposalEligibleVoter, $this>
     */
    public function eligibleVoters(): HasMany
    {
        return $this->hasMany(ProposalEligibleVoter::class);
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function createdByMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'created_by_member_id');
    }

    public function countChoice(VoteChoice $choice): int
    {
        return $this->votes()->where('choice', $choice->value)->count();
    }
}

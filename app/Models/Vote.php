<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\VoteChoice;
use Carbon\CarbonInterface;
use Database\Factories\VoteFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read string $id
 * @property-read string $proposal_id
 * @property-read string $member_id
 * @property-read VoteChoice $choice
 * @property-read bool $has_conflict
 * @property-read string|null $conflict_note
 * @property-read CarbonInterface $cast_at
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class Vote extends Model
{
    /** @use HasFactory<VoteFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'proposal_id' => 'string',
            'member_id' => 'string',
            'choice' => VoteChoice::class,
            'has_conflict' => 'boolean',
            'conflict_note' => 'string',
            'cast_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Proposal, $this>
     */
    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}

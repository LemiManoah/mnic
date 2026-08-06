<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActionItemStatus;
use Carbon\CarbonInterface;
use Database\Factories\ActionItemFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read string $id
 * @property-read string|null $meeting_id
 * @property-read string|null $proposal_id
 * @property-read string $title
 * @property-read string|null $description
 * @property-read string|null $owner_member_id
 * @property-read CarbonInterface|null $due_on
 * @property-read ActionItemStatus $status
 * @property-read CarbonInterface|null $completed_at
 * @property-read string|null $created_by_member_id
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class ActionItem extends Model
{
    /** @use HasFactory<ActionItemFactory> */
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
            'proposal_id' => 'string',
            'title' => 'string',
            'description' => 'string',
            'owner_member_id' => 'string',
            'due_on' => 'date',
            'status' => ActionItemStatus::class,
            'completed_at' => 'datetime',
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
     * @return BelongsTo<Member, $this>
     */
    public function ownerMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'owner_member_id');
    }
}

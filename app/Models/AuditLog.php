<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\AuditLogFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property-read string $id
 * @property-read string|null $actor_member_id
 * @property-read string $event
 * @property-read string $auditable_type
 * @property-read string $auditable_id
 * @property-read array<string, mixed>|null $before
 * @property-read array<string, mixed>|null $after
 * @property-read string|null $ip_address
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class AuditLog extends Model
{
    /** @use HasFactory<AuditLogFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'actor_member_id' => 'string',
            'event' => 'string',
            'auditable_type' => 'string',
            'auditable_id' => 'string',
            'before' => 'array',
            'after' => 'array',
            'ip_address' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function actorMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'actor_member_id');
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\MinuteFactory;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read string $id
 * @property-read string $meeting_id
 * @property-read int $version
 * @property-read string $body
 * @property-read string|null $published_by_member_id
 * @property-read CarbonInterface|null $confirmed_at
 * @property-read string|null $confirmed_by_member_id
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
#[Table(name: 'minutes')]
final class Minute extends Model
{
    /** @use HasFactory<MinuteFactory> */
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
            'version' => 'integer',
            'body' => 'string',
            'published_by_member_id' => 'string',
            'confirmed_at' => 'datetime',
            'confirmed_by_member_id' => 'string',
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

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }
}

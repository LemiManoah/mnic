<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\ReconciliationItemFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read string $id
 * @property-read string $reconciliation_id
 * @property-read string $description
 * @property-read int $amount
 * @property-read bool $is_resolved
 * @property-read string|null $assigned_to_member_id
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class ReconciliationItem extends Model
{
    /** @use HasFactory<ReconciliationItemFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'reconciliation_id' => 'string',
            'description' => 'string',
            'amount' => 'integer',
            'is_resolved' => 'boolean',
            'assigned_to_member_id' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Reconciliation, $this>
     */
    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(Reconciliation::class);
    }
}

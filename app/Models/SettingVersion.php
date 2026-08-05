<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\SettingVersionFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read string $id
 * @property-read string $setting_id
 * @property-read string $value
 * @property-read CarbonInterface $effective_from
 * @property-read string|null $created_by_member_id
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class SettingVersion extends Model
{
    /** @use HasFactory<SettingVersionFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'setting_id' => 'string',
            'value' => 'string',
            'effective_from' => 'date',
            'created_by_member_id' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Setting, $this>
     */
    public function setting(): BelongsTo
    {
        return $this->belongsTo(Setting::class);
    }

    /**
     * @return BelongsTo<Member, $this>
     */
    public function createdByMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'created_by_member_id');
    }
}

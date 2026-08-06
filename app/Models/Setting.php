<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SettingValueType;
use Carbon\CarbonInterface;
use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read string $id
 * @property-read string $key
 * @property-read string $label
 * @property-read SettingValueType $type
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'key' => 'string',
            'label' => 'string',
            'type' => SettingValueType::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<SettingVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(SettingVersion::class);
    }

    public function currentVersion(): ?SettingVersion
    {
        return $this->versions()
            ->whereDate('effective_from', '<=', now()->toDateString())
            ->orderByDesc('effective_from')->latest()
            ->first();
    }
}

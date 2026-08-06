<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ExternalAccountType;
use Carbon\CarbonInterface;
use Database\Factories\ExternalAccountFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read string $id
 * @property-read string $name
 * @property-read ExternalAccountType $type
 * @property-read string|null $institution
 * @property-read string $masked_identifier
 * @property-read bool $is_active
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class ExternalAccount extends Model
{
    /** @use HasFactory<ExternalAccountFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'name' => 'string',
            'type' => ExternalAccountType::class,
            'institution' => 'string',
            'masked_identifier' => 'string',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}

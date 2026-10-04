<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\OpeningWithdrawalFeeFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read string $id
 * @property-read string $contribution_period_id
 * @property-read int $amount
 * @property-read CarbonInterface $paid_on
 * @property-read string|null $import_key
 * @property-read string $reference
 * @property-read string $notes
 */
final class OpeningWithdrawalFee extends Model
{
    /** @use HasFactory<OpeningWithdrawalFeeFactory> */
    use HasFactory;

    use HasUuids;

    /** @return array<string, string> */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'contribution_period_id' => 'string',
            'amount' => 'integer',
            'paid_on' => 'date',
            'reference' => 'string',
            'import_key' => 'string',
            'notes' => 'string',
        ];
    }
}

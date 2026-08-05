<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonInterface;
use Database\Factories\PaymentEvidenceFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read string $id
 * @property-read string $payment_id
 * @property-read string $path
 * @property-read string $original_name
 * @property-read string $mime_type
 * @property-read int $size
 * @property-read string|null $uploaded_by_member_id
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class PaymentEvidence extends Model
{
    /** @use HasFactory<PaymentEvidenceFactory> */
    use HasFactory;

    use HasUuids;

    protected $table = 'payment_evidence';

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'payment_id' => 'string',
            'path' => 'string',
            'original_name' => 'string',
            'mime_type' => 'string',
            'size' => 'integer',
            'uploaded_by_member_id' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Payment, $this>
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}

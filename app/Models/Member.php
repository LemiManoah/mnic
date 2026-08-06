<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ClubPosition;
use App\Enums\MemberStatus;
use Carbon\CarbonInterface;
use Database\Factories\MemberFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read string $id
 * @property-read string|null $user_id
 * @property-read string|null $referred_by_member_id
 * @property-read string $member_number
 * @property-read string $full_name
 * @property-read ClubPosition|null $position
 * @property-read string $phone
 * @property-read string|null $emergency_contact
 * @property-read CarbonInterface $joined_at
 * @property-read MemberStatus $status
 * @property-read CarbonInterface $created_at
 * @property-read CarbonInterface $updated_at
 */
final class Member extends Model
{
    /** @use HasFactory<MemberFactory> */
    use HasFactory;

    use HasUuids;

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return [
            'id' => 'string',
            'user_id' => 'string',
            'referred_by_member_id' => 'string',
            'member_number' => 'string',
            'full_name' => 'string',
            'position' => ClubPosition::class,
            'phone' => 'string',
            'emergency_contact' => 'string',
            'joined_at' => 'date',
            'status' => MemberStatus::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<self, $this>
     */
    public function referredBy(): BelongsTo
    {
        return $this->belongsTo(self::class, 'referred_by_member_id');
    }

    /**
     * @return HasMany<MembershipStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(MembershipStatusHistory::class);
    }

    /**
     * @return HasMany<MemberObligation, $this>
     */
    public function obligations(): HasMany
    {
        return $this->hasMany(MemberObligation::class);
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}

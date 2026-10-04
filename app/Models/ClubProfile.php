<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property array<string, array<string, string>> $content
 * @property string|null $updated_by_member_id
 */
final class ClubProfile extends Model
{
    protected $fillable = ['content', 'updated_by_member_id'];

    /**
     * @return array<string, string>
     */
    public function casts(): array
    {
        return ['content' => 'array'];
    }
}

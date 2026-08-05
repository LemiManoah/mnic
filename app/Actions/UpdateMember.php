<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Member;

final readonly class UpdateMember
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Member $member, array $attributes): void
    {
        $member->update($attributes);
    }
}

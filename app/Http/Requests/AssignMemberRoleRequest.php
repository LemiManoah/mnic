<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ClubRole;
use App\Models\Member;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class AssignMemberRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $member = $this->route('member');
        assert($member instanceof Member);

        return (bool) $this->user()?->can('assignRole', $member);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'role' => ['required', new Enum(ClubRole::class)],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Member;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $member = $this->route('member');
        assert($member instanceof Member);

        return (bool) $this->user()?->can('update', $member);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $member = $this->route('member');
        assert($member instanceof Member);

        return [
            'member_number' => ['required', 'string', 'max:255', Rule::unique(Member::class)->ignore($member->id)],
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'emergency_contact' => ['nullable', 'string', 'max:255'],
            'referred_by_member_id' => ['nullable', 'string', Rule::exists(Member::class, 'id')],
        ];
    }
}

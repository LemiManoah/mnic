<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Member;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', Member::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'member_number' => ['required', 'string', 'max:255', Rule::unique(Member::class)],
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'is_pioneer' => ['sometimes', 'boolean'],
            'joined_at' => ['required', 'date'],
            'referred_by_member_id' => ['nullable', 'string', Rule::exists(Member::class, 'id')],
        ];
    }
}

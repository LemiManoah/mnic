<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\ActionItem;
use App\Models\Meeting;
use App\Models\Member;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class CreateActionItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', ActionItem::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'owner_member_id' => ['nullable', 'string', Rule::exists(Member::class, 'id')],
            'meeting_id' => ['nullable', 'string', Rule::exists(Meeting::class, 'id')],
            'due_on' => ['nullable', 'date'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ClubPosition;
use App\Enums\Permission;
use App\Models\Member;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

final class TransferPositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(Permission::PositionsManage->value);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'member_id' => ['required', 'string', Rule::exists(Member::class, 'id')],
            'position' => ['required', new Enum(ClubPosition::class)],
            'held_from' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}

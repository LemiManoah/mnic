<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ObligationStatus;
use App\Models\MemberObligation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AdjustMemberObligationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $obligation = $this->route('memberObligation');
        assert($obligation instanceof MemberObligation);

        return (bool) $this->user()?->can('adjust', $obligation);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in([ObligationStatus::Waived->value, ObligationStatus::Cancelled->value])],
            'reason' => ['required', 'string', 'max:2000'],
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ExternalAccountType;
use App\Models\ExternalAccount;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class CreateExternalAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', ExternalAccount::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', new Enum(ExternalAccountType::class)],
            'institution' => ['nullable', 'string', 'max:255'],
            // Deliberately a masked value: the club never stores a full
            // account number.
            'masked_identifier' => ['required', 'string', 'max:32'],
        ];
    }
}

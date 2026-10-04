<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateClubProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('update', Setting::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'profile' => ['required', 'array'],
            'profile.*' => ['required', 'array'],
            'profile.*.*' => ['required', 'string', 'max:5000'],
        ];
    }
}

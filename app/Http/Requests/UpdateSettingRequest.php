<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $setting = $this->route('setting');
        assert($setting instanceof Setting);

        return (bool) $this->user()?->can('update', $setting);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $setting = $this->route('setting');
        assert($setting instanceof Setting);

        $valueRules = match ($setting->key) {
            'contribution_amount' => ['required', 'integer', 'min:1'],
            'due_day', 'grace_day' => ['required', 'integer', 'between:1,28'],
            'quorum_percent', 'approval_percent' => ['required', 'integer', 'between:1,100'],
            default => ['required', 'string', 'max:255'],
        };

        return [
            'value' => $valueRules,
            'effective_from' => ['required', 'date'],
        ];
    }
}

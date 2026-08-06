<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Permission as PermissionEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

final class CreateSystemRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', Role::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9-]+$/', Rule::unique('roles', 'name')],
            'permissions' => ['array'],
            'permissions.*' => [Rule::in(array_column(PermissionEnum::cases(), 'value'))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.regex' => 'Use lowercase letters, numbers and hyphens only, for example "deputy-treasurer".',
        ];
    }
}

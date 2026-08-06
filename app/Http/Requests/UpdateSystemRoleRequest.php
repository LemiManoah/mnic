<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\Permission as PermissionEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

final class UpdateSystemRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $role = $this->route('role');
        assert($role instanceof Role);

        return (bool) $this->user()?->can('update', $role);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $role = $this->route('role');
        assert($role instanceof Role);

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9-]+$/',
                Rule::unique('roles', 'name')->ignore($role->getKey()),
            ],
            'permissions' => ['array'],
            'permissions.*' => [Rule::in(array_column(PermissionEnum::cases(), 'value'))],
        ];
    }
}

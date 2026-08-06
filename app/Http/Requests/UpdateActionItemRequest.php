<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ActionItemStatus;
use App\Models\ActionItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class UpdateActionItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $actionItem = $this->route('actionItem');
        assert($actionItem instanceof ActionItem);

        return (bool) $this->user()?->can('update', $actionItem);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', new Enum(ActionItemStatus::class)],
        ];
    }
}

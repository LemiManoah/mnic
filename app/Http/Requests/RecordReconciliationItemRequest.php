<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Member;
use App\Models\Reconciliation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RecordReconciliationItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reconciliation = $this->route('reconciliation');
        assert($reconciliation instanceof Reconciliation);

        return (bool) $this->user()?->can('update', $reconciliation);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:255'],
            // A difference can be either direction, so negative is valid.
            'amount' => ['required', 'integer'],
            'assigned_to_member_id' => ['nullable', 'string', Rule::exists(Member::class, 'id')],
        ];
    }
}

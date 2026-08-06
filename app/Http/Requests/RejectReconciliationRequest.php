<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Reconciliation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class RejectReconciliationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reconciliation = $this->route('reconciliation');
        assert($reconciliation instanceof Reconciliation);

        return (bool) $this->user()?->can('confirm', $reconciliation);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}

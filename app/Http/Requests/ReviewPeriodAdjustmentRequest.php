<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\PeriodAdjustment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class ReviewPeriodAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $adjustment = $this->route('periodAdjustment');
        assert($adjustment instanceof PeriodAdjustment);

        return (bool) $this->user()?->can('review', $adjustment);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Rejecting a correction has to say why; approving one does not,
            // because the request already carries its own reason.
            'reason' => $this->isMethod('DELETE')
                ? ['required', 'string', 'max:1000']
                : ['nullable', 'string', 'max:1000'],
        ];
    }
}

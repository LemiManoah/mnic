<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\ContributionPeriod;
use App\Models\PeriodAdjustment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class RequestPeriodAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', PeriodAdjustment::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'contribution_period_id' => ['required', 'string', Rule::exists(ContributionPeriod::class, 'id')],
            // Signed on purpose — a correction can go either way — but never
            // zero, which would correct nothing.
            'amount' => ['required', 'integer', 'not_in:0'],
            'reason' => ['required', 'string', 'max:1000'],
        ];
    }
}

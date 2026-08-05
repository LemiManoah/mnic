<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\ContributionPeriod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

final class OpenContributionPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', ContributionPeriod::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $exists = ContributionPeriod::query()
                ->where('year', $this->integer('year'))
                ->where('month', $this->integer('month'))
                ->exists();

            if ($exists) {
                $validator->errors()->add('month', __('A contribution period already exists for this month.'));
            }
        });
    }
}

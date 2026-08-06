<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Expense;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class RejectExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $expense = $this->route('expense');
        assert($expense instanceof Expense);

        return (bool) $this->user()?->can('approve', $expense);
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

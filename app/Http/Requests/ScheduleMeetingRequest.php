<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Meeting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ScheduleMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('create', Meeting::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reference' => ['required', 'string', 'max:255', Rule::unique(Meeting::class, 'reference')],
            'title' => ['required', 'string', 'max:255'],
            'scheduled_for' => ['required', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'agenda' => ['nullable', 'string', 'max:5000'],
        ];
    }
}

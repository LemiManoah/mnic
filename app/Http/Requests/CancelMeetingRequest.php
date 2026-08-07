<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Meeting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class CancelMeetingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $meeting = $this->route('meeting');
        assert($meeting instanceof Meeting);

        return (bool) $this->user()?->can('cancel', $meeting);
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

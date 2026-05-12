<?php

namespace App\Http\Requests\Owner;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTenantStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:activate,grace,suspend,remind'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'grace_ends_at' => ['nullable', 'date'],
        ];
    }
}

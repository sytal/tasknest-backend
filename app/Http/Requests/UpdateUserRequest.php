<?php

namespace App\Http\Requests;

use App\Support\Limits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isManagement();
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:'.Limits::NAME_MAX],
            'designation' => ['nullable', 'string', 'max:'.Limits::NAME_MAX],
            'role' => ['sometimes', Rule::in(['director', 'ceo', 'member'])],
        ];
    }
}

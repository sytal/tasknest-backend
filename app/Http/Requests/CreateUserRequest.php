<?php

namespace App\Http\Requests;

use App\Support\Limits;
use Illuminate\Foundation\Http\FormRequest;

class CreateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isManagement();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:'.Limits::PASSWORD_MIN],
            'role' => ['required', 'in:director,ceo,member'],
            'designation' => ['nullable', 'string', 'max:255'],
        ];
    }
}

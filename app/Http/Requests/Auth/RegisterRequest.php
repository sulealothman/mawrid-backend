<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $emailRule = 'required|string|email|max:255';

        $phoneRule = 'required|string|max:255';

        if (config('database.default') !== 'sqlite') {
            $emailRule .= '|unique:users,email';
        }

        if (config('database.default') !== 'sqlite') {
            $phoneRule .= '|unique:users,phone';
        }

        return [
            'name' => 'required|string|max:255',
            'email' => $emailRule,
            'phone' => $phoneRule,
            'password' => 'required|string|min:8|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'The email field is required.',
            'email.email'    => 'The email format is invalid.',
            'email.unique'   => 'The email has already been taken.',
            'phone.required' => 'The phone number field is required.',
            'phone.unique'   => 'The phone number has already been taken.',
            'password.required' => 'The password field is required.',
            'password.min'      => 'The password must be at least 8 characters.',
            'password.max'      => 'The password may not be greater than 255 characters.',
        ];
    }
}

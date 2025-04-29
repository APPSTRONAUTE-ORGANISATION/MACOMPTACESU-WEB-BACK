<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user('sanctum')->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'string',
                Rule::unique('users')->ignore($this->user->id)
            ],
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'country' => 'nullable|string',
            'phone' => 'nullable|string|regex:/^\d{10}$/',
            'job' => 'nullable|string',
            'profile_image' => 'nullable|image',
            'active' => 'required|boolean',
        ];
    }
}

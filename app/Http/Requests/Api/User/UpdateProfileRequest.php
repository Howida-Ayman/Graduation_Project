<?php

namespace App\Http\Requests\Api\User;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
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
        return [
            'full_name' => 'required|string|max:255',
            // 'faculty' => 'required|string',
            'phone' => 'required|string|max:20',
            'track_name' => 'nullable|string|max:255',
            'gpa' => 'nullable|numeric|min:0|max:4',

            'department_id' => 'required|exists:departments,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            // 'city' => 'required|string',
            'email' => 'required|email|max:255|unique:users,email,' . $this->user()->id,
        ];
    }
}

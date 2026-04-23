<?php

namespace App\Http\Requests\Api\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $user = $this->user();
        $roleCode = strtolower($user?->role?->code);

        $rules = [
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'track_name' => 'nullable|string|max:255',
            'profile_image_url' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'department_id' => [
                'required',
                Rule::exists('departments', 'id')->where(function ($query) {
                    $query->where('is_active', 1);
                }),
            ],
        ];

        // الطالب فقط
        if ($roleCode === 'student') {
            $rules['gpa'] = 'nullable|numeric|min:0|max:4';
        } else {
            // للدكتور / المعيد ما نحتاجش GPA
            $rules['gpa'] = 'nullable';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Full name is required',
            'phone.required' => 'Phone is required',
            'email.required' => 'Email is required',
            'email.email' => 'Please enter a valid email address',
            'email.unique' => 'This email is already taken',
            'department_id.required' => 'Department is required',
            'department_id.exists' => 'Selected department is invalid or inactive',
            'gpa.numeric' => 'GPA must be a number',
            'gpa.min' => 'GPA must be at least 0',
            'gpa.max' => 'GPA must not exceed 4',
        ];
    }
}
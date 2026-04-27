<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject' => 'required|string|max:255',
            'description' => 'required|string|min:5',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120', // 5MB
        ];
    }

    public function messages(): array
    {
        return [
            'subject.required' => 'Subject is required',
            'description.required' => 'Please describe your issue',
            'attachment.max' => 'File size cannot exceed 5MB',
            'attachment.mimes' => 'Only JPG, PNG, PDF files are allowed',
        ];
    }
}
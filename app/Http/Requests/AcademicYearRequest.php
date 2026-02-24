<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AcademicYearRequest extends FormRequest
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
        'code'=>'required|string|regex:/^\d{4}-\d{4}$/|unique:academic_years,code'
        ];
    }
    public function messages()
    {
        return[
            'code.regex'=>"The code Must Be Like 2024-2025"
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookingListRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // 参数默认值处理（Request层职责）
        $this->merge([
            'page' => (int) $this->input('page', 1),
            'page_size' => (int) $this->input('page_size', 10),
        ]);
    }

    public function messages(): array
    {
        return [
            'page.integer' => '页码格式错误',
            'page_size.integer' => '每页条数格式错误',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'device_id' => [
                'required',
                'integer',
                'min:1',
                Rule::exists('devices', 'id'),
            ],
            // 借用开始日期，不能早于今天
            'start_time' => ['required', 'date', 'after_or_equal:today'],
            // 结束日期 ≥ 开始日期
            'end_time' => ['required', 'date', 'after_or_equal:start_time'],
            'purpose' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'device_id.required' => '设备ID不能为空',
            'device_id.exists' => '设备不存在',
            'start_time.required' => '借用开始日期不能为空',
            'start_time.after_or_equal' => '借用开始日期不能早于今天',
            'end_time.required' => '借用结束日期不能为空',
            'end_time.after_or_equal' => '借用结束日期不能早于开始日期',
            'purpose.max' => '用途说明不能超过255个字符',
        ];
    }
}

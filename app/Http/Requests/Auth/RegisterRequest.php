<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username' => [
                'required',
                'string',
                'max:50',
                Rule::unique('users', 'username'),
            ],
            'name' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:6', 'max:32'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => '账号不能为空',
            'username.unique' => '该账号已被注册',
            'username.max' => '账号长度不能超过50个字符',
            'name.required' => '姓名不能为空',
            'name.max' => '姓名长度不能超过20个字符',
            'password.required' => '密码不能为空',
            'password.min' => '密码长度不能少于6位',
            'password.max' => '密码长度不能超过32位',
        ];
    }
}

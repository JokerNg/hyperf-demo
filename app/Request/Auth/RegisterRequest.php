<?php

declare(strict_types=1);

namespace App\Request\Auth;

use Hyperf\Validation\Request\FormRequest;

class RegisterRequest extends FormRequest
{
    protected array $scenes = [
        'register' => ['name', 'phone', 'password'],
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:50',
            'phone' => 'required|string|regex:/^1[3-9]\d{9}$/|unique:user,phone',
            'password' => 'required|string|min:6|max:32',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '姓名不能为空',
            'name.max' => '姓名不能超过50个字符',
            'phone.required' => '手机号不能为空',
            'phone.regex' => '手机号格式不正确',
            'phone.unique' => '该手机号已注册',
            'password.required' => '密码不能为空',
            'password.min' => '密码不能少于6位',
            'password.max' => '密码不能超过32位',
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Request\Auth;

use Hyperf\Validation\Request\FormRequest;

class LoginRequest extends FormRequest
{
    protected array $scenes = [
        'login' => ['phone', 'password'],
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => 'required|string|regex:/^1[3-9]\d{9}$/',
            'password' => 'required|string|min:6|max:32',
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => '手机号不能为空',
            'phone.regex' => '手机号格式不正确',
            'password.required' => '密码不能为空',
            'password.min' => '密码不能少于6位',
            'password.max' => '密码不能超过32位',
        ];
    }
}

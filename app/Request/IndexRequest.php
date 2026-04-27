<?php

declare(strict_types=1);

namespace App\Request;

use Hyperf\Validation\Request\FormRequest;

class IndexRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'id' => 'required|integer',
            'name' => 'required|string',
            'email' => 'required|email',
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'id is required',
            'name.required' => 'name is required',
            'email.required' => 'email is required',
            'email.email' => 'email格式错误',
        ];
    }

    protected array $scenes = [
        'default' => [
            'id' => 'required|integer',
        ],
        'test' => [
            'name' => 'required|string',
            'email' => 'required|email',
        ],
    ];
}

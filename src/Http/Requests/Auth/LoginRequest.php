<?php

namespace JangKeyte\Http\Requests\Auth;

use JangKeyte\Http\Requests\BaseFormRequest;

class LoginRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:191'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required' => __('Vui lòng nhập tài khoản hoặc email.'),
            'password.required' => __('Vui lòng nhập mật khẩu.'),
        ];
    }
}

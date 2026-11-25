<?php

namespace JangKeyte\Http\Requests\User;

use Illuminate\Validation\Rule;
use JangKeyte\Http\Requests\BaseFormRequest;

class SaveUserRequest extends BaseFormRequest
{
    public function rules(): array
    {
        $userId = $this->route('id') ?? $this->input('id');
        $isUpdate = ! empty($userId);

        return [
            'id' => ['nullable', 'integer'],
            'name' => ['required', 'string', 'max:225'],
            'username' => [
                $isUpdate ? 'nullable' : 'sometimes',
                'string',
                'max:191',
                Rule::unique('users', 'username')->ignore($userId),
            ],
            'email' => [
                'required',
                'email',
                'max:191',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => [$isUpdate ? 'nullable' : 'required', 'string', 'min:6'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'role' => ['nullable', 'integer', 'exists:roles,id'],
            'permission' => ['nullable', 'array'],
            'permission.*' => ['string', 'exists:permissions,slug'],
        ];
    }
}

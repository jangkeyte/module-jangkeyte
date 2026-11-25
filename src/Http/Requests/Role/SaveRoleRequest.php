<?php

namespace JangKeyte\Http\Requests\Role;

use Illuminate\Validation\Rule;
use JangKeyte\Http\Requests\BaseFormRequest;

class SaveRoleRequest extends BaseFormRequest
{
    public function rules(): array
    {
        $roleId = $this->route('id') ?? $this->input('id');

        return [
            'name' => ['required', 'string', 'max:191'],
            'slug' => [
                'required',
                'string',
                'alpha_dash',
                'max:100',
                Rule::unique('roles', 'slug')->ignore($roleId),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'code' => ['nullable', 'string', 'max:50'],
            'permission' => ['nullable', 'array'],
            'permission.*' => ['string', 'exists:permissions,slug'],
        ];
    }
}

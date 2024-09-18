<?php

namespace Modules\Authetication\src\Http\Requests\Role;

use Modules\Authetication\src\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class CreateRoleRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return array_merge(parent::rules(), [            
            'name' => 'required',
            'slug' => ['required', 'alpha_dash', Rule::unique('roles')->ignore($this->id)],
        ]);
    }

    /**
    * Custom message for validation
    *
    * @return array
    */
   public function messages()
   {
        return array_merge(parent::messages(), [
            'name' => 'Tên vai trò không được để trống.',
            'slug.required' => 'Slug không được để trống.',      
            'slug.alpha_dash' => 'Slug không được chứa ký tự đặc biệt.',     
            'slug.unique' => 'Slug đã tồn tại, vui lòng chọn slug khác.',           
        ]);
   }
   
   /**
    *  Filters to be applied to the input.
    *
    * @return array
    */
   public function filters()
   {
       return array_merge(parent::messages(), [
            'slug' => 'trim|capitalize|escape'
       ]);
   }
}

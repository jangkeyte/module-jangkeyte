<?php

namespace Modules\JangKeyte\src\Http\Requests;

use Modules\JangKeyte\src\Http\Requests\BaseFormRequest;

class CommonFormRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'title' => 'required|string|min:3|max:100',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,gif',
        ]);
    }

   public function messages()
   {
        return array_merge(parent::messages(), [
            'title.required' => 'Tiêu đề không được để trống.',
            'title.string' => 'Tiêu đề phải là chữ.',
            'title.min' => 'Tiêu đề phải từ 3 ký tự trở lên.',
            'title.max' => 'Tiêu đề quá dài, không được vượt quá 100 ký tự.',
            'image.image' => 'Tệp tin không phải là hình ảnh, vui lòng chọn tệp tin phù hợp.',
            'image.mimes' => 'Hình ảnh không hợp lệ, phải là định dạng jpg, jpeg, png hoặc gif.',
        ]);
   }
   
   public function filters()
   {
       return array_merge(parent::messages(), [
            'title' => 'trim|capitalize|escape'
       ]);
   }
}

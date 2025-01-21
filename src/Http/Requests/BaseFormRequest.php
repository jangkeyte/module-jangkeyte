<?php

namespace Modules\JangKeyte\src\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;

abstract class BaseFormRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [

        ]; 
    }

    /**
    * Custom message for validation
    *
    * @return array
    */
    public function messages()
    {
         return [
            
         ];
    }
    
   public function filters()
   {
       return [
        
       ];
   }
   
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    abstract public function authorize();

}
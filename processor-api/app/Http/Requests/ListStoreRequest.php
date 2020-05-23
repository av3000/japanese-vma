<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => 'required|min:2|max:255',
            'type'  => 'required',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'An List Title is Required',
            'title.min' => 'An List Title is too short',
            'title.max' => 'An List Title is too long',
            'type.required' => 'An List type is required',
            'status.required' => 'An List status is required'
        ];
    }

    // /**
    //  *  Filters to be applied to the input.
    //  *
    //  * @return array
    //  */
    // public function filters()
    // {
    //     return [
    //         'email' => 'trim|lowercase',
    //         'name' => 'trim|capitalize|escape'
    //     ];
    // }
}

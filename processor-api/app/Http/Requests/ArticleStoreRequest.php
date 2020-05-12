<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ArticleStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // What authorized means? is simple user is already authorized?
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title_en'    => 'max:255',
            'title_jp'    => 'required|min:2|max:255',
            'content_en'  => 'max:3000',
            'content_jp'  => 'required|min:2|max:3000',
            'status'      => 'required',
            'source_link' => 'required'
        ];
    }

    public function messages()
    {
        return [
            'title_jp.required' => 'An Article Japanese Title is Required',
            'content_jp.required'  => 'An Article Japanese Text Body is Required',
            'source_link.required' => 'The source link needs be provided or approved its ownership.',
            'status.required' => 'An Article needs to have a Status'
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

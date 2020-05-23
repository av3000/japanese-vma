<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostStoreRequest extends FormRequest
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
            'title'    => 'required|string|min:2|max:255',
            'type'     => 'required',
            'content'  => 'required|string|min:5|max:15000'
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'An Post Title is Required',
            'title.min' => 'An Post Title is too short',
            'title.max' => 'An Post Title is too long',
            'type.required' => 'An Post type is required',
            'content.required' => 'An Post content is required',
            'content.min' => 'An Post content is too short',
            'content.max' => 'An Post content is too long'
        ];
    }
}

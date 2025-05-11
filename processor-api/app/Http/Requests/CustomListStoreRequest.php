<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomListStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return auth()->check();
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
            'description' => 'nullable|max:500',
            'type' => 'required|integer|between:5,9', // Based on seeder, types 5-9 are for custom lists
            'publicity' => 'nullable|boolean',
            'tags' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'title.required' => 'A list title is required',
            'title.min' => 'The list title must be at least 2 characters',
            'title.max' => 'The list title must not exceed 255 characters',
            'type.required' => 'A list type is required',
            'type.between' => 'List type must be between 5 and 9 for custom lists',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    // protected function prepareForValidation()
    // {
    //     // Trim whitespace from inputs
    //     $this->merge([
    //         'title' => trim($this->title),
    //         'description' => $this->description ? trim($this->description) : null,
    //         'publicity' => $this->publicity ?? 0,
    //     ]);
    // }
}

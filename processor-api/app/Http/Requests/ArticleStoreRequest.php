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
            'title_jp' => 'required|min:2|max:255',
            'title_en' => 'nullable|max:255',
            'content_jp' => 'required|min:2|max:3000',
            'content_en' => 'nullable|max:3000',
            'source_link' => 'required|url',
            'publicity' => 'nullable|boolean',
            'tags' => 'nullable|string',
            'attach' => 'nullable|boolean'
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
            'title_jp.required' => 'A Japanese title is required',
            'title_jp.min' => 'The Japanese title must be at least 2 characters',
            'content_jp.required' => 'Japanese content is required',
            'content_jp.min' => 'The Japanese content must be at least 2 characters',
            'source_link.required' => 'The source link is required',
            'source_link.url' => 'The source link must be a valid URL',
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
    //         'title_jp' => trim($this->title_jp),
    //         'title_en' => $this->title_en ? trim($this->title_en) : null,
    //         'content_jp' => trim($this->content_jp),
    //         'content_en' => $this->content_en ? trim($this->content_en) : null,
    //         'publicity' => $this->publicity ?? 0,
    //         'attach' => $this->attach ?? 0,
    //     ]);
    // }
}

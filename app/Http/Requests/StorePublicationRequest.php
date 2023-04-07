<?php

namespace App\Http\Requests;

use App\Models\Publication;
use Gate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Response;

class StorePublicationRequest extends FormRequest
{
    public function authorize()
    {
        return Gate::allows('publication_create');
    }

    public function rules()
    {
        return [
            'title' => [
                'string',
                'required',
                function ($attribute, $value, $fail) {
                    if (strpos($value, '_') !== false) {
                        $fail('O campo de título não pode conter underline(_)');
                    }
                },
            ],
            'text' => [
                'required',
            ],
            'photos' => [
                'array',
            ],
            'categories.*' => [
                'integer',
            ],
            'categories' => [
                'array',
            ],
        ];
    }
}

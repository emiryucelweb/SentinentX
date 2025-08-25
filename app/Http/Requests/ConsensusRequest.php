<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConsensusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'symbol' => [
                'required',
                'string',
                'regex:/^[A-Z]{2,10}USDT$/',
                'max:20',
            ],
            'equity' => [
                'required',
                'numeric',
                'min:100',
                'max:100000000',
            ],
            'context' => [
                'required',
                'array',
            ],
            'context.price' => [
                'required',
                'numeric',
                'min:0.01',
            ],
            'context.volume' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'context.indicators' => [
                'nullable',
                'array',
            ],
            'providers' => [
                'nullable',
                'array',
                'max:5',
            ],
            'providers.*' => [
                'string',
                'in:openai,gemini,grok,claude',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'symbol.regex' => 'Symbol must be in format like BTCUSDT, ETHUSDT',
            'equity.min' => 'Minimum equity is $100',
            'equity.max' => 'Maximum equity is $100,000,000',
            'context.price.required' => 'Current price is required in context',
            'providers.*.in' => 'Invalid AI provider specified',
        ];
    }
}

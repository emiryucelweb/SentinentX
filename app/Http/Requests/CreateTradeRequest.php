<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateTradeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
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
            'side' => [
                'required',
                Rule::in(['LONG', 'SHORT']),
            ],
            'qty' => [
                'required',
                'numeric',
                'min:0.001',
                'max:1000000',
                'regex:/^\d+(\.\d{1,8})?$/',
            ],
            'entry_price' => [
                'required',
                'numeric',
                'min:0.01',
                'max:10000000',
            ],
            'leverage' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],
            'stop_loss' => [
                'nullable',
                'numeric',
                'min:0.01',
                'max:10000000',
            ],
            'take_profit' => [
                'nullable',
                'numeric',
                'min:0.01',
                'max:10000000',
            ],
            'margin_mode' => [
                'nullable',
                Rule::in(['CROSS', 'ISOLATED']),
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
            'qty.regex' => 'Quantity must have maximum 8 decimal places',
            'side.in' => 'Side must be either LONG or SHORT',
            'leverage.max' => 'Maximum leverage is 100x',
            'margin_mode.in' => 'Margin mode must be CROSS or ISOLATED',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Custom validation: stop loss should be logical for trade direction
            if ($this->has(['side', 'entry_price', 'stop_loss'])) {
                $side = $this->input('side');
                $entryPrice = $this->input('entry_price');
                $stopLoss = $this->input('stop_loss');

                if ($side === 'LONG' && $stopLoss >= $entryPrice) {
                    $validator->errors()->add('stop_loss', 'Stop loss for LONG position must be below entry price');
                }

                if ($side === 'SHORT' && $stopLoss <= $entryPrice) {
                    $validator->errors()->add('stop_loss', 'Stop loss for SHORT position must be above entry price');
                }
            }

            // Custom validation: take profit should be logical for trade direction
            if ($this->has(['side', 'entry_price', 'take_profit'])) {
                $side = $this->input('side');
                $entryPrice = $this->input('entry_price');
                $takeProfit = $this->input('take_profit');

                if ($side === 'LONG' && $takeProfit <= $entryPrice) {
                    $validator->errors()->add('take_profit', 'Take profit for LONG position must be above entry price');
                }

                if ($side === 'SHORT' && $takeProfit >= $entryPrice) {
                    $validator->errors()->add('take_profit', 'Take profit for SHORT position must be below entry price');
                }
            }
        });
    }
}

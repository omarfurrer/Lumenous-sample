<?php

namespace lumenous\Http\Requests\TransactionBatches;

use Illuminate\Foundation\Http\FormRequest;

class ShowTransactionBatchRequest extends FormRequest {

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return $this->user()->can('sign transactions');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
                //
        ];
    }

}

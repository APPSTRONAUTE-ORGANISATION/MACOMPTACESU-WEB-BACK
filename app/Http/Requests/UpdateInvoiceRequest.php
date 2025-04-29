<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth('sanctum')->id() == $this->invoice->user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'client_id' => 'required|integer|exists:clients,id',
            'activity_id' => 'required|integer|exists:activities,id',
            'total' => 'required|numeric',
            'due_date' => 'nullable|date_format:Y-m-d',
            'invoice_date' => 'nullable|date_format:Y-m-d',
            'invoice_file' => 'nullable|file',
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Models\WeeklyBudget;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateWeeklyBudgetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth('sanctum')->id() == $this->weekly_budget->user_id;
    }

    /**
     * Get the validation rules that apply to the request.
     * 
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'year' => 'required|numeric|min:1',
            'month' => 'required|numeric|min:1|max:12',
            'week' => 'required|numeric|min:1|max:52',
            'amount' => 'required|numeric|min:1',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $weekly_budget = WeeklyBudget::where('year', $this->get('year'))
                    ->where('month', $this->get('month'))
                    ->where('week', $this->get('week'))
                    ->where('amount', $this->get('amount'))
                    ->where('user_id', auth('sanctum')->id())
                    ->where('id', '!=', $this->weekly_budget->id)
                    ->first();
                if ($weekly_budget) {
                    $validator->errors()->add(
                        'weekly_budget',
                        'year, month, week combination already exists'
                    );
                }
            }
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Models\WeeklyBudget;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreWeeklyBudgetRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'data' => 'required|array',
            'data.*.year' => 'required|numeric|min:1',
            'data.*.month' => 'required|numeric|min:1',
            'data.*.week' => 'required|numeric|min:1',
            'data.*.amount' => 'required|numeric|min:1',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $data = $this->get('data', []);

                for ($i = 0; $i < count($data); $i++) {
                    $weekly_budget = WeeklyBudget::where('year', $data[$i]['year'])
                        ->where('month', $data[$i]['month'])
                        ->where('week', $data[$i]['week'])
                        ->where('user_id', auth('sanctum')->id())
                        ->first();
                    if ($weekly_budget) {
                        $validator->errors()->add(
                            'weekly_budget',
                            'year, month, week combination already exists'
                        );
                    }
                }
            }
        ];
    }
}

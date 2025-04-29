<?php

namespace App\Http\Controllers;

use App\Models\WeeklyBudget;
use App\Http\Requests\StoreWeeklyBudgetRequest;
use App\Http\Requests\UpdateWeeklyBudgetRequest;
use Illuminate\Http\Request;

class WeeklyBudgetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $year = $request->year ?? date('Y');

        $query = WeeklyBudget::query();

        $query->where('user_id', auth('sanctum')->id());

        $query->where('year', $year);

        $data = $query->get();

        $dd = $data->groupBy('month');

        $result = [];
        foreach ($dd as $key => $value) {
            $result[] = [
                'month' => $key,
                'data' => $value->toArray(),
            ];
        }

        return response()->json($result);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreWeeklyBudgetRequest $request)
    {
        $data = $request->validated('data', []);
        foreach ($data as $key => $value) {
            $weekly_budget = WeeklyBudget::create([
                'user_id' => auth('sanctum')->id(),
                'year' => $value['year'],
                'month' => $value['month'],
                'week' => $value['week'],
                'amount' => $value['amount'],
            ]);
        }

        return response()->noContent();
    }

    /**
     * Display the specified resource.
     */
    public function show(WeeklyBudget $weekly_budget)
    {
        return response()->json($weekly_budget);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateWeeklyBudgetRequest $request, WeeklyBudget $weekly_budget)
    {
        $weekly_budget->update($request->validated());
        return response()->json($weekly_budget);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WeeklyBudget $weekly_budget)
    {
        $weekly_budget->delete();
        return response()->noContent();
    }
}

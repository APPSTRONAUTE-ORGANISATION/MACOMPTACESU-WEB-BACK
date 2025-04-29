<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Http\Requests\StoreBudgetRequest;
use App\Http\Requests\UpdateBudgetRequest;
use App\Http\Resources\BudgetResource;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BudgetController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $paginate = $request->paginate ?? 10;
        $budget = $request->budget;
        $year = $request->year;

        $query = Budget::query();

        $query->where('user_id', auth('sanctum')->id());

        $query->when($year, function ($query) use ($year) {
            $query->where('year', $year);
        });
        $query->when($budget, function ($query) use ($budget) {
            $query->where('id', $budget);
        });
        $data = $query->paginate($paginate);

        return response()->json([
            'data' => BudgetResource::collection($data),
            'total' => $data->total(),
            'per_page' => $data->perPage(),
            'current_page' => $data->currentPage(),
            'last_page' => $data->lastPage(),
            'from' => $data->firstItem(),
            'to' => $data->lastItem(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBudgetRequest $request)
    {
        return response()->json(new BudgetResource(Budget::create((array_merge($request->validated(), [
            'user_id' => auth('sanctum')->id()
        ])))));
    }

    /**
     * Display the specified resource.
     */
    public function show(Budget $budget)
    {
        $query = DB::table('invoices');
        $query->where('user_id', auth('sanctum')->id());
        $query->whereYear('invoices.invoice_date', $budget->year);
        $query->select([
            DB::raw('DATE_FORMAT(invoices.invoice_date, "%u") as week'),
            DB::raw('SUM(invoices.total) as total'),
        ]);
        $query->groupBy('week');
        $query->orderBy('week', 'asc');

        $data = $query->get();

        // $remaining_amount = $budget->amount - $data->sum('total');
        $year_weeks = (new DateTime('December 28th ' . $budget->year))->format('W');

        $return = [];

        for ($month = 1; $month <= 12; $month++) {
            $date = Carbon::create($budget->year, $month, 1);

            $temp = [];

            while ($date->month == $month) {
                $week = $date->format('W');
                $week_invoice = $data->where('week', $week)->first();
                if ($week_invoice == null) {
                    $temp[$week] = [
                        'invoices' => 0,
                        'budget' => $budget->amount / $year_weeks,
                    ];
                } else {
                    $temp[$week] = [
                        'invoices' => $week_invoice->total,
                        'budget' => $budget->amount / $year_weeks,
                    ];
                }
                $date = $date->addWeeks(1);
            }
            $return[$month] = $temp;
        }

        return response()->json([
            'budget' => new BudgetResource($budget),
            'year_weeks' => $return,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBudgetRequest $request, Budget $budget)
    {
        $budget->update($request->validated());

        return response()->json(new BudgetResource($budget));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Budget $budget)
    {
        $budget->delete();
        return response()->noContent();
    }
}

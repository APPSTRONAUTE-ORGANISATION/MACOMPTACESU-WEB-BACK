<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Http\Requests\StoreExpenseRequest;
use App\Http\Requests\UpdateExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Traits\SaveFileTrait;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    use SaveFileTrait;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $paginate = $request->paginate ?? 10;
        $search = $request->search;

        $query = Expense::query();

        $query->with([
            'ExpenseLabel.ExpenseCategory',
        ]);

        if (auth('sanctum')->user()->hasRole('client')) {
            $query->where('user_id', auth('sanctum')->id());
        }

        $query->when($search != '', function ($query) use ($search) {
            $query->whereHas('ExpenseLabel', function ($query) use ($search) {
                $query->where('name', 'LIKE', "%$search%");
            });
            $query->orWhereHas('ExpenseLabel.ExpenseCategory', function ($query) use ($search) {
                $query->where('name', 'LIKE', "%$search%");
            });
        });

        $data = $query->paginate($paginate);

        return response()->json([
            'data' => ExpenseResource::collection($data),
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
    public function store(StoreExpenseRequest $request)
    {
        $expense = Expense::create(array_merge($request->validated(), [
            'user_id' => auth('sanctum')->id(),
            'expense_file' => $this->save_file($request->validated('expense_file'), 'expenses'),
        ]));

        $expense->load([
            'ExpenseLabel.ExpenseCategory',
        ]);

        return response()->json(new ExpenseResource($expense));
    }

    /**
     * Display the specified resource.
     */
    public function show(Expense $expense)
    {
        $expense->load([
            'ExpenseLabel.ExpenseCategory',
        ]);

        return response()->json(new ExpenseResource($expense));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateExpenseRequest $request, Expense $expense)
    {
        if ($request->validated('expense_file')) {
            $expense->update(array_merge($request->validated(), [
                'expense_file' => $this->save_file($request->file('expense_file'), 'expenses'),
            ]));
        } else {
            $expense->update($request->validated());
        }
        $expense->load([
            'ExpenseLabel.ExpenseCategory',
        ]);

        return response()->json(new ExpenseResource($expense));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expense $expense)
    {
        $expense->delete();
        return response()->noContent();
    }
}

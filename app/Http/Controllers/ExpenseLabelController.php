<?php

namespace App\Http\Controllers;

use App\Models\ExpenseLabel;
use App\Http\Requests\StoreExpenseLabelRequest;
use App\Http\Requests\UpdateExpenseLabelRequest;
use App\Http\Resources\ExpenseLabelResource;
use Illuminate\Http\Request;

class ExpenseLabelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $paginate = $request->paginate ?? 10;
        $search = $request->search;

        $query = ExpenseLabel::query();

        $query->with([
            'ExpenseCategory'
        ]);

        $query->when($search != '', function ($query) use ($search) {
            $query->where('name', 'LIKE', "%$search%");
        });

        $data = $query->paginate($paginate);

        return response()->json([
            'data' => ExpenseLabelResource::collection($data),
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
    public function store(StoreExpenseLabelRequest $request)
    {
        return response()->json(new ExpenseLabelResource(ExpenseLabel::create($request->validated())));
    }

    /**
     * Display the specified resource.
     */
    public function show(ExpenseLabel $expense_label)
    {
        $expense_label->load([
            'ExpenseCategory'
        ]);
        return response()->json(new ExpenseLabelResource($expense_label));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateExpenseLabelRequest $request, ExpenseLabel $expense_label)
    {
        $expense_label->update($request->validated());
        $expense_label->load([
            'ExpenseCategory'
        ]);
        return response()->json(new ExpenseLabelResource($expense_label));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ExpenseLabel $expense_label)
    {
        $expense_label->delete();
        return response()->noContent();
    }
}

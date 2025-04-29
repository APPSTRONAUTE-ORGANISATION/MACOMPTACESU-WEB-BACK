<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseCategoryRequest;
use App\Http\Requests\UpdateExpenseCategoryRequest;
use App\Models\ExpenseCategory;
use App\Http\Resources\ExpenseCategoryResource;
use Illuminate\Http\Request;

class ExpenseCategorieController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $paginate = $request->paginate ?? 10;
        $search = $request->search;

        $query = ExpenseCategory::query();

        $query->when($search != '', function ($query) use ($search) {
            $query->where('name', 'LIKE', "%$search%");
        });

        $data = $query->paginate($paginate);

        return response()->json([
            'data' => ExpenseCategoryResource::collection($data),
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
    public function store(StoreExpenseCategoryRequest $request)
    {
        return response()->json(new ExpenseCategoryResource(ExpenseCategory::create($request->validated())));
    }

    /**
     * Display the specified resource.
     */
    public function show(ExpenseCategory $expense_category)
    {
        return response()->json(new ExpenseCategoryResource($expense_category));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateExpenseCategoryRequest $request, ExpenseCategory $expense_category)
    {
        $expense_category->update($request->validated());

        return response()->json(new ExpenseCategoryResource($expense_category));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ExpenseCategory $expense_category)
    {
        $expense_category->delete();
        return response()->noContent();
    }
}

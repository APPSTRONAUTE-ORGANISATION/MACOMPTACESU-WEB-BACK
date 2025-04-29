<?php

namespace App\Http\Controllers;

use App\Models\VacationWeek;
use App\Http\Requests\StoreVacationWeekRequest;
use App\Http\Requests\UpdateVacationWeekRequest;
use App\Http\Resources\VacationWeekResource;
use Illuminate\Http\Request;

class VacationWeekController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $paginate = $request->paginate ?? 100;
        $year = $request->year;

        $query = VacationWeek::query();

        $query->where('user_id', auth('sanctum')->id());

        $data = $query->paginate($paginate);

        $query->when($year, function ($query) use ($year) {
            $query->where('year', $year);
        });

        return response()->json([
            'data' => VacationWeekResource::collection($data),
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
    public function store(StoreVacationWeekRequest $request)
    {
        $userId = auth('sanctum')->id();
        $validatedData = $request->validated();

        $existingVacation = VacationWeek::where('user_id', $userId)
            ->where('year', $validatedData['year'])
            ->where('week', $validatedData['week'])
            ->first();

        if ($existingVacation) {
            return response()->json([
                'message' => 'Vous avez déjà enregistré cette semaine pour cette année.'
            ], 409);
        }

        $vacationWeek = VacationWeek::create(
            array_merge(
                $validatedData,
                 [
                    'user_id' => $userId
                 ]
                )
            );

        return response()->json(new VacationWeekResource($vacationWeek), 201);

        // return response()->json(new VacationWeekResource(VacationWeek::create(
        //     array_merge(
        //         $request->validated(),
        //         [
        //             'user_id' => auth('sanctum')->id()
        //         ]
        //     )
        // )));
    }

    /**
     * Display the specified resource.
     */
    public function show(VacationWeek $vacation_week)
    {
        return response()->json(new VacationWeekResource($vacation_week));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVacationWeekRequest $request, VacationWeek $vacation_week)
    {
        $vacation_week->update($request->validated());
        return response()->json(new VacationWeekResource($vacation_week));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VacationWeek $vacation_week)
    {
        $vacation_week->delete();
        return response()->noContent();
    }
}

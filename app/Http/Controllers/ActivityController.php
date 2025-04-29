<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\UpdateActivityRequest;
use App\Http\Resources\ActivityResource;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $paginate = $request->paginate ?? 100;

        // $query = Activity::query()->orderBy('name', 'asc');
        $query = Activity::where('user_id', auth('sanctum')->id())
        ->orderBy('name', 'asc');

        // $query = Activity::query();

        $data = $query->paginate($paginate);

        return response()->json([
            'data' => ActivityResource::collection($data),
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
    public function store(StoreActivityRequest $request)
    {
        $data = $request->validated();
        $data['user_id'] = auth('sanctum')->id(); 
        return response()->json(new ActivityResource(Activity::create($data)));
        // return response()->json(new ActivityResource(Activity::create($request->validated())));
    }

    /**
     * Display the specified resource.
     */
    public function show(Activity $activity)
    {
        return response()->json(new ActivityResource($activity));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateActivityRequest $request, Activity $activity)
    {
        $activity->update($request->validated());
        return response()->json(new ActivityResource($activity));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Activity $activity)
    {
        $activity->delete();
        return response()->noContent();
    }
}

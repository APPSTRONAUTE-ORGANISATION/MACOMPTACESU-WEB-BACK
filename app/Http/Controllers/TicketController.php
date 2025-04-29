<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Http\Resources\TicketResource;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $paginate = $request->paginate ?? 10;
        $search = $request->search;

        $query = Ticket::query();

        $query->with([
            'User'
        ]);

        if (auth('sanctum')->user()->hasRole('client')) {
            $query->where('user_id', auth('sanctum')->id());
        }

        if ($search) {
            $query->where('subject', 'like', "%$search%");
            $query->orWhere('message', 'like', "%$search%");
        }

        $data = $query->paginate($paginate);

        return response()->json([
            'data' => TicketResource::collection($data),
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
    public function store(StoreTicketRequest $request)
    {
        $ticket = Ticket::create(array_merge($request->validated(), [
            'user_id' => auth('sanctum')->id()
        ]));

        return response()->json(new TicketResource($ticket));
    }

    /**
     * Display the specified resource.
     */
    public function show(Ticket $ticket)
    {
        $ticket->load([
            'User'
        ]);
        return response()->json(new TicketResource($ticket));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTicketRequest $request, Ticket $ticket)
    {
        $ticket->update($request->validated());
        return response()->json(new TicketResource($ticket));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ticket $ticket)
    {
        if (auth('sanctum')->user()->hasRole('client') && $ticket->user_id != auth('sanctum')->id()) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $ticket->delete();
        return response()->noContent();
    }
}

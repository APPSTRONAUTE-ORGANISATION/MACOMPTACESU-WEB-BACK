<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Http\Requests\StoreClientRequest;
use App\Http\Requests\UpdateClientRequest;
use App\Http\Resources\ClientResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $paginate = $request->paginate ?? 10;
        $search = $request->search;

        $query = Client::query();

        $query->with([
            'User',
        ]);

        $query->where('user_id', auth('sanctum')->user()->id);

        $query->when($search != '', function ($query) use ($search) {
            $query->where('first_name', 'LIKE', "%$search%");
            $query->orWhere('last_name', 'LIKE', "%$search%");
            $query->orWhere('email', 'LIKE', "%$search%");
            $query->orWhere('address', 'LIKE', "%$search%");
            $query->orWhere('phone', 'LIKE', "%$search%");
        });

        $query->orderBy('first_name', 'asc');

        $data = $query->paginate($paginate);

        return response()->json([
            'data' => ClientResource::collection($data),
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
    public function store(StoreClientRequest $request)
    {
        $client = Client::create(array_merge($request->validated(), [
            'user_id' => auth('sanctum')->user()->id
        ]));

        return response()->json(new ClientResource($client));
    }

    /**
     * Display the specified resource.
     */
    public function show(Client $client)
    {
        if ($client->user_id != auth('sanctum')->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }
        $client->load([
            'User',
            'Invoices.Activity',
        ]);
        return response()->json(new ClientResource($client));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateClientRequest $request, Client $client)
    {
        $client->update($request->validated());
        return response()->json(new ClientResource($client));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client)
    {
        if ($client->user_id != auth('sanctum')->id()) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $client->delete();
        return response()->noContent();
    }

    public function TotalClients(Request $request)
    {
        $from = $request->from;
        $to = $request->to;
        $client_id = $request->client;

        $query = DB::table('clients');

        $query->leftJoin('invoices', 'invoices.client_id', '=', 'clients.id');

        $query->where('clients.user_id', auth('sanctum')->id());

        if ($from && $to) {
            $query->whereDate('invoices.created_at', '>=', $from);
            $query->whereDate('invoices.created_at', '<=', $to);
        }

        if ($client_id) {
            $query->where('clients.id', $client_id);
        }

        $query->select([
            'clients.id',
            'clients.first_name',
            'clients.last_name',
            DB::raw('SUM(case when month(invoices.created_at) = 1 then invoices.total else 0 end) as jan'),
            DB::raw('SUM(case when month(invoices.created_at) = 2 then invoices.total else 0 end) as feb'),
            DB::raw('SUM(case when month(invoices.created_at) = 3 then invoices.total else 0 end) as mar'),
            DB::raw('SUM(case when month(invoices.created_at) = 4 then invoices.total else 0 end) as apr'),
            DB::raw('SUM(case when month(invoices.created_at) = 5 then invoices.total else 0 end) as may'),
            DB::raw('SUM(case when month(invoices.created_at) = 6 then invoices.total else 0 end) as jun'),
            DB::raw('SUM(case when month(invoices.created_at) = 7 then invoices.total else 0 end) as jul'),
            DB::raw('SUM(case when month(invoices.created_at) = 8 then invoices.total else 0 end) as aug'),
            DB::raw('SUM(case when month(invoices.created_at) = 9 then invoices.total else 0 end) as sep'),
            DB::raw('SUM(case when month(invoices.created_at) = 10 then invoices.total else 0 end) as oct'),
            DB::raw('SUM(case when month(invoices.created_at) = 11 then invoices.total else 0 end) as nov'),
            DB::raw('SUM(case when month(invoices.created_at) = 12 then invoices.total else 0 end) as "dec"'),
            DB::raw('SUM(invoices.total) as total'),
        ]);

        $query->orderBy('clients.first_name', 'asc');

        $query->groupBy([
            'clients.id',
            'clients.first_name',
            'clients.last_name',
        ]);

        $data = $query->get();

        return $data;
    }
}

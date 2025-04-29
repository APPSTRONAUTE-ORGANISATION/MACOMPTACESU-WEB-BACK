<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceDayRequest;
use App\Models\Invoice;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceDayRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Http\Resources\InvoiceDayResource;
use App\Http\Resources\InvoiceResource;
use App\Models\InvoiceDay;
use App\Traits\SaveFileTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class InvoiceController extends Controller
{
    use SaveFileTrait;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $paginate = $request->paginate ?? 10;
        $client_id = $request->client_id;
        $activity_id = $request->activity_id;
        $search = $request->search;
        $from = $request->from;
        $to = $request->to;
        $status = $request->status;
        $year = $request->year ?? date('Y');

        $query = Invoice::query();

        $query->where('user_id', auth('sanctum')->id());

        $query->when($client_id, function ($query) use ($client_id) {
            $query->where('client_id', $client_id);
        });

        $query->when($activity_id, function ($query) use ($activity_id) {
            $query->where('activity_id', $activity_id);
        });
        
        $query->with([
            'User',
            'Client',
            'Activity',
        ]);

        if ($search) {
            $query->whereHas('Activity', function ($query) use ($search) {
                $query->where('name', 'LIKE', "%$search%")
                      ->orWhere('total', 'LIKE', "%$search%");
            });
        }

        if ($from && $to) {
            $query->whereBetween('created_at', [$from, $to]);
        }

        if ($year) {
            $query->whereYear('invoice_date', $year);
        }

        if ($status === 'Payé') {
            $query->whereRaw('(SELECT COALESCE(SUM(payments.amount), 0) FROM payments WHERE payments.invoice_id = invoices.id) = invoices.total');
        } elseif ($status === 'En retard') {
            $query->whereRaw('(SELECT COALESCE(SUM(payments.amount), 0) FROM payments WHERE payments.invoice_id = invoices.id) <> invoices.total');
        }

        $query->withSum('Payments', 'amount');
        $query->withSum('InvoiceDays', 'hours');
        $query->withCount('InvoiceDays');

        $data = $query->paginate($paginate);

        return response()->json([
            'data' => InvoiceResource::collection($data),
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
    public function store(StoreInvoiceRequest $request)
    {
        $invoice = Invoice::create(array_merge($request->validated(), [
            'user_id' => auth('sanctum')->id(),
            'invoice_file' => $this->save_file($request->file('invoice_file'), 'invoices'),
        ]));

        foreach ($request->validated('invoice_days', []) as $key => $value) {
            $invoice->InvoiceDays()->create($value);
        }

        $invoice->load([
            'User',
            'Client',
            'Activity',
            'InvoiceDays',
        ]);

        return response()->json(new InvoiceResource($invoice));
    }

    public function StoreInvoiceDay(StoreInvoiceDayRequest $request)
    {
        if (auth('sanctum')->id() != Invoice::find($request->validated('invoice_id'))->user_id) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $invoice_day = InvoiceDay::create($request->validated());

        return response()->json(new InvoiceDayResource($invoice_day));
    }

    /**
     * Display the specified resource.
     */
    public function show(Invoice $invoice)
    {
        $invoice->load([
            'User',
            'Client',
            'Activity',
            'Payments',
            'InvoiceDays',
        ]);

        $invoice->loadSum('Payments', 'amount');

        return response()->json(new InvoiceResource($invoice));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice)
    {
        if ($request->validated('invoice_file')) {
            $invoice->update(array_merge($request->validated(), [
                'invoice_file' => $this->save_file($request->file('invoice_file'), 'invoices'),
            ]));
        } else {
            $invoice->update($request->validated());
        }

        $invoice->load([
            'User',
            'Client',
            'Activity',
            'Payments',
        ]);

        return response()->json(new InvoiceResource($invoice));
    }

    public function UpdateInvoiceDay(UpdateInvoiceDayRequest $request, InvoiceDay $invoice_day)
    {
        $invoice_day->update($request->validated());

        return response()->json(new InvoiceDayResource($invoice_day));
    }

    public function DestroyInvoiceDay(InvoiceDay $invoice_day)
    {
        $invoice_day->delete();
        return response()->noContent();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invoice $invoice)
    {
        $invoice->delete();
        return response()->noContent();
    }

    public function Calendar(Request $request)
    {
        $from = $request->from;
        $to = $request->to;
        $client_id = $request->client;
        $activity_id = $request->activity;

        $invoice_sub_query = DB::table('invoices');
        $invoice_sub_query->leftJoin('invoice_days', 'invoice_days.invoice_id', '=', 'invoices.id');
        $invoice_sub_query->select([
            'invoices.id as invoice_id',
            DB::raw('SUM(invoice_days.hours) as hours'),
            DB::raw('SUM(invoice_days.trailers) as trailers'),
            DB::raw('COUNT(invoice_days.id) as work_days'),
        ]);
        $invoice_sub_query->groupBy([
            'invoices.id',
        ]);

        $invoice_payment_sub_query = DB::table('invoices');
        $invoice_payment_sub_query->leftJoin('payments', 'payments.invoice_id', '=', 'invoices.id');
        $invoice_payment_sub_query->select([
            'invoices.id as invoice_id',
            DB::raw('SUM(IFNULL(payments.amount,0)) as payments'),
        ]);
        $invoice_payment_sub_query->groupBy([
            'invoices.id',
        ]);

        $query = DB::table('invoices');

        $query->join('clients', 'clients.id', '=', 'invoices.client_id');
        $query->join('activities', 'activities.id', '=', 'invoices.activity_id');
        $query->leftJoinSub($invoice_sub_query, 'invoice_days', function ($join) {
            $join->on('invoice_days.invoice_id', '=', 'invoices.id');
        });
        $query->leftJoinSub($invoice_payment_sub_query, 'payments_sum', function ($join) {
            $join->on('payments_sum.invoice_id', '=', 'invoices.id');
        });

        $query->where('invoices.user_id', auth('sanctum')->id());

        $query->whereDate('invoices.invoice_date', '>=', $from);
        $query->whereDate('invoices.invoice_date', '<=', $to);

        $query->when($client_id != '', function ($query) use ($client_id) {
            $query->where('invoices.client_id', $client_id);
        });

        $query->when($activity_id != '', function ($query) use ($activity_id) {
            $query->where('invoices.activity_id', $activity_id);
        });

        $query->select([
            DB::raw('DATE_FORMAT(invoices.invoice_date, "%m") as month'),
            DB::raw('DATE_FORMAT(invoices.invoice_date, "%u") as week'),
            DB::raw('DATE(invoices.invoice_date) invoice_date'),
            DB::raw('CONCAT(clients.first_name, " ", clients.last_name) as client_name'),
            'invoices.id as id',
            'activities.name as activity_name',
            'invoice_days.hours',
            'invoice_days.trailers',
            'invoices.total',
            'payments_sum.payments',
        ]);

        $data = $query->get()->groupBy('month')->map(function ($item, $key) {
            return [
                'month' => $key,
                'month_sum_total' => $item->sum('total'),
                'weeks' => $item->groupBy('week'),
            ];
        })->toArray();

        return response()->json($data);
    }

    public function DueInvoices(Request $request)
    {
        $paginate = $request->paginate ?? 10;
        $client_id = $request->client_id;
        $activity_id = $request->activity_id;

        $query = Invoice::query();

        $query->with([
            'User',
            'Client',
            'Activity',
        ]);

        $query->withSum([
            'Payments' => fn($query) => $query->select(DB::raw('IFNULL(SUM(amount), 0)')),
        ], 'amount');

        $due_date = now()->addDays(3);

        if ($due_date) {
            $query->whereDate('due_date', '<=', $due_date->toDateString());
        }

        $query->where('user_id', auth('sanctum')->id());

        $query->when($client_id, function ($query) use ($client_id) {
            $query->where('client_id', $client_id);
        });

        $query->when($activity_id, function ($query) use ($activity_id) {
            $query->where('activity_id', $activity_id);
        });

        $query->havingRaw('payments_sum_amount < total');

        $data = $query->paginate($paginate);

        return response()->json([
            'data' => InvoiceResource::collection($data),
            'total' => $data->total(),
            'per_page' => $data->perPage(),
            'current_page' => $data->currentPage(),
            'last_page' => $data->lastPage(),
            'from' => $data->firstItem(),
            'to' => $data->lastItem(),
        ]);
    }

    public function getTotalInvoicesByMonth(Request $request)
    {
        $userId = auth('sanctum')->id();
        $year = $request->year ?? date('Y');

        $totals = Invoice::selectRaw('MONTH(invoice_date) as month, SUM(total) as total')
            ->whereYear('invoice_date', $year)
            ->where('user_id', $userId)
            ->groupByRaw('MONTH(invoice_date)')
            ->orderByRaw('MONTH(invoice_date)')
            ->get();

        return response()->json($totals);
    }

    public function getInvoicesByYear(Request $request)
    {
        $userId = auth('sanctum')->id();
        $year = $request->year ?? date('Y');

        $data = Invoice::where('user_id', $userId)
            ->whereYear('invoice_date', $year)
            ->get()
            ->groupBy(function ($invoice) {
                return Carbon::parse($invoice->invoice_date)->format('d-m-Y');
            })
            ->map(function ($invoices, $date) {
                return [
                    'date' => $date,
                    'invoices' => $invoices->sum('total'), 
                ];
            })
            ->values(); 

        return response()->json($data);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Http\Resources\PaymentResource;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $paginate = $request->paginate ?? 10;
        $client_id = $request->client;
        $invoice_id = $request->invoice;

        $query = Payment::query();

        $query->with([
            'Invoice.Client'
        ]);

        if (auth('sanctum')->user()->hasRole('client')) {
            $query->whereHas('Invoice', function ($query) {
                $query->where('user_id', auth('sanctum')->id());
            });
        }

        if ($client_id) {
            $query->whereHas('Invoice', function ($query) use ($client_id) {
                $query->where('client_id', $client_id);
            });
        }

        if ($invoice_id) {
            $query->where('invoice_id', $invoice_id);
        }

        $data = $query->paginate($paginate);

        return response()->json([
            'data' => PaymentResource::collection($data),
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
    public function store(StorePaymentRequest $request)
    {
        $payment = Payment::create($request->validated());

        $payment->load([
            'Invoice.Client'
        ]);

        return response()->json(new PaymentResource($payment));
    }

    /**
     * Display the specified resource.
     */
    public function show(Payment $payment)
    {
        $payment->load([
            'Invoice.Client'
        ]);

        return response()->json(new PaymentResource($payment));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePaymentRequest $request, Payment $payment)
    {
        $payment->update($request->validated());
        $payment->load([
            'Invoice.Client'
        ]);
        return response()->json(new PaymentResource($payment));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payment $payment)
    {
        if (auth('sanctum')->user()->hasRole('client') && $payment->Invoice->user_id != auth('sanctum')->id()) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        $payment->delete();

        return response()->noContent();
    }
}

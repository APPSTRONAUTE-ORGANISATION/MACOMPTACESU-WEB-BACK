<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'client_id' => $this->client_id,
            'activity_id' => $this->activity_id,
            'hours' => $this->hours,
            'trailers' => $this->trailers,
            'total' => $this->total,
            'due_date' => $this->due_date ? $this->due_date->toDateString() : null,
            'invoice_date' => $this->invoice_date ? $this->invoice_date->toDateString() : null,
            'invoice_file' => $this->invoice_file ? asset(Storage::url($this->invoice_file)) : null,
            'created_at' => $this->created_at,
            'user' => new UserResource($this->whenLoaded('User')),
            'client' => new ClientResource($this->whenLoaded('Client')),
            'activity' => new ActivityResource($this->whenLoaded('Activity')),
            'payments' => PaymentResource::collection($this->whenLoaded('Payments')),
            'invoice_days' => InvoiceDayResource::collection($this->whenLoaded('InvoiceDays')),
            'payments_sum_amount' => $this->whenAggregated('Payments', 'amount', 'sum'),
            'invoice_days_sum_hours' => $this->whenAggregated('InvoiceDays', 'hours', 'sum'),
            'invoice_days_count' => $this->whenCounted('InvoiceDays'),
        ];
    }
}

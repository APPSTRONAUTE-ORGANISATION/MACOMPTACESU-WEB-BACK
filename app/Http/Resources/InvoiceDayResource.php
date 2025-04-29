<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceDayResource extends JsonResource
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
            'invoice_id' => $this->invoice_id,
            'work_date' => $this->work_date->toDateString(),
            'hours' => $this->hours,
            'trailers' => $this->trailers,
            'created_at' => $this->created_at,
            'invoice' => new InvoiceResource($this->whenLoaded('Invoice')),
        ];
    }
}

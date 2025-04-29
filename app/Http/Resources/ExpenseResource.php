<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ExpenseResource extends JsonResource
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
            'expense_label_id' => $this->expense_label_id,
            'amount' => $this->amount,
            'expense_file' => asset(Storage::url($this->expense_file)),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'expense_label' => new ExpenseLabelResource($this->whenLoaded('ExpenseLabel')),
        ];
    }
}

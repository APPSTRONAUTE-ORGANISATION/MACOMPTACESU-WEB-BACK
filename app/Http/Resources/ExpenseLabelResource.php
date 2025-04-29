<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExpenseLabelResource extends JsonResource
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
            'expense_category_id' => $this->expense_category_id,
            'name' => $this->name,
            'created_at' => $this->created_at,
            'expense_category' => new ExpenseCategoryResource($this->whenLoaded('ExpenseCategory'))
        ];
    }
}

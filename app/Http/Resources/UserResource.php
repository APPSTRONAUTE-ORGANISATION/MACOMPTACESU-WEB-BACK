<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'country' => $this->country,
            'phone' => $this->phone,
            'job' => $this->job,
            'email' => $this->email,
            'role' => $this->roles->first()?->name,
            'profile_image' => $this->profile_image ? asset(Storage::url($this->profile_image)) : null,
            'active' => $this->active,
            'created_at' => $this->created_at,
            'pm_type' => $this->pm_type,
            'pm_last_four' => $this->pm_last_four,
            'exp_month' => $this->exp_month,
            'exp_year' => $this->exp_year,
            'card_holder' => $this->card_holder,
            'stripe_id' => $this->stripe_id,
            'subscriptions' => StripeSubscriptionResource::collection($this->whenLoaded('Subscriptions')),
        ];
    }
}

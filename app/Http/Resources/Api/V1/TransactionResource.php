<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'inventory_id' => $this->inventory_id,
            'code' => $this->code,
            'type' => $this->type,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'total' => $this->price * $this->quantity,
            'image' => new ImageResource($this->image),
        ];
    }
}

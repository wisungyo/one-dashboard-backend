<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionItemResource extends JsonResource
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
            'transaction_id' => $this->transaction_id,
            'inventory_id' => $this->inventory_id,
            'price' => $this->price,
            'quantity' => $this->quantity,
            'total' => $this->total,
            'note' => $this->note,
        ];
    }
}

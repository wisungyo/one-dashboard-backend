<?php

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'inventory_id',
        'code', // auto generate
        'type',
        'price',
        'quantity',
        'total',
        'note',
        'created_by',
    ];

    public function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'price' => 'float',
            'quantity' => 'integer',
            'total' => 'float',
        ];
    }

    public function inventory(): BelongsTo
    {
        return $this->belongsTo(Inventory::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'attachable');
    }

    public function image(): MorphOne
    {
        return $this->morphOne(Image::class, 'attachable');
    }
}

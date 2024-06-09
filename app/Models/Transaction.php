<?php

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'code', // auto generate
        'type',
        'total_item',
        'total_quantity',
        'total_price',
        'customer_name',
        'customer_phone',
        'customer_address',
        'note',
        'created_by',
        'created_at',
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

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
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

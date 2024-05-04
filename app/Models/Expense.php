<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'date',
        'total_item',
        'total_quantity',
        'amount',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
        ];
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

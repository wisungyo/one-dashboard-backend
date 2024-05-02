<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Image extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'type',
        'size',
        'mime_type',
        'file_name',
        'path',
        'height',
        'width',
        'attachable_type',
        'attachable_id',
    ];

    /**
     * Get all of the owning attachable models.
     */
    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get Url
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->path);
    }

    /**
     * Get Full Url
     */
    public function getFullurlAttribute(): string
    {
        return asset($this->url);
    }
}

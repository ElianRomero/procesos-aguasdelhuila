<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ObservacionArchivo extends Model
{
    protected $fillable = [
        'observacion_id',
        'disk',
        'path',
        'original_name',
        'mime',
        'size',
    ];

    public function observacion()
    {
        return $this->belongsTo(Observacion::class);
    }

    // (opcional) accesores útiles
    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}

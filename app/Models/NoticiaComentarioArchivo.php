<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class NoticiaComentarioArchivo extends Model
{
    protected $fillable = [
        'comentario_id',
        'disk',
        'path',
        'original_name',
        'mime',
        'size',
    ];

    public function comentario()
    {
        return $this->belongsTo(NoticiaComentario::class, 'comentario_id');
    }

    // Accesor útil
    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}

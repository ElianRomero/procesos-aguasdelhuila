<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;



class NoticiaArchivo extends Model
{
    protected $table = 'noticia_archivos';

    protected $fillable = [
        'noticia_id',
        'disk',
        'path',
        'original_name',
        'mime',
        'size',
    ];

    public function noticia()
    {
        return $this->belongsTo(Noticia::class);
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }
}

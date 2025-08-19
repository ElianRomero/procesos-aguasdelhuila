<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostulacionArchivo extends Model
{
    protected $fillable = [
        'proceso_codigo',
        'proponente_id',
        'requisito_key',
        'original_name',
        'path',
        'size_bytes',
    ];

    public function proceso()
    {
        return $this->belongsTo(Proceso::class, 'proceso_codigo', 'codigo');
    }

    public function proponente()
    {
        return $this->belongsTo(Proponente::class);
    }
}

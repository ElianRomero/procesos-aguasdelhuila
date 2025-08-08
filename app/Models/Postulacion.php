<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

class Postulacion extends Model
{
      use HasFactory;

    protected $table = 'postulaciones';

    protected $fillable = [
        'proponente_id',
        'proceso_codigo',
        'fecha_postulacion',
        'estado',         // Ej: ENVIADA, ACEPTADA, RECHAZADA
        'observacion',    // Texto libre para notas
    ];

    protected $casts = [
        'fecha_postulacion' => 'date',
    ];

    // Relaciones
    public function proponente()
    {
        return $this->belongsTo(Proponente::class);
    }

    public function proceso()
    {
        return $this->belongsTo(Proceso::class, 'proceso_codigo', 'codigo');
    }
}

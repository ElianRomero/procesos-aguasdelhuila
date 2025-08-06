<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proceso extends Model
{
   protected $table = 'procesos';
    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true; // ⬅️ ESTA LÍNEA ES CLAVE

    protected $fillable = [
        'codigo',
        'objeto',
        'link_secop',
        'valor',
        'fecha',
        'tipo_proceso_codigo',
        'estado_contrato_codigo',
        'tipo_contrato_codigo',
        'modalidad_codigo',
        'estado',
    ];

    // Relaciones
    public function tipoProceso()
    {
        return $this->belongsTo(TipoProceso::class, 'tipo_proceso_codigo', 'codigo');
    }

    public function estadoContrato()
    {
        return $this->belongsTo(EstadoContrato::class, 'estado_contrato_codigo', 'codigo');
    }

    public function tipoContrato()
    {
        return $this->belongsTo(TipoContrato::class, 'tipo_contrato_codigo', 'codigo');
    }
}

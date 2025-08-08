<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proceso extends Model
{
    protected $table = 'procesos';

    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

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
 protected $casts = [
        'fecha' => 'date', // 👈 importante
    ];
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

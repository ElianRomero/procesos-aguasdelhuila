<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proceso extends Model
{
    protected $table = 'procesos';

    // PK real: 'codigo' (string)
    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';

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
        'proponente_id',
        'requisitos',
    ];

    protected $casts = [
        'fecha' => 'date',
        'requisitos' => 'array',
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

    public function proponente()
    {
        return $this->belongsTo(Proponente::class, 'proponente_id', 'id');
    }

    public function proponentesPostulados()
    {

        return $this->belongsToMany(
            Proponente::class,
            'postulaciones',
            'proceso_codigo',
            'proponente_id',
            'codigo',
            'id'
        )
            ->withPivot(['estado', 'observacion', 'postulado_en'])
            ->withTimestamps();
    }
    // App\Models\Proceso.php
    public function getRouteKeyName(): string
    {
        return 'codigo';
    }
}

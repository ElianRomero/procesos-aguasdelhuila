<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proceso extends Model
{
    protected $table = 'procesos';

    protected $primaryKey = 'codigo';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'codigo',
        'objeto',
        'link_secop',
        'valor',
        'fecha' => 'datetime:Y-m-d',
        'tipo_proceso_codigo',
        'estado_contrato_codigo',
        'tipo_contrato_codigo',
        'modalidad_codigo',
        'estado',
        'proponente_id',
        'requisitos',
        'observaciones', // <- nota libre rápida (la conservamos)
        'observaciones_abren_en',
        'observaciones_cierran_en',
    ];

    protected $casts = [
        'fecha' => 'date',
        'requisitos' => 'array',
        'observaciones_abren_en' => 'datetime',
        'observaciones_cierran_en' => 'datetime',
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
        )->withPivot(['estado', 'observacion', 'postulado_en'])
            ->withTimestamps();
    }

    public function archivosPostulacion()
    {
        return $this->hasMany(PostulacionArchivo::class, 'proceso_codigo', 'codigo');
    }

    // 🔹 Observaciones formales (con adjuntos)
    public function observacionesFormales()
    {
        return $this->hasMany(Observacion::class, 'proceso_codigo', 'codigo')->latest();
    }

    public function getRouteKeyName(): string
    {
        return 'codigo';
    }
    public function tieneVentanaObservaciones(): bool
    {
        return !is_null($this->observaciones_abren_en) && !is_null($this->observaciones_cierran_en);
    }

    // ¿la ventana está abierta y además está definida?
    public function ventanaObservacionesAbiertaYDefinida(): bool
    {
        if (!$this->tieneVentanaObservaciones()) return false;
        return now()->between($this->observaciones_abren_en, $this->observaciones_cierran_en);
    }

    // 👇 Compatibilidad con vistas antiguas
    public function ventanaObservacionesAbierta(): bool
    {
        // Antes quizá devolvía true si no había ventana.
        // Ahora lo hacemos consistente: solo true si está definida y abierta.
        return $this->ventanaObservacionesAbiertaYDefinida();
    }
}

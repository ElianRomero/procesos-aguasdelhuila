<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Observacion extends Model
{
    protected $table = 'observaciones';
    protected $fillable = [
        'proceso_codigo',
        'proponente_id',
        'user_id',
        'asunto',
        'descripcion',
        'estado',
    ];

    public function proceso()
    {
        return $this->belongsTo(Proceso::class, 'proceso_codigo', 'codigo');
    }

    public function proponente()
    {
        return $this->belongsTo(Proponente::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function archivos()
    {
        return $this->hasMany(ObservacionArchivo::class);
    }
    public function esDueno($user): bool
    {
        if (!$user) return false;
        $userEsProp = optional($user->proponente)->id;
        return ($this->user_id === $user->id) || ($userEsProp && $this->proponente_id === $userEsProp);
    }

    public function puedeEditarPor($user): bool
    {
        if (!$this->proceso) $this->load('proceso');
        return $this->esDueno($user)
            && $this->estado === 'ENVIADA'
            && $this->proceso
            && $this->proceso->ventanaObservacionesAbiertaYDefinida();
    }
}

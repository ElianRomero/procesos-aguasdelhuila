<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Noticia extends Model
{
    use SoftDeletes;
    protected $table = 'noticias';

    protected $fillable = [
        'proceso_codigo',
        'autor_user_id',
        'destinatario_proponente_id',
        'titulo',
        'cuerpo',
        'tipo',
        'publico',
        'estado',
        'publicada_en',
    ];

    protected $casts = [
        'publico' => 'boolean',
        'publicada_en' => 'datetime',
    ];

    public function proceso()
    {
        return $this->belongsTo(Proceso::class, 'proceso_codigo', 'codigo');
    }

    public function autor()
    {
        return $this->belongsTo(User::class, 'autor_user_id');
    }

    public function destinatarioProponente()
    {
        return $this->belongsTo(Proponente::class, 'destinatario_proponente_id');
    }

    public function archivos()
    {
        return $this->hasMany(NoticiaArchivo::class);
    }

    /* ===== Scopes útiles ===== */
    public function scopeDelProceso($q, string $codigo)
    {
        return $q->where('proceso_codigo', $codigo);
    }

    public function scopePublicas($q)
    {
        return $q->where('publico', true);
    }

    public function scopePrivadasParaProponente($q, ?int $proponenteId)
    {
        if (!$proponenteId)
            return $q->whereRaw('1=0');
        return $q->where('publico', false)->where('destinatario_proponente_id', $proponenteId);
    }

    public function scopeVisiblesParaUsuario($q, $user)
    {
        // Admin/Entidad ven todo
        if ($user && method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            return $q;
        }

        $proponenteId = optional(optional($user)->proponente)->id;

        // Público + (privadas dirigidas al proponente logueado)
        return $q->where(function ($qq) use ($proponenteId) {
            $qq->where('publico', true)
                ->orWhere(function ($q2) use ($proponenteId) {
                    $q2->where('publico', false)
                        ->where('destinatario_proponente_id', $proponenteId);
                });
        });
    }

    public function esPublica(): bool
    {
        return (bool) $this->publico;
    }
    // app/Models/Noticia.php
    public function lecturas()
    {
        return $this->hasMany(NoticiaLectura::class);
    }
    public function lecturaDeProponente($proponenteId)
    {
        return $this->hasOne(NoticiaLectura::class)->where('proponente_id', $proponenteId);
    }

}

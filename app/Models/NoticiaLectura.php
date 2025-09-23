<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NoticiaLectura extends Model
{
    protected $table = 'noticia_lecturas';
    protected $fillable = ['noticia_id','proponente_id','read_at'];

    public function noticia() { return $this->belongsTo(Noticia::class); }
    public function proponente() { return $this->belongsTo(Proponente::class); }
}
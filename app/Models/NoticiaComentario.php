<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NoticiaComentario extends Model
{
    protected $fillable = ['noticia_id', 'user_id', 'proponente_id', 'parent_id', 'cuerpo'];

    public function noticia()
    {
        return $this->belongsTo(Noticia::class);
    }
    public function autor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function proponente()
    {
        return $this->belongsTo(Proponente::class, 'proponente_id');
    }
    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }
    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }
    // app/Models/NoticiaComentario.php
    public function archivos()
    {
        return $this->hasMany(NoticiaComentarioArchivo::class, 'comentario_id');
    }

}

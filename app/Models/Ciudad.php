<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ciudad extends Model
{
    use HasFactory;

    protected $fillable = ['codigo', 'nombre', 'departamento_id'];

    protected $table = 'ciudades';


    public function departamento()
    {
        return $this->belongsTo(Departamento::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Departamento extends Model
{
      use HasFactory;

    protected $fillable = ['codigo', 'nombre'];

    public function ciudades()
    {
        return $this->hasMany(Ciudad::class);
    }
}

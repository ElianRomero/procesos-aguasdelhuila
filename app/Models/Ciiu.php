<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Ciiu extends Model
{
      use HasFactory;

    protected $fillable = ['codigo', 'nombre'];
     protected $table = 'ciiu';

    public function proponentes()
    {
        return $this->hasMany(Proponente::class, 'ciiu_id');
    }
}

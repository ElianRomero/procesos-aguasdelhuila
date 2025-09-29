<?php
// app/Models/ProponenteOld.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProponenteOld extends Model
{
    protected $table = 'proponentes_old';
    protected $primaryKey = 'proponente_id';
    public $timestamps = false; // la tabla tiene sus propios campos fecha

    protected $fillable = [
        'estado_codigo',
        'tipo_identificacion_codigo',
        'proponente_razonsocial',
        'proponente_nit',
        'proponente_representante',
        'actividad_codigo',
        'municipio_id',
        'proponente_direccion',
        'proponente_telefono1',
        'proponente_telefono2',
        'proponente_correo',
        'proponente_actividadinicio',
        'proponente_fechacreacion',
        'proponente_fechamodificacion',
        'usuario_id',
        'proponente_usuarioaprobo',
        'proponente_observacion',
        'proponente_sitioweb',
    ];

    protected $casts = [
        'proponente_actividadinicio'   => 'date',
        'proponente_fechacreacion'     => 'datetime',
        'proponente_fechamodificacion' => 'datetime',
    ];
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Proponente;
use App\Models\Ciudad;
use App\Models\Ciiu;
use App\Models\TipoIdentificacion;
use Illuminate\Support\Facades\Auth;
use App\Models\Departamento;

class ProponenteController extends Controller
{
    public function create()
    {
        $departamentos = Departamento::with('ciudades')->get();
        $ciius = Ciiu::all();
        $tiposIdentificacion = TipoIdentificacion::all();

        // Verificar si ya hay proponente
        $proponente = Proponente::where('user_id', Auth::id())->first();

        return view('proponentes.create', compact('departamentos', 'ciius', 'tiposIdentificacion', 'proponente'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'ciudad_id' => 'required|exists:ciudades,id',
            'ciiu_id' => 'required|exists:ciiu,id',
            'tipo_identificacion_codigo' => 'required|exists:tipo_identificaciones,codigo',
            'razon_social' => 'required|string|max:255',
            'nit' => 'required|string|max:50|unique:proponentes,nit',
            'representante' => 'required|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'telefono1' => 'nullable|string|max:20',
            'telefono2' => 'nullable|string|max:20',
            'correo' => 'nullable|email|max:255',
            'sitio_web' => 'nullable|url',
            'google_drive_url' => 'nullable|url',
            'actividad_inicio' => 'nullable|date',
            'observacion' => 'nullable|string|max:1024',
        ]);

        Proponente::create([
            'user_id' => Auth::id(),
            ...$request->only([
                'ciudad_id',
                'ciiu_id',
                'tipo_identificacion_codigo',
                'razon_social',
                'nit',
                'representante',
                'direccion',
                'telefono1',
                'telefono2',
                'correo',
                'sitio_web',
                'google_drive_url',
                'actividad_inicio',
                'observacion'
            ])
        ]);

        return redirect()->route('dashboard')->with('success', 'Información registrada correctamente.');
    }
    public function update(Request $request, Proponente $proponente)
    {
        $request->validate([
            'ciudad_id' => 'required|exists:ciudades,id',
            'ciiu_id' => 'required|exists:ciiu,id',
            'tipo_identificacion_codigo' => 'required|exists:tipo_identificaciones,codigo',
            'razon_social' => 'required|string|max:255',
            'nit' => 'required|string|max:50|unique:proponentes,nit,' . $proponente->id,
            'representante' => 'required|string|max:255',
            'direccion' => 'nullable|string|max:255',
            'telefono1' => 'nullable|string|max:20',
            'telefono2' => 'nullable|string|max:20',
            'correo' => 'nullable|email|max:255',
            'sitio_web' => 'nullable|url',
            'google_drive_url' => 'nullable|url',
            'actividad_inicio' => 'nullable|date',
            'observacion' => 'nullable|string|max:1024',
        ]);

        $proponente->update($request->only([
            'ciudad_id',
            'ciiu_id',
            'tipo_identificacion_codigo',
            'razon_social',
            'nit',
            'representante',
            'direccion',
            'telefono1',
            'telefono2',
            'correo',
            'sitio_web',
            'google_drive_url',
            'actividad_inicio',
            'observacion'
        ]));

        return redirect()->route('proponente.create')->with('success', 'Información actualizada correctamente.');
    }
}

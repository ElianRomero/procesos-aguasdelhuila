<?php

namespace App\View\Components\Proponente;

use App\Models\Noticia;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\Component;

class WidgetNoticias extends Component
{
    public $ultimas;
    public $limit;
    public $unreadCount = 0;

    public function __construct($limit = 3)
    {
        $this->limit = (int) $limit;
        $this->ultimas = collect();

        $user = Auth::user();
        $proponente = optional($user)->proponente;
        if (!$proponente)
            return;

        $procesosCodigos = $proponente->procesosPostulados()->pluck('procesos.codigo');

        // Base de noticias relevantes para este proponente
        $base = Noticia::query()->where(function ($q) use ($procesosCodigos, $proponente) {
            $q->where(function ($qq) use ($procesosCodigos) {
                $qq->where('publico', true)
                    ->whereIn('proceso_codigo', $procesosCodigos);
            })->orWhere(function ($qq) use ($proponente) {
                $qq->where('publico', false)
                    ->where('destinatario_proponente_id', $proponente->id);
            });
        });

        // Contador de NO leídas
        $this->unreadCount = (clone $base)
            ->whereDoesntHave('lecturas', fn($lq) => $lq->where('proponente_id', $proponente->id))
            ->count();

        // Últimas N
        $this->ultimas = $base
            ->with('proceso')
            ->orderByDesc('publicada_en')->orderByDesc('id')
            ->limit($this->limit)
            ->get();
    }

    public function render()
    {
        return view('components.proponente.widget-noticias');
    }
}

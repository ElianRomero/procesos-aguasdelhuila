<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class RequisitosField extends Component
{
   public function __construct(
        public string $name = 'requisitos_json',
        public array|string $initial = [],   // ⬅️ aceptar array o string
        public string $label = 'Requisitos'
    ) {
        // Si viene como string (JSON), decodificar; si falla, dejar []
        $this->initial = is_string($initial)
            ? (json_decode($initial, true) ?: [])
            : $initial;
    }

    public function render(): View|Closure|string
    {
        return view('components.requisitos-field');
    }
}

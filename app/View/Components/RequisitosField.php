<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class RequisitosField extends Component
{
    public function __construct(
        public string $name = 'requisitos_json',
        public array $initial = [],
        public string $label = 'Requisitos'
    ) {}

    public function render(): View|Closure|string
    {
        return view('components.requisitos-field');
    }
}

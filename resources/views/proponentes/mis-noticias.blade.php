@extends('layouts.app')

@section('content')
    @php
      $propId = optional(auth()->user()->proponente)->id;
    @endphp

    <div class="max-w-6xl mx-auto p-4">
        <div class="mt-5">
            <h1 class="text-2xl font-semibold text-white mb-3">Mis noticias</h1>
        </div>

        <div class="bg-white border rounded overflow-x-auto">
            <table class="min-w-full divide-y">
                <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-600">
                    <tr>
                        <th class="px-3 py-2">Fecha</th>
                        <th class="px-3 py-2">Tipo/Alcance</th>
                        <th class="px-3 py-2">Proceso</th>
                        <th class="px-3 py-2">Título</th>
                        <th class="px-3 py-2">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y text-sm">
                    @forelse ($noticias as $n)
                        @php
                            $p = $n->proceso;
                            $urlProceso = route('procesos.noticias.index', ['proceso' => $n->proceso_codigo]);
                            $urlShow    = route('procesos.noticias.show', ['proceso' => $n->proceso_codigo, 'noticia' => $n->id]);
                            $leerUrl    = route('procesos.noticias.leer', ['proceso' => $n->proceso_codigo, 'noticia' => $n->id]);

                            // ¿no leída? (simple; si quieres performance, luego eager-load lecturas en el controlador)
                            $esNoLeida = $propId
                                ? !\App\Models\NoticiaLectura::where('noticia_id', $n->id)->where('proponente_id', $propId)->exists()
                                : false;
                        @endphp

                        <tr class="{{ $esNoLeida ? 'bg-blue-50' : '' }} transition-colors" data-noticia-id="{{ $n->id }}">
                            <td class="px-3 py-2 align-top text-gray-600 whitespace-nowrap">
                                {{ optional($n->publicada_en)->format('d/m/Y H:i') ?? $n->created_at->format('d/m/Y H:i') }}
                            </td>

                            <td class="px-3 py-2 align-top">
                                <div class="flex items-center gap-1 flex-wrap">
                                    <span class="inline-block text-[11px] px-2 py-0.5 rounded-full border
                                        {{ $esNoLeida ? 'bg-blue-100 text-blue-800 border-blue-200' : '' }}">
                                        {{ $n->tipo }}
                                    </span>
                                    @if ($n->publico)
                                        <span class="inline-block text-[11px] px-2 py-0.5 rounded-full bg-blue-50 text-blue-700">
                                            Pública
                                        </span>
                                    @else
                                        <span class="inline-block text-[11px] px-2 py-0.5 rounded-full bg-amber-50 text-amber-700">
                                            Privada
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <td class="px-3 py-2 align-top">
                                <div class="font-medium">{{ $n->proceso_codigo }}</div>
                                @if ($p && $p->objeto)
                                    <div class="text-xs text-gray-500 line-clamp-2">{{ $p->objeto }}</div>
                                @endif
                            </td>

                            <td class="px-3 py-2 align-top">
                                <div class="font-medium {{ $esNoLeida ? 'text-blue-800' : '' }}">{{ $n->titulo }}</div>
                            </td>

                            {{-- Acciones + puntico a la derecha si NO leída --}}
                            <td class="px-3 py-2 align-top whitespace-nowrap relative">
                                @if($esNoLeida)
                                    <span class="js-unread-dot absolute right-2 top-2 inline-block w-2 h-2 rounded-full bg-blue-500"></span>
                                @endif

                                <div class="flex flex-col gap-1 items-start">
                                    <a href="{{ $urlShow }}"
                                       class="text-xs text-indigo-600 hover:underline js-mark-leida"
                                       data-leer-url="{{ $leerUrl }}">
                                        Ver
                                    </a>
                                   
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-6 text-center text-gray-500">No hay noticias por ahora.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $noticias->withQueryString()->links() }}</div>
    </div>
@endsection

@section('scripts')
<script>
  (function(){
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    function markRead(url){
      if (!url || !token) return;
      if (navigator.sendBeacon) {
        const body = new Blob([new URLSearchParams({'_token': token})], {type: 'application/x-www-form-urlencoded'});
        navigator.sendBeacon(url, body);
        return;
      }
      fetch(url, { method: 'POST', headers: {'X-CSRF-TOKEN': token, 'Accept': 'application/json'}, keepalive: true })
        .catch(()=>{});
    }

    function updateRowUI(anchorEl){
      const tr = anchorEl.closest('tr[data-noticia-id]');
      if (!tr) return;
      tr.classList.remove('bg-blue-50');
      // quitar puntico
      const dot = tr.querySelector('.js-unread-dot');
      if (dot) dot.remove();
      // título a color normal
      const title = tr.querySelector('td:nth-child(4) .font-medium');
      if (title) title.classList.remove('text-blue-800');
      // badge de tipo a neutro
      const tipoBadge = tr.querySelector('td:nth-child(2) span.inline-block');
      if (tipoBadge){
        tipoBadge.classList.remove('bg-blue-100','text-blue-800','border-blue-200');
      }
    }

    document.querySelectorAll('.js-mark-leida').forEach(a=>{
      a.addEventListener('click', function(){
        const url = this.dataset.leerUrl;
        updateRowUI(this); // optimismo visual
        markRead(url);     // registrar en servidor
      }, {passive:true});
    });
  })();
</script>
@endsection

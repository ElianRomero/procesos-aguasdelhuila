@if (auth()->check() && optional(auth()->user()->proponente)->id)
    @php
        // contador de no leídas (si ya lo calculas en el componente, puedes quitar este bloque y usar $unreadCount directo)
        $unreadCount = $unreadCount ?? 0;
        if (!isset($unreadCount)) {
            $prop = optional(auth()->user())->proponente;
            $procCods = $prop?->procesosPostulados()->pluck('procesos.codigo') ?? collect();
            $unreadCount = \App\Models\Noticia::query()
                ->where(function ($q) use ($procCods, $prop) {
                    $q->where(function ($qq) use ($procCods) {
                        $qq->where('publico', true)->whereIn('proceso_codigo', $procCods);
                    })->orWhere(function ($qq) use ($prop) {
                        $qq->where('publico', false)->where('destinatario_proponente_id', $prop?->id);
                    });
                })
                ->whereDoesntHave('lecturas', fn($lq) => $lq->where('proponente_id', $prop?->id))
                ->count();
        }
        $propId = optional(auth()->user()->proponente)->id;
    @endphp

    <div class="mt-3 bg-white border rounded text-[12px] leading-snug">
        <div class="px-2 py-1.5 border-b flex items-center">
            <h2 class="text-[11px] font-semibold">Noticias</h2>

            @if ($unreadCount > 0)
                <span data-unread-badge
                    class="ml-2 inline-flex items-center justify-center min-w-[16px] px-1 h-4 rounded-full bg-red-600 text-white text-[10px]">
                    {{ $unreadCount }}
                </span>
            @endif

            <a href="{{ route('proponente.noticias.index') }}"
                class="ml-auto text-[10px] text-indigo-600 hover:underline">Ver todo</a>
        </div>

        @if ($ultimas->isEmpty())
            <div class="p-2 text-[11px] text-gray-500">No hay noticias por ahora.</div>
        @else
            <ul class="divide-y">
                @foreach ($ultimas as $n)
                    @php
                        $dt = $n->publicada_en ?? $n->created_at;
                        $mes =
                            [
                                '01' => 'ene',
                                '02' => 'feb',
                                '03' => 'mar',
                                '04' => 'abr',
                                '05' => 'may',
                                '06' => 'jun',
                                '07' => 'jul',
                                '08' => 'ago',
                                '09' => 'sep',
                                '10' => 'oct',
                                '11' => 'nov',
                                '12' => 'dic',
                            ][$dt->format('m')] ?? '';
                        $fechaBonita = $dt ? $dt->format('d') . ' ' . $mes . ' ' . $dt->format('Y') : '';

                        $urlShow = route('procesos.noticias.show', [
                            'proceso' => $n->proceso_codigo,
                            'noticia' => $n->id,
                        ]);
                        $leerUrl = route('procesos.noticias.leer', [
                            'proceso' => $n->proceso_codigo,
                            'noticia' => $n->id,
                        ]);

                        // ¿no leída?
                        $esNoLeida = $propId
                            ? !\App\Models\NoticiaLectura::where('noticia_id', $n->id)
                                ->where('proponente_id', $propId)
                                ->exists()
                            : false;
                    @endphp

                    <li class="px-2 py-1.5 transition-colors cursor-pointer {{ $esNoLeida ? 'bg-blue-50 hover:bg-blue-100' : 'hover:bg-gray-50' }} js-open-noticia"
                        data-noticia-id="{{ $n->id }}" data-show-url="{{ $urlShow }}"
                        data-leer-url="{{ $leerUrl }}" role="link" tabindex="0"
                        aria-label="Abrir noticia {{ $n->titulo }}">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                {{-- 1) Título (primero, truncado a 14) --}}
                                <div class="font-semibold text-[12px] js-title {{ $esNoLeida ? 'text-blue-800' : '' }}">
                                    {{ \Illuminate\Support\Str::limit($n->titulo, 14, '…') }}
                                </div>
                                {{-- 2) Fecha --}}
                                <div class="text-[10px] text-gray-500 mt-0.5">{{ $fechaBonita }}</div>
                                {{-- 3) Proceso --}}
                                <div class="text-[10px] text-gray-600 font-mono mt-0.5">{{ $n->proceso_codigo }}</div>
                            </div>

                            {{-- Badge del tipo con "bolita" si no leída --}}
                            <span
                                class="js-type-badge shrink-0 inline-flex items-center gap-1 text-[10px] px-1.5 py-0.5 rounded-full border
                 {{ $esNoLeida ? 'bg-blue-100 text-blue-800 border-blue-200' : 'bg-gray-50 text-gray-700 border-gray-200' }}">
                                @if ($esNoLeida)
                                    <span
                                        class="js-unread-dot inline-block w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                @endif
                                {{ $n->tipo }}
                            </span>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    {{-- Script: marcar como leída al click y actualizar UI sin frenar la navegación --}}
    <script>
  (function () {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    function markRead(url) {
      if (!url || !token) return;
      if (navigator.sendBeacon) {
        const body = new Blob([new URLSearchParams({'_token': token})], {type: 'application/x-www-form-urlencoded'});
        navigator.sendBeacon(url, body);
        return;
      }
      fetch(url, { method: 'POST', headers: {'X-CSRF-TOKEN': token, 'Accept': 'application/json'}, keepalive: true }).catch(() => {});
    }

    function updateUI(li) {
      if (!li) return;
      li.classList.remove('bg-blue-50', 'hover:bg-blue-100');
      li.classList.add('hover:bg-gray-50');

      const title = li.querySelector('.js-title');
      title?.classList.remove('text-blue-800');

      const badge = li.querySelector('.js-type-badge');
      if (badge) {
        badge.classList.remove('bg-blue-100','text-blue-800','border-blue-200');
        badge.classList.add('bg-gray-50','text-gray-700','border-gray-200');
        badge.querySelector('.js-unread-dot')?.remove();
      }

      const badgeCounter = document.querySelector('[data-unread-badge]');
      if (badgeCounter) {
        const n = parseInt(badgeCounter.textContent.trim(), 10);
        if (!isNaN(n) && n > 0) {
          const next = n - 1;
          if (next > 0) badgeCounter.textContent = String(next);
          else badgeCounter.remove();
        }
      }
    }

    function openNoticia(li) {
      const showUrl = li.dataset.showUrl;
      const leerUrl = li.dataset.leerUrl;
      updateUI(li);      // optimismo visual inmediato
      markRead(leerUrl); // dispara al backend con sendBeacon/keepalive
      if (showUrl) window.location.href = showUrl;
    }

    document.querySelectorAll('li.js-open-noticia').forEach(li => {
      li.addEventListener('click', function () { openNoticia(this); }, { passive: true });
      li.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          openNoticia(this);
        }
      });
    });
  })();
</script>

@endif

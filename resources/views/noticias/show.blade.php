@extends('layouts.app')

@section('content')
    @php
        $dt = $noticia->publicada_en ?? $noticia->created_at;
        $mes = [
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
        ];
        $fechaBonita = $dt ? $dt->format('d') . ' ' . $mes[$dt->format('m')] . ' ' . $dt->format('Y') : '';
    @endphp

    <div class="max-w-4xl mx-auto p-4 md:p-6">
        {{-- migas --}}
        <nav class="text-xs text-white/80 mb-3 flex items-center gap-2">
            <a href="{{ route('proponente.noticias.index') }}" class="underline hover:text-white">← Mis noticias</a>
            <span class="opacity-60">/</span>
            <a href="{{ route('procesos.noticias.index', $proceso) }}" class="underline hover:text-white">
                Proceso {{ $proceso->codigo }}
            </a>
        </nav>

        {{-- tarjeta blanca --}}
        <div class="bg-white border rounded-2xl shadow-sm overflow-hidden">

            {{-- header --}}
            <div class="px-5 py-4 border-b bg-white/60">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h1 class="text-xl md:text-2xl font-semibold text-gray-900">
                            {{ $noticia->titulo }}
                        </h1>

                        <div class="mt-2 flex flex-wrap items-center gap-2 text-sm text-gray-600">
                            {{-- tipo --}}
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full border border-green-200 bg-green-100 text-green-800 text-xs font-medium">
                                {{ $noticia->tipo }}
                            </span>

                            <span class="text-gray-300">•</span>

                            {{-- fecha --}}
                            <span class="inline-flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24"
                                    fill="currentColor">
                                    <path
                                        d="M7 2a1 1 0 0 1 1 1v1h8V3a1 1 0 1 1 2 0v1h1a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3H5a3 3 0 0 1-3-3V7a3 3 0 0 1 3-3h1V3a1 1 0 0 1 1-1Zm13 8H4v9a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-9ZM6 8h12V7H6v1Z" />
                                </svg>
                                {{ $fechaBonita }}
                            </span>

                            <span class="text-gray-300">•</span>

                            {{-- proceso --}}
                            <span class="inline-flex items-center gap-1">
                                <span class="text-[11px] font-mono px-1.5 py-0.5 rounded bg-gray-100 border text-gray-700">
                                    {{ $noticia->proceso_codigo }}
                                </span>
                            </span>
                        </div>
                    </div>

                    {{-- acciones rápidas --}}
                    <div class="flex items-center gap-2">
                        <a href="{{ route('proponente.noticias.index') }}"
                            class="text-xs px-3 py-1.5 border rounded-lg hover:bg-gray-50">Volver</a>
                    </div>
                </div>
                <div class="text-[15px] leading-relaxed text-gray-800 whitespace-pre-line">
                    {{ $noticia->cuerpo }}
                </div>
            </div>

            {{-- cuerpo --}}
            <div class="px-5 py-6">
               

                {{-- adjuntos de la noticia --}}
                @if ($noticia->archivos && $noticia->archivos->count())
                    <div class="mt-8">
                        <h2 class="text-sm font-semibold text-gray-900 mb-3">Adjuntos</h2>
                        <ul class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            @foreach ($noticia->archivos as $a)
                                @php
                                    $url = \Illuminate\Support\Facades\Storage::disk($a->disk)->url($a->path);
                                    $peso = $a->size
                                        ? ($a->size >= 1048576
                                            ? number_format($a->size / 1048576, 2) . ' MB'
                                            : number_format($a->size / 1024, 0) . ' KB')
                                        : null;
                                @endphp
                                <li>
                                    <a href="{{ $url }}" target="_blank"
                                        class="flex items-start gap-3 p-3 border rounded-lg hover:bg-gray-50 transition">
                                        <span
                                            class="mt-0.5 inline-flex h-9 w-9 items-center justify-center rounded-md border bg-white">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600"
                                                viewBox="0 0 24 24" fill="currentColor">
                                                <path
                                                    d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Zm0 2.5L18.5 9H14Z" />
                                            </svg>
                                        </span>
                                        <span class="min-w-0">
                                            <span
                                                class="block text-sm font-medium text-gray-900 truncate">{{ $a->original_name }}</span>
                                            <span class="block text-xs text-gray-500">
                                                {{ $a->mime ?? 'archivo' }} @if ($peso)
                                                    • {{ $peso }}
                                                @endif
                                            </span>
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- ====== Foro de respuestas ====== --}}
                <div class="px-5 py-2">
                    <h2 class="text-sm font-semibold text-gray-900 mb-3">
                        Respuestas
                        ({{ $noticia->comentarios->count() + $noticia->comentarios->sum(fn($c) => $c->children->count()) }})
                    </h2>

                    {{-- Form crear comentario --}}
                    @auth
                        <form method="POST"
                            action="{{ route('procesos.noticias.comentarios.store', ['proceso' => $proceso->codigo, 'noticia' => $noticia->id]) }}"
                            class="mb-5" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="parent_id" id="reply_parent_id">

                            <div class="bg-gray-50 border rounded-lg">
                                <div class="px-3 py-2 border-b flex items-center gap-2 text-xs text-gray-600"
                                    id="replyingToWrap" style="display:none;">
                                    Respondiendo a <span id="replyingToUser" class="font-medium"></span>
                                    <button type="button" class="ml-auto text-red-600 hover:underline"
                                        id="cancelReply">Cancelar</button>
                                </div>
                                <textarea name="cuerpo" rows="3" class="w-full px-3 py-2 rounded-b-lg bg-white focus:outline-none"
                                    placeholder="Escribe tu respuesta…">{{ old('cuerpo') }}</textarea>
                            </div>
                            @error('cuerpo')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror

                            {{-- Adjuntos --}}
                            <div class="mt-2">
                                <label class="block text-xs text-gray-700 mb-1">Adjuntar archivos (opcional)</label>
                                <input type="file" name="archivos[]" multiple accept=".pdf,image/*"
                                    class="block w-full text-xs file:mr-3 file:px-3 file:py-1.5 file:border file:rounded file:bg-gray-100 file:text-gray-700">
                                @error('archivos')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                                @error('archivos.*')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror

                            </div>

                            <div class="mt-2 flex items-center gap-2">
                                <button class="px-3 py-1.5 text-xs rounded-lg bg-indigo-600 text-white">Publicar</button>

                            </div>
                        </form>
                    @endauth

                    {{-- Lista de comentarios (nivel 1 + respuestas anidadas) --}}
                    <ul class="space-y-3">
                        @forelse($noticia->comentarios as $c)
                            <li id="c{{ $c->id }}" class="p-3 border rounded-lg">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="shrink-0 h-8 w-8 rounded-full bg-gray-200 flex items-center justify-center text-xs text-gray-600">
                                        {{ strtoupper(mb_substr($c->autor->name ?? 'U', 0, 1)) }}
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2 text-xs text-gray-600">
                                            <span
                                                class="font-medium text-gray-800">{{ $c->autor->name ?? 'Usuario' }}</span>
                                            @if ($c->proponente)
                                                <span class="px-1.5 py-0.5 rounded border text-[10px]">Proponente</span>
                                            @else
                                                <span
                                                    class="px-1.5 py-0.5 rounded border bg-gray-100 text-[10px]">Admin/Entidad</span>
                                            @endif
                                            <span>•</span>
                                            <span>{{ $c->created_at->format('d/m/Y H:i') }}</span>

                                            {{-- borrar (dueño o admin) --}}
                                            @auth
                                                @if ($c->user_id === auth()->id() || auth()->user()->can('isAdmin'))
                                                    <form method="POST"
                                                        action="{{ route('procesos.noticias.comentarios.destroy', [$proceso->codigo, $noticia->id, $c->id]) }}"
                                                        class="ml-auto" onsubmit="return confirm('¿Eliminar comentario?');">
                                                        @csrf @method('DELETE')
                                                        <button class="text-xs text-red-600 hover:underline">Eliminar</button>
                                                    </form>
                                                @endif
                                            @endauth
                                        </div>

                                        <div class="mt-1 text-sm text-gray-800 whitespace-pre-line">{{ $c->cuerpo }}
                                        </div>

                                        {{-- adjuntos del comentario padre --}}
                                        @if ($c->archivos->count())
                                            <ul class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                @foreach ($c->archivos as $a)
                                                    @php
                                                        $url = route('procesos.noticias.comentarios.archivos.ver', [
                                                            'proceso' => $proceso->codigo,
                                                            'noticia' => $noticia->id,
                                                            'comentario' => $c->id,
                                                            'archivo' => $a->id,
                                                        ]);
                                                        $peso = $a->size
                                                            ? ($a->size >= 1048576
                                                                ? number_format($a->size / 1048576, 2) . ' MB'
                                                                : number_format($a->size / 1024, 0) . ' KB')
                                                            : null;
                                                    @endphp
                                                    <li>
                                                        <a href="{{ $url }}" target="_blank"
                                                            class="flex items-start gap-2 p-2 border rounded hover:bg-gray-50">
                                                            <span
                                                                class="inline-flex h-8 w-8 items-center justify-center rounded border bg-white">
                                                                <svg xmlns="http://www.w3.org/2000/svg"
                                                                    class="h-4 w-4 text-gray-600" viewBox="0 0 24 24"
                                                                    fill="currentColor">
                                                                    <path
                                                                        d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Zm0 2.5L18.5 9H14Z" />
                                                                </svg>
                                                            </span>
                                                            <span class="min-w-0">
                                                                <span
                                                                    class="block text-xs font-medium text-gray-900 truncate">{{ $a->original_name }}</span>
                                                                <span
                                                                    class="block text-[11px] text-gray-500">{{ $a->mime ?? 'archivo' }}
                                                                    @if ($peso)
                                                                        • {{ $peso }}
                                                                    @endif
                                                                </span>
                                                            </span>
                                                        </a>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif

                                        <div class="mt-2 flex items-center gap-3">
                                            @auth
                                                <button type="button"
                                                    class="text-xs text-indigo-600 hover:underline js-reply"
                                                    data-id="{{ $c->id }}"
                                                    data-user="{{ $c->autor->name ?? 'Usuario' }}">Responder</button>
                                            @endauth
                                        </div>

                                        {{-- hijos --}}
                                        @if ($c->children->isNotEmpty())
                                            <ul class="mt-3 space-y-2 border-l pl-3">
                                                @foreach ($c->children as $h)
                                                    <li id="c{{ $h->id }}"
                                                        class="p-2 border rounded-lg bg-white/60">
                                                        <div class="flex items-start gap-3">
                                                            <div
                                                                class="shrink-0 h-7 w-7 rounded-full bg-gray-200 flex items-center justify-center text-[11px] text-gray-600">
                                                                {{ strtoupper(mb_substr($h->autor->name ?? 'U', 0, 1)) }}
                                                            </div>
                                                            <div class="min-w-0 flex-1">
                                                                <div
                                                                    class="flex items-center gap-2 text-[11px] text-gray-600">
                                                                    <span
                                                                        class="font-medium text-gray-800">{{ $h->autor->name ?? 'Usuario' }}</span>
                                                                    @if ($h->proponente)
                                                                        <span
                                                                            class="px-1.5 py-0.5 rounded border text-[10px]">Proponente</span>
                                                                    @else
                                                                        <span
                                                                            class="px-1.5 py-0.5 rounded border bg-gray-100 text-[10px]">Admin/Entidad</span>
                                                                    @endif
                                                                    <span>•</span>
                                                                    <span>{{ $h->created_at->format('d/m/Y H:i') }}</span>

                                                                    @auth
                                                                        @if ($h->user_id === auth()->id() || auth()->user()->can('isAdmin'))
                                                                            <form method="POST"
                                                                                action="{{ route('procesos.noticias.comentarios.destroy', [$proceso->codigo, $noticia->id, $h->id]) }}"
                                                                                class="ml-auto"
                                                                                onsubmit="return confirm('¿Eliminar comentario?');">
                                                                                @csrf @method('DELETE')
                                                                                <button
                                                                                    class="text-[11px] text-red-600 hover:underline">Eliminar</button>
                                                                            </form>
                                                                        @endif
                                                                    @endauth
                                                                </div>

                                                                <div
                                                                    class="mt-1 text-[13px] text-gray-800 whitespace-pre-line">
                                                                    {{ $h->cuerpo }}</div>

                                                                {{-- adjuntos del comentario hijo --}}
                                                                @if ($h->archivos->count())
                                                                    <ul class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                                        @foreach ($h->archivos as $a)
                                                                            @php
                                                                                $url = route(
                                                                                    'procesos.noticias.comentarios.archivos.ver',
                                                                                    [
                                                                                        'proceso' => $proceso->codigo,
                                                                                        'noticia' => $noticia->id,
                                                                                        'comentario' => $h->id,
                                                                                        'archivo' => $a->id,
                                                                                    ],
                                                                                );
                                                                                $peso = $a->size
                                                                                    ? ($a->size >= 1048576
                                                                                        ? number_format(
                                                                                                $a->size / 1048576,
                                                                                                2,
                                                                                            ) . ' MB'
                                                                                        : number_format(
                                                                                                $a->size / 1024,
                                                                                                0,
                                                                                            ) . ' KB')
                                                                                    : null;
                                                                            @endphp
                                                                            <li>
                                                                                <a href="{{ $url }}"
                                                                                    target="_blank"
                                                                                    class="flex items-start gap-2 p-2 border rounded hover:bg-gray-50">
                                                                                    <span
                                                                                        class="inline-flex h-8 w-8 items-center justify-center rounded border bg-white">
                                                                                        <svg xmlns="http://www.w3.org/2000/svg"
                                                                                            class="h-4 w-4 text-gray-600"
                                                                                            viewBox="0 0 24 24"
                                                                                            fill="currentColor">
                                                                                            <path
                                                                                                d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8Zm0 2.5L18.5 9H14Z" />
                                                                                        </svg>
                                                                                    </span>
                                                                                    <span class="min-w-0">
                                                                                        <span
                                                                                            class="block text-xs font-medium text-gray-900 truncate">{{ $a->original_name }}</span>
                                                                                        <span
                                                                                            class="block text-[11px] text-gray-500">{{ $a->mime ?? 'archivo' }}
                                                                                            @if ($peso)
                                                                                                • {{ $peso }}
                                                                                            @endif
                                                                                        </span>
                                                                                    </span>
                                                                                </a>
                                                                            </li>
                                                                        @endforeach
                                                                    </ul>
                                                                @endif

                                                                @auth
                                                                    <div class="mt-1">
                                                                        <button type="button"
                                                                            class="text-[11px] text-indigo-600 hover:underline js-reply"
                                                                            data-id="{{ $c->id }}"
                                                                            data-user="{{ $c->autor->name ?? 'Usuario' }}">
                                                                            Responder al hilo
                                                                        </button>
                                                                    </div>
                                                                @endauth
                                                            </div>
                                                        </div>
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif

                                    </div>
                                </div>
                            </li>
                        @empty
                            <li class="p-3 text-sm text-gray-500">Aún no hay respuestas. ¡Sé el primero en comentar!</li>
                        @endforelse
                    </ul>
                </div>

                {{-- JS: manejar "Responder" (setea parent_id y muestra el banner) --}}
                @auth
                    <script>
                        (function() {
                            const parentInput = document.getElementById('reply_parent_id');
                            const wrap = document.getElementById('replyingToWrap');
                            const toUser = document.getElementById('replyingToUser');
                            const cancelBtn = document.getElementById('cancelReply');

                            document.querySelectorAll('.js-reply').forEach(b => {
                                b.addEventListener('click', function() {
                                    parentInput.value = this.dataset.id;
                                    toUser.textContent = this.dataset.user || 'Usuario';
                                    wrap.style.display = '';
                                    try {
                                        window.scrollTo({
                                            top: wrap.getBoundingClientRect().top + window.scrollY - 120,
                                            behavior: 'smooth'
                                        });
                                    } catch (e) {}
                                });
                            });

                            cancelBtn?.addEventListener('click', function() {
                                parentInput.value = '';
                                wrap.style.display = 'none';
                            });
                        })
                        ();
                    </script>
                @endauth

            </div>
        </div>
    </div>
@endsection

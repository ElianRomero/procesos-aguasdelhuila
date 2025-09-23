@extends('layouts.app')

@section('styles')
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <style>
    .line-clamp-2 { display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient: vertical; overflow:hidden; }
  </style>
@endsection

@section('content')
<div class="max-w-6xl mx-auto p-4">
  <div class="flex items-center gap-3 mt-5">
    <h1 class="text-2xl font-semibold">Noticias — Global (ADMIN)</h1>
    <a href="{{ route('admin.noticias.create') }}" class="ml-auto px-3 py-2 rounded bg-blue-600 text-white text-sm">Crear noticia</a>
  </div>

  <div class="bg-white border rounded overflow-x-auto mt-4">
    <table id="tablaNoticias" class="min-w-full divide-y">
      <thead class="bg-gray-50 text-left text-xs font-semibold text-gray-600">
        <tr>
          <th class="px-3 py-2">Fecha</th>
          <th class="px-3 py-2">Usuario</th>
          <th class="px-3 py-2">Proceso</th>
          <th class="px-3 py-2">Título</th>
          <th class="px-3 py-2">Alcance</th>
          <th class="px-3 py-2">Acciones</th>
        </tr>
      </thead>
      <tbody class="divide-y text-sm"></tbody>
    </table>
  </div>
</div>
@endsection

@section('scripts')
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <script>
    $(function () {
      const tabla = $('#tablaNoticias').DataTable({
        ajax: { url: "{{ route('admin.noticias.data') }}", dataSrc: 'data' },
        columns: [
          { data: 'fecha' },
          { data: 'usuario' },
          { data: 'proceso' },
          { data: 'titulo' },
          { data: 'alcance' },
          { data: 'acciones', orderable:false, searchable:false },
        ],
        order: [[0, 'desc']],
        pageLength: 25,
        language: { url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json' },
        createdRow: function (row, data) {
          // aplica padding a celdas
          $('td', row).addClass('px-3 py-2 align-top');
        },
        drawCallback: function () {
          // eliminar via fetch con CSRF
          $('.btn-eliminar').off('click').on('click', function () {
            const proceso = $(this).data('proceso');
            const id = $(this).data('id');
            if (!confirm('¿Eliminar noticia?')) return;

            fetch(`/procesos/${encodeURIComponent(proceso)}/noticias/${id}`, {
              method: 'POST',
              headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'X-HTTP-Method-Override': 'DELETE'
              }
            })
            .then(r => r.ok ? r.text() : Promise.reject())
            .then(() => tabla.ajax.reload(null, false))
            .catch(() => alert('Error eliminando la noticia'));
          });
        }
      });
    });
  </script>
@endsection

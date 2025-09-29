@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto p-4 mt-5">
  <div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold text-white">Proponentes — Certificados</h1>

    <a href="{{ route('proponentes.old.create') }}"
       class="inline-flex items-center gap-2 bg-white hover:bg-teal-700 text-black px-4 py-2 rounded-lg shadow-sm transition">
      <span class="text-lg">＋</span>
      <span>Registrar proponente </span>
    </a>
  </div>

  <table id="tabla" class="display w-100 table table-striped" style="width:100%">
    <thead>
      <tr>
        <th>Razón social</th>
        <th>NIT</th>
        <th>Actividad</th>
        <th>Teléfono</th>
        <th>Correo</th>
        <th class="text-end">Acciones</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>
@endsection

@section('scripts')
    {{-- jQuery + DataTables --}}
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script>
      $(function () {
        $('#tabla').DataTable({
          processing: true,
          ajax: {
            url: "{{ route('proponentes.certificados.data') }}",
            type: 'GET',
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            dataSrc: function (json) {
              if (typeof json === 'string') {
                console.warn('Respuesta NO-JSON:', json);
                try { json = JSON.parse(json); } catch(e) {
                  alert('El servidor no devolvió JSON válido.');
                  return [];
                }
              }
              if (!json || !json.data) {
                console.warn('JSON inesperado:', json);
                return [];
              }
              return json.data;
            },
            error: function (xhr) {
              console.error('DT AJAX error', xhr.status, xhr.responseText);
              alert('Error cargando datos (' + xhr.status + '). Revisa Network -> Response.');
            }
          },
          columns: [
            { data: 'razon_social' },
            { data: 'nit' },
            { data: 'ciiu' },
            { data: 'telefono1' },
            { data: 'correo' },
            {
              data: 'certificado_url',
              orderable: false,
              searchable: false,
              className: 'text-end',
              render: function (url) {
                const ver = url.includes('?') ? url + '&disposition=inline' : url + '?disposition=inline';
                return `
                  <div class="btn-group" role="group">
                    <a href="${ver}" class="btn btn-sm btn-secondary" target="_blank" rel="noopener">Ver</a>
                    <a href="${url}" class="btn btn-sm btn-primary"   target="_blank" rel="noopener">Descargar</a>
                  </div>`;
              }
            }
          ],
          language: {
            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
          }
        });
      });
    </script>
@endsection

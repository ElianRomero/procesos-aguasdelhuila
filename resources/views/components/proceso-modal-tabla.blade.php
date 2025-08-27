<div id="modal-tabla"
x-init="window.openModalTabla = (payload) => open(payload)"
 x-data="{

    show: false,
    det: {
        codigo: '',
        fecha: '',
        objeto: '',
        valor: '',
        tipo: '',
        estado_contrato: '',
        tipo_contrato: '',
        link: '',
        secop_url: '',
        requisitos: [],
        ya: false,
        observaciones: '',
        estado: '',
        ventana_definida: false,
        ventana_abierta: false,
        ventana_abre_fmt: '',
        ventana_cierra_fmt: '',
        obs_create_url: '',
        postular_url: '',
        archivos_url: ''
    },
    secopUrl(u) {
        if (!u) return '';
        if (/^https?:\/\//i.test(u)) {
            const m = u.match(/numConstancia=([^&]+)/i);
            return m ? ('https://www.contratos.gov.co/consultas/detalleProceso.do?numConstancia=' + encodeURIComponent(m[1])) : u;
        }
        return 'https://www.contratos.gov.co/consultas/detalleProceso.do?numConstancia=' + encodeURIComponent(u);
    },
    open(p) {
      if (!p || !p.__click || !p.codigo) return;
        this.det = Object.assign({}, this.det, p);
        this.det.secop_url = this.secopUrl(this.det.link || '');
        this.show = true;
    }
}" x-on:modal-proceso-tabla-open.document="open($event.detail)" x-cloak>

    <div x-show="show" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-xl w-[96vw] max-w-5xl h-[80vh] p-8 md:p-10 overflow-y-auto">
            <div class="flex items-center justify-between mb-4 sticky top-0 z-10">
                <h3 class="text-lg font-semibold"></h3>
                <button @click="show=false" class="text-gray-500 hover:text-gray-700">✕</button>
            </div>

            <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-8 gap-y-3 text-sm">
                <div>
                    <dt class="font-medium text-blue-700">Código</dt>
                    <dd x-text="det.codigo"></dd>
                </div>
                <div>
                    <dt class="font-medium text-blue-700">Fecha</dt>
                    <dd x-text="det.fecha"></dd>
                </div>
                <div class="sm:col-span-2 lg:col-span-3">
                    <dt class="font-medium text-blue-700">Objeto</dt>
                    <dd class="whitespace-pre-line" x-text="det.objeto"></dd>
                </div>
                <div>
                    <dt class="font-medium text-blue-700">Valor</dt>
                    <dd x-text="det.valor"></dd>
                </div>
                <div>
                    <dt class="font-medium text-blue-700">Tipo de Proceso</dt>
                    <dd x-text="det.tipo||'—'"></dd>
                </div>
                <div>
                    <dt class="font-medium text-blue-700">Estado Contrato</dt>
                    <dd x-text="det.estado_contrato||'—'"></dd>
                </div>
                <div>
                    <dt class="font-medium text-blue-700">Tipo de Contrato</dt>
                    <dd x-text="det.tipo_contrato||'—'"></dd>
                </div>
            </dl>

            <div class="mt-5 p-3 rounded-lg bg-gray-50 border text-[13px] leading-relaxed text-gray-700">
                Estimado interesado, en cumplimiento de la Ley 2195 de 2022 Art. 53, mediante el cual se adiciona el
                Art. 13 de la Ley 1150 de 2007, el presente contrato se encuentra publicado en el SECOP II y podrá
                acceder a través del siguiente botón.
            </div>

            <div class="mt-4" x-show="det.secop_url">
                <a :href="det.secop_url" target="_blank" rel="noopener noreferrer"
                    class="inline-flex items-center px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-medium">
                    Ver en SECOP
                </a>
            </div>

            <div class="mt-6">
                <h4 class="font-semibold mb-2">Requisitos</h4>
                <template x-if="!(det.requisitos && det.requisitos.length)">
                    <p class="text-sm text-gray-500">Este proceso no tiene requisitos configurados.</p>
                </template>
                <div class="max-h-48 overflow-y-auto border rounded-lg p-3 bg-gray-50"
                    x-show="det.requisitos && det.requisitos.length">
                    <ul class="list-disc pl-6 space-y-1">
                        <template x-for="r in det.requisitos" :key="r.key">
                            <li class="text-sm text-gray-700" x-text="r.name"></li>
                        </template>
                    </ul>
                </div>

                <div class="mt-6 flex flex-wrap gap-3 items-center">
                    <template x-if="!det.ya && det.estado==='CREADO'">
                        <form x-ref="formInteresado" :action="det.postular_url" method="POST" class="inline"
                            @submit="$event.target.querySelector('button[type=submit]').disabled=true">
                            @csrf
                            <input type="hidden" name="redirect_to" :value="det.archivos_url">
                            <button type="submit"
                                class="px-4 py-2 rounded bg-blue-600 hover:bg-blue-700 text-white">Interesado</button>
                        </form>
                    </template>

                    <button @click="show=false" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>

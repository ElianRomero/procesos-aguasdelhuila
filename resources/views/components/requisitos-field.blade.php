@props([])

<style>
    [x-cloak] {
        display: none !important
    }
</style>

<div x-data x-init="$store.reqs?.init(@js($initial)) ?? initReqsStore(@js($initial))" class="mt-6">
    <div class="flex items-center justify-between">
        <div>
            <h3 class="text-lg font-semibold">{{ $label ?? 'Requisitos' }}</h3>
            <p class="text-sm text-gray-500"><span x-text="$store.reqs.items.length"></span> definidos</p>
        </div>
        <button type="button" @click="$store.reqs.openModal()"
            class="px-3 py-2 rounded bg-blue-600 text-white hover:bg-blue-700">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor"
                class="bi bi-plus-circle-fill" viewBox="0 0 16 16">
                <path
                    d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0M8.5 4.5a.5.5 0 0 0-1 0v3h-3a.5.5 0 0 0 0 1h3v3a.5.5 0 0 0 1 0v-3h3a.5.5 0 0 0 0-1h-3z" />
            </svg>
        </button>
    </div>

    <div class="mt-3 flex flex-wrap gap-2">
        <template x-for="r in $store.reqs.items" :key="r.key">
            <span class="px-2 py-1 text-xs rounded bg-gray-100 border" x-text="r.name"></span>
        </template>
        <template x-if="$store.reqs.items.length === 0">
            <span class="text-xs text-gray-400">Sin requisitos…</span>
        </template>
    </div>

    {{-- JSON para backend --}}
    <input type="hidden" name="{{ $name ?? 'requisitos_json' }}" x-ref="reqHidden"
        x-effect="$refs.reqHidden.value = $store.reqs.serialize()" />


</div>

{{-- MODAL MINIMAL --}}
<div x-data x-show="$store.reqs.open" x-cloak @keydown.escape.window="$store.reqs.closeModal()"
    class="fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black/40" @click="$store.reqs.closeModal()"></div>

    <div class="relative bg-white w-full max-w-2xl rounded-xl shadow-xl p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-semibold">Requisitos (PDF)</h2>
            <button class="text-gray-500 hover:text-gray-700" @click="$store.reqs.closeModal()">✕</button>
        </div>

        <div class="mb-3 flex items-center justify-between">
            <div class="text-sm text-gray-500">
                <span x-text="$store.reqs.items.length"></span> requisitos
            </div>
            <button type="button" @click="$store.reqs.add()"
                class="px-3 py-1 rounded bg-blue-600 text-white hover:bg-blue-700">
                + Agregar
            </button>
        </div>

        <div class="space-y-3 max-h-[60vh] overflow-auto pr-1">
            <template x-for="(it, idx) in $store.reqs.items" :key="it._id">
                <div class="border rounded-lg p-3">
                    <div class="grid md:grid-cols-6 gap-3 items-end">
                        <div class="md:col-span-5">
                            <label class="block text-sm font-medium">Nombre del documento (PDF)</label>
                            <input type="text" class="w-full border rounded px-2 py-1" x-model="it.name"
                                @input="it.key = $store.reqs.slug(it.name)"
                                placeholder="Ej: RUT, Cámara de Comercio, Certificado Bancario">
                        </div>
                        <div class="flex gap-2 justify-end">
                            <button type="button" @click="$store.reqs.moveUp(idx)"
                                class="px-2 py-1 text-sm rounded bg-gray-200 hover:bg-gray-300">↑</button>
                            <button type="button" @click="$store.reqs.moveDown(idx)"
                                class="px-2 py-1 text-sm rounded bg-gray-200 hover:bg-gray-300">↓</button>
                            <button type="button" @click="$store.reqs.remove(idx)"
                                class="px-2 py-1 text-sm rounded bg-red-600 text-white hover:bg-red-700">Eliminar</button>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div class="mt-5 flex justify-end gap-3">
            <button type="button" class="px-4 py-2 rounded bg-gray-200 hover:bg-gray-300"
                @click="$store.reqs.closeModal()">Cancelar</button>
            <button type="button" class="px-4 py-2 rounded bg-green-600 text-white hover:bg-green-700"
                @click="$store.reqs.applyAndClose()">Listo</button>
        </div>
    </div>
</div>
{{-- Alpine store minimal --}}
<script>
function initReqsStore(inicial) {
  if (!window.Alpine) return;

  // Si ya existe el store (p.e. navegaste entre create/edit), reinit con los datos que llegan
  if (Alpine.store('reqs')) {
    Alpine.store('reqs').init(inicial);
    return;
  }

  Alpine.store('reqs', {
    open: false,
    items: [],

    init(data = []) {
      // soporta [{name,key}] o ["RUT","Camara de Comercio"]
      this.items = (data || []).map(x => {
        if (typeof x === 'string') return this.normalize({ name: x });
        return this.normalize(x);
      });
      // el x-effect del hidden reflejará el valor siempre
    },

    openModal() { this.open = true; },
    closeModal() { this.open = false; },
    applyAndClose() { this.open = false; },

    add() { this.items.push(this.normalize({ name: '' })); },
    remove(i) { this.items.splice(i, 1); },
    moveUp(i) { if (i > 0) [this.items[i-1], this.items[i]] = [this.items[i], this.items[i-1]]; },
    moveDown(i) { if (i < this.items.length - 1) [this.items[i+1], this.items[i]] = [this.items[i], this.items[i+1]]; },

    normalize(x) {
      const name = String(x?.name ?? '').trim();
      const key  = this.slug(x?.key ?? name);
      return { _id: crypto.randomUUID(), name, key };
    },

    slug(s) {
      return String(s || '')
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .toLowerCase().replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');
    },

    serialize() {
      // [{name,key}]
      const keys = new Set();
      const out = this.items
        .map((r, i) => {
          const name = String(r.name || '').trim();
          if (!name) return null;
          let key = this.slug(r.key || r.name || `req-${i+1}`);
          const base = key; let k = 1;
          while (keys.has(key)) key = `${base}-${k++}`;
          keys.add(key);
          return { name, key };
        })
        .filter(Boolean);
      return JSON.stringify(out);
    },
  });

  Alpine.store('reqs').init(inicial);
}
</script>


<link rel="stylesheet" href="https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css" />

@auth
<div class="min-h-screen flex flex-row bg-gray-200 mt-[50px]">
    <div class="flex flex-col w-56 bg-white rounded-r-3xl shadow-xl overflow-hidden">
        <ul class="flex flex-col py-4">

            {{-- 🔐 Opciones para Proveedor (rol_id = 3) --}}
            @if (Auth::user()->role_id === 3)
                <li>
                    <a href="{{ route('proponente.create') }}"
                        class="flex flex-row items-center h-12 transform hover:translate-x-2 transition-transform ease-in duration-200 text-gray-500 hover:text-gray-800">
                        <span class="inline-flex items-center justify-center h-12 w-12 text-lg text-black">
                            <img src="/image/editar.png" width="24" height="24" alt="Citas Icon">
                        </span>
                        <span class="text-sm font-medium text-black">Información Postulante</span>
                    </a>
                </li>
                <li>
                    <a href="#"
                        class="flex flex-row items-center h-12 transform hover:translate-x-2 transition-transform ease-in duration-200 text-gray-500 hover:text-gray-800">
                        <span class="inline-flex items-center justify-center h-12 w-12 text-lg text-black">
                            <img src="/image/busqueda.png" width="28" height="28" alt="Citas Icon">
                        </span>
                        <span class="text-sm font-medium text-black">Mis Postulaciones</span>
                    </a>
                </li>
            @endif

            {{-- 🛠 Opciones para Administrador (rol_id = 1) --}}
            @if (Auth::user()->role_id === 1)
                <li>
                    <a href="#"
                        class="flex flex-row items-center h-12 transform hover:translate-x-2 transition-transform ease-in duration-200 text-gray-500 hover:text-gray-800">
                        <span class="inline-flex items-center justify-center h-12 w-12 text-lg text-black">
                            <img src="/image/proveedor.png" width="24" height="24" alt="Usuarios Icon">
                        </span>
                        <span class="text-sm font-medium text-black">Proponentes</span>
                    </a>
                </li>
                <li>
                    <a href="#"
                        class="flex flex-row items-center h-12 transform hover:translate-x-2 transition-transform ease-in duration-200 text-gray-500 hover:text-gray-800">
                        <span class="inline-flex items-center justify-center h-12 w-12 text-lg text-black">
                            <img src="/image/proceso.png" width="24" height="24" alt="Proponentes Icon">
                        </span>
                        <span class="text-sm font-medium text-black">Listado de Proceso</span>
                    </a>
                </li>
                
            @endif

        </ul>
    </div>
</div>
@endauth


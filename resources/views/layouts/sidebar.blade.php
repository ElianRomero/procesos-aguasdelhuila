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
                        <a href="{{ route('postulaciones.index') }}"
                            class="flex flex-row items-center h-12 transform hover:translate-x-2 transition-transform ease-in duration-200 text-gray-500 hover:text-gray-800">
                            <span class="inline-flex items-center justify-center h-12 w-12 text-lg text-black">
                                <img src="/image/busqueda.png" width="28" height="28" alt="Citas Icon">
                            </span>
                            <span class="text-sm font-medium text-black">Postulaciones</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('mis.observaciones.index') }}"
                            class="flex flex-row items-center h-12 transform hover:translate-x-2 transition-transform ease-in duration-200 text-gray-500 hover:text-gray-800">
                            <span class="inline-flex items-center justify-center h-12 w-12 text-lg text-black">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                                    class="bi bi-card-checklist" viewBox="0 0 16 16">
                                    <path
                                        d="M14 3a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1zM2 2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2z" />
                                    <path
                                        d="M2 5.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5zm0 3a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5m3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5m3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5m3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5" />
                                </svg>
                            </span>
                            <span class="text-sm font-medium text-black">Observaciones</span>
                        </a>
                    </li>
                @endif

                {{-- 🛠 Opciones para Administrador (rol_id = 1) --}}
                @if (Auth::user()->role_id === 1)
                    <li>
                        <a href="{{ route('admin.postulaciones.index') }}"
                            class="flex flex-row items-center h-12 transform hover:translate-x-2 transition-transform ease-in duration-200 text-gray-500 hover:text-gray-800">
                            <span class="inline-flex items-center justify-center h-12 w-12 text-lg text-black">
                                <img src="/image/proveedor.png" width="24" height="24" alt="Usuarios Icon">
                            </span>
                            <span class="text-sm font-medium text-black">Proponentes</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('procesos.create') }}"
                            class="flex flex-row items-center h-12 transform hover:translate-x-2 transition-transform ease-in duration-200 text-gray-500 hover:text-gray-800">
                            <span class="inline-flex items-center justify-center h-12 w-12 text-lg text-black">
                                <img src="/image/proceso.png" width="24" height="24" alt="Proponentes Icon">
                            </span>
                            <span class="text-sm font-medium text-black">Listado de Proceso</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('parametros.index') }}"
                            class="flex flex-row items-center h-12 transform hover:translate-x-2 transition-transform ease-in duration-200 text-gray-500 hover:text-gray-800">
                            <span class="inline-flex items-center justify-center h-12 w-12 text-lg text-black">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                                    class="bi bi-card-checklist" viewBox="0 0 16 16">
                                    <path
                                        d="M14.5 3a.5.5 0 0 1 .5.5v9a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5zm-13-1A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h13a1.5 1.5 0 0 0 1.5-1.5v-9A1.5 1.5 0 0 0 14.5 2z" />
                                    <path
                                        d="M7 5.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5m-1.496-.854a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0l-.5-.5a.5.5 0 1 1 .708-.708l.146.147 1.146-1.147a.5.5 0 0 1 .708 0M7 9.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5m-1.496-.854a.5.5 0 0 1 0 .708l-1.5 1.5a.5.5 0 0 1-.708 0l-.5-.5a.5.5 0 0 1 .708-.708l.146.147 1.146-1.147a.5.5 0 0 1 .708 0" />
                                </svg>
                            </span>
                            <span class="text-sm font-medium text-black">Parámetros</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('admin.observaciones.index') }}"
                            class="flex flex-row items-center h-12 transform hover:translate-x-2 transition-transform ease-in duration-200 text-gray-500 hover:text-gray-800">
                            <span class="inline-flex items-center justify-center h-12 w-12 text-lg text-black">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor"
                                    class="bi bi-card-checklist" viewBox="0 0 16 16">
                                    <path
                                        d="M14 3a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1zM2 2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2z" />
                                    <path
                                        d="M2 5.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-2a.5.5 0 0 1-.5-.5zm0 3a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5m3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5m3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5m3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5" />
                                </svg>
                            </span>
                            <span class="text-sm font-medium text-black">Observaciones</span>
                        </a>
                    </li>
                @endif

            </ul>
        </div>
    </div>
@endauth

<footer class="mt-8 border-t border-slate-200 bg-white/90 backdrop-blur-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
            <!-- Marca -->
            <div>
                <div class="flex items-center gap-3">
                    <img src="{{ asset('image/logo.png') }}" alt="Aguas Del Huila" class="h-10 w-10 rounded-lg ring-1 ring-slate-200">
                    <div>
                        <p class="text-lg font-semibold tracking-tight">Aguas Del Huila</p>
                        <p class="text-sm text-slate-500">Gestión y pagos en línea</p>
                    </div>
                </div>
                <p class="mt-4 text-sm text-slate-600 leading-relaxed">
                    Plataforma para consulta e impresión de facturas, generación de enlaces de pago,
                    y confirmación de transacciones de forma segura.
                </p>

                @if (env('WOMPI_MODE') === 'sandbox')
                    <div class="mt-4 inline-flex items-center gap-2 rounded-full bg-amber-50 text-amber-700 px-3 py-1 text-xs font-medium ring-1 ring-amber-200">
                        <span class="inline-block h-2 w-2 rounded-full bg-amber-500"></span>
                        Modo pruebas (Sandbox)
                    </div>
                @endif
            </div>

            <!-- Enlaces -->
            <div>
                <p class="text-sm font-semibold text-slate-900">Navegación</p>
                <ul class="mt-4 space-y-2 text-sm">
                    <li><a href="{{ url('/') }}" class="hover:text-sky-700 transition-colors">Inicio</a></li>
                    <li><a href="{{ route('pago.search.form') }}" class="hover:text-sky-700 transition-colors">Buscar pago por REFPAGO</a></li>
                    <li><a href="{{ url('/politicas-privacidad') }}" class="hover:text-sky-700 transition-colors">Políticas de privacidad</a></li>
                    <li><a href="{{ url('/terminos') }}" class="hover:text-sky-700 transition-colors">Términos y condiciones</a></li>
                </ul>
            </div>

            <!-- Soporte -->
            <div>
                <p class="text-sm font-semibold text-slate-900">Soporte</p>
                <ul class="mt-4 space-y-2 text-sm">
                    <li class="flex items-center gap-2">
                        <!-- Mail icon -->
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M3 7l9 6 9-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                            <rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.5"/>
                        </svg>
                        <a href="mailto:soporte@aguasdelhuila.gov.co" class="hover:text-sky-700 transition-colors">
                            soporte@aguasdelhuila.gov.co
                        </a>
                    </li>
                    <li class="flex items-center gap-2">
                        <!-- Phone icon -->
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M2 5a3 3 0 0 1 3-3h1a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2h-.28a.72.72 0 0 0-.6 1.14 17 17 0 0 0 6.92 6.92.72.72 0 0 0 1.14-.6V17a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v1a3 3 0 0 1-3 3h0A18 18 0 0 1 2 5Z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                        </svg>
                        <a href="tel:+578000123456" class="hover:text-sky-700 transition-colors">
                            +57 800 012 3456
                        </a>
                    </li>
                    <li class="flex items-center gap-2">
                        <!-- Map pin icon -->
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 22s-7-5.33-7-12a7 7 0 1 1 14 0c0 6.67-7 12-7 12Z" stroke="currentColor" stroke-width="1.5"/>
                            <circle cx="12" cy="10" r="2.5" stroke="currentColor" stroke-width="1.5"/>
                        </svg>
                        <span>Huila, Colombia</span>
                    </li>
                </ul>
            </div>

            <!-- Redes -->
            <div>
                <p class="text-sm font-semibold text-slate-900">Síguenos</p>
                <div class="mt-4 flex items-center gap-3">
                    <a href="#" class="p-2 rounded-lg ring-1 ring-slate-200 hover:ring-sky-300 hover:text-sky-700 transition" aria-label="Facebook">
                        <!-- Facebook -->
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M13 11h3l.5-4H13V5.5c0-1 .3-1.5 1.8-1.5H17V0h-2.3C11.4 0 10 1.7 10 4.7V7H7v4h3v13h3V11z"/></svg>
                    </a>
                    <a href="#" class="p-2 rounded-lg ring-1 ring-slate-200 hover:ring-sky-300 hover:text-sky-700 transition" aria-label="X / Twitter">
                        <!-- X -->
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.5 2h3.1l-6.8 7.7L22 22h-6.7l-5.2-6.8L4 22H.9l7.4-8.4L2 2h6.8l4.7 6.2L17.5 2z"/></svg>
                    </a>
                    <a href="#" class="p-2 rounded-lg ring-1 ring-slate-200 hover:ring-sky-300 hover:text-sky-700 transition" aria-label="YouTube">
                        <!-- YouTube -->
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.5 12 3.5 12 3.5s-7.5 0-9.4.6A3 3 0 0 0 .5 6.2 31 31 0 0 0 0 12a31 31 0 0 0 .5 5.8 3 3 0 0 0 2.1 2.1c1.9.6 9.4.6 9.4.6s7.5 0 9.4-.6a3 3 0 0 0 2.1-2.1A31 31 0 0 0 24 12a31 31 0 0 0-.5-5.8ZM9.5 15.5v-7l6 3.5-6 3.5Z"/></svg>
                    </a>
                    <a href="https://wa.me/57XXXXXXXXXX" class="p-2 rounded-lg ring-1 ring-slate-200 hover:ring-sky-300 hover:text-sky-700 transition" aria-label="WhatsApp">
                        <!-- WhatsApp -->
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M.1 24 1.7 17a10.9 10.9 0 1 1 4 4L0 24ZM6.6 19a9 9 0 1 0-3.1-3.1L2.3 21l4.3-2Zm10.3-5.3c-.1-.1-1.2-.6-1.4-.7-.2-.1-.4-.1-.6.1-.2.3-.7.8-.8.9-.2.1-.3.1-.5 0-.1-.1-.6-.2-1.2-.7a9 9 0 0 1-1.6-1.4c-.2-.3-.3-.5 0-.8.1-.2.3-.3.4-.5.1-.2.2-.3.3-.5.1-.2 0-.3 0-.5l-.7-1.7c-.2-.4-.4-.4-.6-.4h-.5c-.2 0-.5.1-.7.3a3 3 0 0 0-1 2.3c0 1.3.9 2.5 1 2.6a10.7 10.7 0 0 0 4.1 3.4c.4.2.8.3 1.1.4.5.1 1 .1 1.4.1.4 0 1.2-.3 1.4-.8.2-.5.2-1 .1-1.1Z"/></svg>
                    </a>
                </div>

                <div class="mt-6">
                    <p class="text-xs text-slate-500">
                        Pagos procesados con Wompi. No almacenamos datos de tarjetas en nuestros servidores.
                    </p>
                </div>
            </div>
        </div>

        <div class="mt-10 border-t border-slate-200 pt-6 flex flex-col sm:flex-row items-center justify-between gap-4">
            <p class="text-xs text-slate-500">
                © {{ date('Y') }} Aguas Del Huila. Todos los derechos reservados.
            </p>

            <div class="flex items-center gap-3">
                <span class="text-xs text-slate-500">Seguridad:</span>
                <span class="inline-flex items-center text-xs rounded-full px-2.5 py-1 ring-1 ring-slate-200">
                    TLS 1.2+
                </span>
                <span class="inline-flex items-center text-xs rounded-full px-2.5 py-1 ring-1 ring-slate-200">
                    PCI-DSS (a través de Wompi)
                </span>
            </div>
        </div>
    </div>
</footer>

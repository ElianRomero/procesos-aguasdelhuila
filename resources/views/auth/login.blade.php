<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Hospital Garzón</title>
    <link rel="icon" href="{{ asset('image/logo.png') }}" type="image/png">
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /* Puedes colocar ajustes extra aquí si quieres */
    </style>
</head>

<body class="antialiased bg-white text-gray-900">
    <div class="bg-white font-family-karla">
        <div class="w-full flex flex-wrap">
            <div class="w-1/2 shadow-2xl">
                <img class="object-cover w-full h-screen hidden md:block"
                    src="https://www.huila.gov.co/info/huila_bco/media/pub/thumbs/thpub_700X400_13414.webp">
            </div>
            <div class="w-full md:w-1/2 flex flex-col">
                <div class="flex flex-col justify-center md:justify-start my-auto pt-8 md:pt-0 px-8 md:px-24 lg:px-32">
                    <div class="flex justify-center items-center">
                        <img src="{{ asset('image/logo.png') }}" width="300">
                    </div>
                    @if (session('status') === 'failed')
                        <div class="fixed z-10 inset-0 overflow-y-auto flex items-center justify-center">
                            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
                            <div
                                class="bg-white rounded-lg overflow-hidden shadow-xl transform transition-all sm:max-w-lg sm:w-full animate-scale">
                                <div class="px-4 py-6">
                                    <div class="flex justify-center">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80"
                                            fill="currentColor" color="Red" class="bi bi-x-circle animate-pulse"
                                            viewBox="0 0 16 16">
                                            <path
                                                d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16" />
                                            <path
                                                d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708" />
                                        </svg>
                                    </div>
                                    <div class="mt-6 text-center">
                                        <p class="text-xl text-gray-900"><b>Incorrect user or password. Please try
                                                again.</b></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <script>
                            setTimeout(function() {
                                document.querySelector('.fixed').remove();
                            }, 2000);
                        </script>
                    @endif
                   <form method="POST" action="{{ route('login') }}" class="flex flex-col pt-3 md:pt-8">
    @csrf

    <!-- Usuario -->
    <div class="flex flex-col pt-4">
        <label for="email" class="text-lg">Usuario</label>
        <x-text-input type="email" id="email" name="email" :value="old('email')" required
            autofocus autocomplete="username" placeholder="ejemplo@email.com" />
    </div>

    <!-- Contraseña -->
    <div class="flex flex-col pt-4">
        <label for="password" class="text-lg">Contraseña</label>
        <x-text-input type="password" id="password" name="password" placeholder="*******" />
    </div>

    <!-- Enlaces de Recuperar / Registro -->
    <div class="flex flex-col sm:flex-row justify-between items-center text-sm text-gray-600 mt-8 mb-6 gap-2 sm:gap-0">
        <a href="{{ route('password.request') }}"
            class="hover:text-blue-700 transition duration-150 ease-in-out underline">
            ¿Olvidaste tu contraseña?
        </a>
        <span class="hidden sm:inline mx-2">|</span>
        <a href="{{ route('register') }}"
            class="hover:text-blue-700 transition duration-150 ease-in-out underline font-semibold">
            ¿No tienes una cuenta? Regístrate
        </a>
    </div>

    <!-- Botón -->
    <x-primary-button class="bg-black text-white text-center font-bold justify-center">
        Ingresar
    </x-primary-button>
</form>

                </div>
            </div>
        </div>
    </div>
</body>

</html>

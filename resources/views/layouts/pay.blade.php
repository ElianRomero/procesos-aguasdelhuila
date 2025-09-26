<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Aguas Del Huila')</title>
    <link rel="icon" href="{{ asset('image/logo.png') }}" type="image/png">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>

<body class="font-sans antialiased bg-slate-50 text-slate-800">

    <div class="min-h-screen flex bg-gradient-to-br from-blue-50 via-sky-50 to-slate-50">


        <!-- Main column -->
        <div class="flex-1 flex flex-col min-w-0">

            @if (isset($header))
                <header class="bg-white/90 backdrop-blur-sm border-b border-slate-200">
                    <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endif

            <!-- Content -->
            <main class="flex-1">
                <div class="max-w-7xl mx-auto w-full py-8 px-4 sm:px-6 lg:px-8">
                    @yield('content')
                </div>
            </main>

            <!-- Footer -->
            @include('layouts.footer')
        </div>
    </div>

    @yield('scripts')
    @stack('scripts')
</body>

</html>

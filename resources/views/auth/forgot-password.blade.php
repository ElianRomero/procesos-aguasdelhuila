@extends('layouts.guest')
@section('content')
    <div class="flex items-center justify-center min-h-screen px-4 py-6 bg-blue-400">
    <div class="max-w-md w-full p-6 pt-16 bg-white rounded-lg shadow-lg">
        <x-auth-session-status class="mb-4" :status="session('status')" />
        <div class="flex justify-center items-center">
            <img src="{{ asset('image/logos.png') }}" width="300">
        </div>
        <h1 class="text-1xl font-semibold text-center text-gray-500 mt-8 mb-6">Recuperación de contraseña</h1>
        <p class="text-sm text-gray-600 text-center mt-8 mb-6">Introduce tu correo electrónico para restablecer tu contraseña</p>
        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <!-- Email Address -->
            <div>

                <x-input-label for="email" :value="__('Email')" />
                <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
            <div class="flex justify-center mt-4">
                <x-primary-button>
                    {{ __('Enviar') }}
                </x-primary-button>
            </div>
        </form>
        <div class="text-center mt-4">
            <p class="text-sm">Volver a <a href="{{ route('login') }}" class="text-cyan-600 underline">Iniciar sesión</a></p>
        </div>
        <p class="text-xs text-gray-600 text-center mt-8">&copy; Todos los derechos reservados 2025</p>
    </div>
</div>


@endsection
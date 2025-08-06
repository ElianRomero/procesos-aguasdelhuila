@extends('layouts.reset')

@section('content')
    <div class="flex items-center justify-center min-h-screen px-4 py-6 bg-gray-100">
        <div class="w-full max-w-md bg-white rounded-xl shadow-lg p-8 border border-gray-200">

            <!-- Logo -->
            <div class="flex justify-center mb-6">
                <img src="{{ asset('image/logo.png') }}" alt="Logo Clínica Alejandría" class="h-16 w-auto">
            </div>

            <h2 class="text-2xl font-semibold text-gray-800 text-center mb-6">Restablecer contraseña</h2>

            <form method="POST" action="{{ route('password.store') }}">
                @csrf

                <!-- Token -->
                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <!-- Email -->
                <div class="mb-4">
                    <x-input-label for="email" :value="__('Correo electrónico')" />
                    <x-text-input id="email" class="mt-1 block w-full" type="email" name="email"
                        :value="old('email', $request->email)" required autofocus autocomplete="username" />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <!-- Nueva contraseña -->
                <div class="mb-4">
                    <x-input-label for="password" :value="__('Nueva contraseña')" />
                    <x-text-input id="password" class="mt-1 block w-full" type="password" name="password"
                        required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <!-- Confirmar contraseña -->
                <div class="mb-6">
                    <x-input-label for="password_confirmation" :value="__('Confirmar contraseña')" />
                    <x-text-input id="password_confirmation" class="mt-1 block w-full" type="password"
                        name="password_confirmation" required autocomplete="new-password" />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>

                <!-- Botón -->
                <div>
                    <x-primary-button class="w-full justify-center">
                        {{ __('Restablecer contraseña') }}
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
@endsection

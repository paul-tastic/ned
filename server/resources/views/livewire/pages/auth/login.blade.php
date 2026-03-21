<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login">
        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-emerald-400 font-mono mb-1">email</label>
            <input wire:model="form.email" id="email"
                class="block w-full bg-gray-800 border border-gray-700 text-gray-200 rounded px-3 py-2 font-mono text-sm
                       focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 focus:outline-none
                       placeholder-gray-600"
                type="email" name="email" required autofocus autocomplete="username"
                placeholder="ned@yourdomain.com" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-1" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <label for="password" class="block text-sm font-medium text-emerald-400 font-mono mb-1">password</label>
            <input wire:model="form.password" id="password"
                class="block w-full bg-gray-800 border border-gray-700 text-gray-200 rounded px-3 py-2 font-mono text-sm
                       focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 focus:outline-none
                       placeholder-gray-600"
                type="password" name="password" required autocomplete="current-password"
                placeholder="********" />
            <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember" class="inline-flex items-center cursor-pointer">
                <input wire:model="form.remember" id="remember" type="checkbox"
                    class="rounded bg-gray-800 border-gray-700 text-emerald-500 shadow-sm focus:ring-emerald-500 focus:ring-offset-0 focus:ring-offset-gray-900"
                    name="remember">
                <span class="ms-2 text-sm text-gray-400 font-mono">--remember-me</span>
            </label>
        </div>

        <div class="flex items-center justify-between mt-6">
            @if (Route::has('password.request'))
                <a class="text-xs text-gray-500 hover:text-emerald-400 font-mono transition-colors" href="{{ route('password.request') }}" wire:navigate>
                    forgot credentials?
                </a>
            @endif

            <button type="submit"
                class="inline-flex items-center px-5 py-2 bg-emerald-600 hover:bg-emerald-500 border border-emerald-500
                       rounded font-mono text-sm text-white font-semibold tracking-wide
                       focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 focus:ring-offset-gray-900
                       transition ease-in-out duration-150">
                <svg class="w-4 h-4 mr-2 -ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                </svg>
                ssh in
            </button>
        </div>
    </form>
</div>

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-zinc-200 leading-tight">
                {{ __('Dashboard') }}
            </h2>
            <a href="{{ route('servers.create') }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 rounded-lg text-sm font-semibold transition-colors">
                Add Server
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <livewire:dashboard.server-list />
        </div>
    </div>
</x-app-layout>

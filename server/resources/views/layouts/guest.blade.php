<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'ned') }}</title>

        <!-- Favicon -->
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="icon" href="/favicon.ico" sizes="32x32">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=jetbrains-mono:400,500,600,700&family=inter:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        <script src="https://cdn.tailwindcss.com"></script>

        <style>
            .font-mono { font-family: 'JetBrains Mono', monospace; }
            .font-sans { font-family: 'Inter', sans-serif; }
            .blink { animation: blink 1.2s step-end infinite; }
            @keyframes blink { 50% { opacity: 0; } }
            .scan-line {
                background: linear-gradient(
                    transparent 0%,
                    rgba(16, 185, 129, 0.02) 50%,
                    transparent 100%
                );
                animation: scan 4s linear infinite;
            }
            @keyframes scan {
                0% { transform: translateY(-100%); }
                100% { transform: translateY(100%); }
            }
        </style>
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen flex flex-col items-center justify-center bg-gray-950 relative overflow-hidden">
            {{-- Subtle scan line effect --}}
            <div class="scan-line absolute inset-0 pointer-events-none h-full"></div>

            {{-- Faint grid pattern --}}
            <div class="absolute inset-0 opacity-[0.03]" style="background-image: url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2240%22 height=%2240%22><rect width=%2240%22 height=%2240%22 fill=%22none%22 stroke=%2210b981%22 stroke-width=%220.5%22/></svg>');"></div>

            <div class="relative z-10 w-full max-w-md px-6">
                {{-- Logo + branding --}}
                <div class="text-center mb-8">
                    <a href="/" wire:navigate class="inline-block">
                        <x-ned-logo class="w-16 h-16 mx-auto" />
                    </a>
                    <h1 class="mt-4 text-2xl font-bold text-white font-mono tracking-tight">ned</h1>
                    <p class="mt-1 text-sm text-emerald-500/70 font-mono">never-ending daemon v{{ config('ned.version', '0.3.0') }}</p>
                </div>

                {{-- Terminal-style card --}}
                <div class="bg-gray-900/80 border border-gray-800 rounded-lg shadow-2xl shadow-emerald-900/10 overflow-hidden backdrop-blur-sm">
                    {{-- Terminal title bar --}}
                    <div class="flex items-center gap-2 px-4 py-2.5 bg-gray-900 border-b border-gray-800">
                        <div class="w-2.5 h-2.5 rounded-full bg-red-500/80"></div>
                        <div class="w-2.5 h-2.5 rounded-full bg-yellow-500/80"></div>
                        <div class="w-2.5 h-2.5 rounded-full bg-emerald-500/80"></div>
                        <span class="ml-2 text-xs text-gray-500 font-mono">auth@ned ~ $</span>
                    </div>

                    {{-- Form content --}}
                    <div class="p-6">
                        {{ $slot }}
                    </div>
                </div>

                {{-- Footer --}}
                <p class="mt-6 text-center text-xs text-gray-600 font-mono">
                    <span class="text-emerald-600">$</span> excuse me, I believe you have my... server metrics<span class="blink">_</span>
                </p>
            </div>
        </div>
    </body>
</html>

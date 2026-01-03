<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Ned - Server Monitoring Dashboard</title>
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="icon" href="/favicon.ico" sizes="32x32">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="antialiased font-sans bg-zinc-900 text-white">
        <div class="min-h-screen flex flex-col">
            <!-- Nav -->
            <nav class="border-b border-zinc-800">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                    <a href="/" class="flex items-center gap-3">
                        <x-ned-logo class="h-10 w-10" />
                        <span class="text-xl font-bold">Ned</span>
                    </a>
                    <div class="flex items-center gap-4">
                        @auth
                            <a href="{{ route('dashboard') }}" class="text-zinc-300 hover:text-white transition">Dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="text-zinc-300 hover:text-white transition">Log in</a>
                            <a href="{{ route('register') }}" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 rounded-lg font-semibold transition">Get Started</a>
                        @endauth
                    </div>
                </div>
            </nav>

            <!-- Hero -->
            <main class="flex-1 flex items-center">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
                    <div class="text-center max-w-3xl mx-auto">
                        <h1 class="text-5xl sm:text-6xl font-bold mb-6">
                            <span class="text-emerald-500">Simple</span> Server Monitoring
                        </h1>
                        <p class="text-xl text-zinc-400 mb-8 italic">
                            "Excuse me, I believe you have my... server metrics."
                        </p>
                        <p class="text-lg text-zinc-300 mb-10">
                            Ned is a lightweight, open-source server monitoring tool.
                            A tiny bash agent runs on your servers and pushes metrics to your dashboard.
                            No bloat. No complexity. Just the essentials.
                        </p>

                        <div class="flex flex-col sm:flex-row gap-4 justify-center mb-16">
                            @auth
                                <a href="{{ route('dashboard') }}" class="px-8 py-3 bg-emerald-600 hover:bg-emerald-500 rounded-lg font-semibold text-lg transition">
                                    Go to Dashboard
                                </a>
                            @else
                                <a href="{{ route('register') }}" class="px-8 py-3 bg-emerald-600 hover:bg-emerald-500 rounded-lg font-semibold text-lg transition">
                                    Get Started Free
                                </a>
                                <a href="https://getneddy.com" class="px-8 py-3 bg-zinc-700 hover:bg-zinc-600 rounded-lg font-semibold text-lg transition">
                                    Learn More
                                </a>
                            @endauth
                        </div>

                        <!-- Feature highlights -->
                        <div class="grid sm:grid-cols-3 gap-8 text-left">
                            <div class="bg-zinc-800/50 rounded-xl p-6 border border-zinc-700">
                                <div class="w-12 h-12 bg-emerald-600/20 rounded-lg flex items-center justify-center mb-4">
                                    <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <h3 class="font-semibold text-lg mb-2">Zero Dependencies</h3>
                                <p class="text-zinc-400 text-sm">Pure bash agent. Works on any Linux server. No Docker, no agents to install.</p>
                            </div>

                            <div class="bg-zinc-800/50 rounded-xl p-6 border border-zinc-700">
                                <div class="w-12 h-12 bg-emerald-600/20 rounded-lg flex items-center justify-center mb-4">
                                    <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                                <h3 class="font-semibold text-lg mb-2">Push-Based</h3>
                                <p class="text-zinc-400 text-sm">Agents push metrics to you. No SSH required. Works through firewalls.</p>
                            </div>

                            <div class="bg-zinc-800/50 rounded-xl p-6 border border-zinc-700">
                                <div class="w-12 h-12 bg-emerald-600/20 rounded-lg flex items-center justify-center mb-4">
                                    <svg class="w-6 h-6 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                    </svg>
                                </div>
                                <h3 class="font-semibold text-lg mb-2">Self-Hosted</h3>
                                <p class="text-zinc-400 text-sm">Your data stays yours. Host on your own infrastructure.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <footer class="border-t border-zinc-800 py-8">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-zinc-500 text-sm">
                    <p>Ned - The Never-Ending Daemon. Milton's brother who actually monitors things.</p>
                    <p class="mt-2">
                        <a href="https://github.com/paul-tastic/ned" class="hover:text-emerald-500 transition">GitHub</a>
                        <span class="mx-2">|</span>
                        <a href="https://getneddy.com" class="hover:text-emerald-500 transition">Documentation</a>
                    </p>
                </div>
            </footer>
        </div>
    </body>
</html>

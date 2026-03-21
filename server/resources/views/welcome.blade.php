<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>ned - Server Monitoring Dashboard</title>
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="icon" href="/favicon.ico" sizes="32x32">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=jetbrains-mono:400,500,600,700&family=inter:400,500,600,700&display=swap" rel="stylesheet" />
        <script src="https://cdn.tailwindcss.com"></script>
        <style>
            .font-mono { font-family: 'JetBrains Mono', monospace; }
            .font-sans { font-family: 'Inter', sans-serif; }
            .blink { animation: blink 1.2s step-end infinite; }
            @keyframes blink { 50% { opacity: 0; } }
        </style>
    </head>
    <body class="antialiased font-sans bg-gray-950 text-white">
        <div class="min-h-screen flex flex-col">
            {{-- Nav --}}
            <nav class="border-b border-gray-800/60">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
                    <a href="/" class="flex items-center gap-3">
                        <x-ned-logo class="h-9 w-9" />
                        <span class="text-lg font-bold font-mono">ned</span>
                    </a>
                    <div class="flex items-center gap-4">
                        @auth
                            <a href="{{ route('dashboard') }}" class="text-gray-300 hover:text-white transition font-mono text-sm">dashboard</a>
                        @else
                            <a href="{{ route('login') }}" class="text-gray-400 hover:text-emerald-400 transition font-mono text-sm">log in</a>
                        @endauth
                    </div>
                </div>
            </nav>

            {{-- Content --}}
            <main class="flex-1">
                <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-16 sm:py-24">

                    {{-- Logos --}}
                    <div class="flex items-center justify-center gap-4 mb-12">
                        <svg class="h-10 w-auto opacity-30" viewBox="0 0 70 47" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M23.3969 0.398855V4.58248C23.3969 4.74481 23.2986 4.89024 23.1461 4.9545L17.9806 7.06491C17.6959 7.1799 17.6417 7.5587 17.8824 7.74809L26.8033 14.8673C26.9084 14.9519 26.9626 15.0804 26.9524 15.2157L26.5932 19.3824C26.566 19.6969 26.2034 19.8559 25.9526 19.6665L12.3135 9.38163C12.1983 9.2937 12.0458 9.27679 11.9136 9.33428L4.3857 12.5472C4.24335 12.6081 4.08065 12.5811 3.96541 12.483L1.17931 10.0885C0.728521 9.70293 0.84715 8.97917 1.39624 8.75595L22.8445 0.030209C23.1088 -0.0780173 23.3969 0.118143 23.3969 0.402237V0.398855Z" fill="#10b981"/>
                            <path d="M7.94428 13.5109L11.7303 11.8571C11.8591 11.803 12.0048 11.8199 12.1133 11.9078L25.1795 22.1724C25.2845 22.2536 25.3354 22.3821 25.3218 22.514L24.0169 33.0559C24 33.1844 24.0542 33.3129 24.1559 33.3941L33.5513 40.9091C33.6394 40.9801 33.6937 41.0883 33.6937 41.2033V46.2122C33.6937 46.5301 33.3242 46.7026 33.0802 46.503L19.3565 35.3963C19.1904 35.2644 19.1056 35.0547 19.1294 34.845L20.2682 24.3471C20.2919 24.134 20.2038 23.9243 20.0343 23.7924L7.85955 14.1434C7.63585 13.9675 7.6833 13.6192 7.94428 13.5042V13.5109Z" fill="#6b7280"/>
                            <path d="M46.6046 0.398855V4.58248C46.6046 4.74481 46.7029 4.89024 46.8554 4.9545L52.0208 7.06491C52.3056 7.1799 52.3598 7.5587 52.1191 7.74809L43.1982 14.8673C43.0931 14.9519 43.0389 15.0804 43.0491 15.2157L43.4083 19.3824C43.4355 19.6969 43.7981 19.8559 44.0489 19.6665L57.688 9.38163C57.8032 9.2937 57.9557 9.27679 58.0879 9.33428L65.6158 12.5472C65.7582 12.6081 65.9208 12.5811 66.0361 12.483L68.8222 10.0885C69.273 9.70293 69.1544 8.97917 68.6053 8.75595L47.157 0.030209C46.8927 -0.0780173 46.6046 0.118143 46.6046 0.402237V0.398855Z" fill="#10b981"/>
                            <path d="M62.0565 13.5109L58.2705 11.8571C58.1418 11.803 57.996 11.8199 57.8875 11.9078L44.8213 22.1724C44.7163 22.2536 44.6654 22.3821 44.679 22.514L45.9839 33.0559C46.0009 33.1844 45.9466 33.3129 45.8449 33.3941L36.4495 40.9091C36.3614 40.9801 36.3071 41.0883 36.3071 41.2033V46.2122C36.3071 46.5301 36.6766 46.7026 36.9206 46.503L50.6444 35.3963C50.8104 35.2644 50.8952 35.0547 50.8715 34.845L49.7326 24.3471C49.7089 24.134 49.797 23.9243 49.9665 23.7924L62.1413 14.1434C62.365 13.9675 62.3175 13.6192 62.0565 13.5042V13.5109Z" fill="#6b7280"/>
                        </svg>
                        <span class="text-gray-700 text-xl font-light">/</span>
                        <x-ned-logo class="h-10 w-10" />
                    </div>

                    {{-- The conversation --}}
                    <div class="space-y-8">

                        {{-- Hunter --}}
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-amber-900/40 border border-amber-800/50 flex items-center justify-center text-sm">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div>
                                <p class="text-xs text-amber-600 font-mono mb-1">hunter</p>
                                <p class="text-gray-300 text-lg">Alright, so I've got all these servers running my scan processing pipeline. Trail cams, 3D reconstruction, the whole deal. How do I know when something goes sideways?</p>
                            </div>
                        </div>

                        {{-- Ned --}}
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-emerald-900/40 border border-emerald-800/50 flex items-center justify-center">
                                <x-ned-logo class="w-5 h-5" />
                            </div>
                            <div>
                                <p class="text-xs text-emerald-600 font-mono mb-1">ned</p>
                                <p class="text-gray-300 text-lg">Well, um... yeah, so I just sit here and watch your servers. You run one command and I'll, uh, keep an eye on everything. CPU, memory, disk, all of it. Every five minutes. Quietly.</p>
                            </div>
                        </div>

                        {{-- Terminal --}}
                        <div class="ml-12 bg-gray-900/80 border border-gray-800 rounded-lg overflow-hidden">
                            <div class="flex items-center gap-2 px-4 py-2 bg-gray-900 border-b border-gray-800">
                                <div class="w-2 h-2 rounded-full bg-red-500/70"></div>
                                <div class="w-2 h-2 rounded-full bg-yellow-500/70"></div>
                                <div class="w-2 h-2 rounded-full bg-emerald-500/70"></div>
                                <span class="ml-2 text-[11px] text-gray-600 font-mono">your-server $</span>
                            </div>
                            <div class="p-4 font-mono text-xs sm:text-sm space-y-1">
                                <div><span class="text-emerald-400">$</span> <span class="text-gray-300">curl -fsSL https://your-ned/install.sh | bash</span></div>
                                <div class="text-gray-600">Installing... done. Cron set. First metrics sent.</div>
                                <div class="text-gray-600"># That's it. One line. Go back to hunting.</div>
                            </div>
                        </div>

                        {{-- Hunter --}}
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-amber-900/40 border border-amber-800/50 flex items-center justify-center text-sm">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div>
                                <p class="text-xs text-amber-600 font-mono mb-1">hunter</p>
                                <p class="text-gray-300 text-lg">One command? No Docker? No config files? I spend enough time setting up trail cams, I don't need another thing to babysit.</p>
                            </div>
                        </div>

                        {{-- Ned --}}
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-emerald-900/40 border border-emerald-800/50 flex items-center justify-center">
                                <x-ned-logo class="w-5 h-5" />
                            </div>
                            <div>
                                <p class="text-xs text-emerald-600 font-mono mb-1">ned</p>
                                <p class="text-gray-300 text-lg">No, no, you don't need to babysit me. I'm just a bash script and a cron job. I don't even need to be installed, really. I just... run. And I report back. That's what I do. I report things.</p>
                                <p class="text-gray-500 text-sm mt-2 italic">*adjusts glasses, clutches red stapler*</p>
                            </div>
                        </div>

                        {{-- Hunter --}}
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-amber-900/40 border border-amber-800/50 flex items-center justify-center text-sm">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div>
                                <p class="text-xs text-amber-600 font-mono mb-1">hunter</p>
                                <p class="text-gray-300 text-lg">OK, but what exactly are you watching? I need to know when my GPU workers go down or when the disk fills up with scan data.</p>
                            </div>
                        </div>

                        {{-- Ned --}}
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-emerald-900/40 border border-emerald-800/50 flex items-center justify-center">
                                <x-ned-logo class="w-5 h-5" />
                            </div>
                            <div>
                                <p class="text-xs text-emerald-600 font-mono mb-1">ned</p>
                                <p class="text-gray-300 text-lg">Oh, I watch everything. I'm very thorough. They moved my desk to the basement but I still watch everything.</p>
                            </div>
                        </div>

                        {{-- What ned watches --}}
                        <div class="ml-12 bg-gray-900/60 border border-gray-800 rounded-lg p-5">
                            <div class="grid grid-cols-2 gap-2 font-mono text-sm">
                                <div class="flex items-center gap-2"><span class="text-emerald-500">+</span> <span class="text-gray-400">CPU load averages</span></div>
                                <div class="flex items-center gap-2"><span class="text-emerald-500">+</span> <span class="text-gray-400">Memory & swap</span></div>
                                <div class="flex items-center gap-2"><span class="text-emerald-500">+</span> <span class="text-gray-400">Disk per mount</span></div>
                                <div class="flex items-center gap-2"><span class="text-emerald-500">+</span> <span class="text-gray-400">Network I/O</span></div>
                                <div class="flex items-center gap-2"><span class="text-emerald-500">+</span> <span class="text-gray-400">Running services</span></div>
                                <div class="flex items-center gap-2"><span class="text-emerald-500">+</span> <span class="text-gray-400">SSH brute force</span></div>
                                <div class="flex items-center gap-2"><span class="text-emerald-500">+</span> <span class="text-gray-400">fail2ban bans</span></div>
                                <div class="flex items-center gap-2"><span class="text-emerald-500">+</span> <span class="text-gray-400">Uptime & distro</span></div>
                            </div>
                        </div>

                        {{-- Hunter --}}
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-amber-900/40 border border-amber-800/50 flex items-center justify-center text-sm">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div>
                                <p class="text-xs text-amber-600 font-mono mb-1">hunter</p>
                                <p class="text-gray-300 text-lg">What's this gonna cost me? I'm already paying AWS enough to fund a small country.</p>
                            </div>
                        </div>

                        {{-- Ned --}}
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-emerald-900/40 border border-emerald-800/50 flex items-center justify-center">
                                <x-ned-logo class="w-5 h-5" />
                            </div>
                            <div>
                                <p class="text-xs text-emerald-600 font-mono mb-1">ned</p>
                                <p class="text-gray-300 text-lg">It's free. Open source. MIT license. You host it yourself on, um, whatever you want. A $4 server works fine. I don't eat much. Just... just don't take my stapler.</p>
                            </div>
                        </div>

                        {{-- Hunter --}}
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-amber-900/40 border border-amber-800/50 flex items-center justify-center text-sm">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0zm6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div>
                                <p class="text-xs text-amber-600 font-mono mb-1">hunter</p>
                                <p class="text-gray-300 text-lg">Alright Ned, you've got yourself a deal. Where do I start?</p>
                            </div>
                        </div>

                        {{-- Ned --}}
                        <div class="flex gap-4">
                            <div class="flex-shrink-0 w-8 h-8 rounded-full bg-emerald-900/40 border border-emerald-800/50 flex items-center justify-center">
                                <x-ned-logo class="w-5 h-5" />
                            </div>
                            <div>
                                <p class="text-xs text-emerald-600 font-mono mb-1">ned</p>
                                <p class="text-gray-300 text-lg">I, uh... I put the button right here for you.</p>
                            </div>
                        </div>

                        {{-- CTA --}}
                        <div class="flex flex-col sm:flex-row gap-4 justify-center pt-4">
                            @auth
                                <a href="{{ route('dashboard') }}" class="px-8 py-3 bg-emerald-600 hover:bg-emerald-500 rounded font-mono font-semibold transition text-center">
                                    $ open dashboard
                                </a>
                            @else
                                <a href="{{ route('login') }}" class="px-8 py-3 bg-emerald-600 hover:bg-emerald-500 rounded font-mono font-semibold transition inline-flex items-center justify-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" /></svg>
                                    ssh in
                                </a>
                                <a href="https://github.com/paul-tastic/ned" class="px-8 py-3 bg-gray-800 hover:bg-gray-700 border border-gray-700 rounded font-mono font-semibold transition text-center">
                                    view source
                                </a>
                            @endauth
                        </div>
                    </div>
                </div>
            </main>

            {{-- Footer --}}
            <footer class="border-t border-gray-800/60 py-8">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row justify-between items-center gap-4 text-gray-600 text-xs font-mono">
                    <div class="flex items-center gap-3">
                        <x-ned-logo class="h-5 w-5" />
                        <span>ned <span class="text-gray-700">// never-ending daemon</span></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-gray-700">watching servers for</span>
                        <svg class="h-3.5 w-auto" viewBox="0 0 70 47" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M23.3969 0.398855V4.58248C23.3969 4.74481 23.2986 4.89024 23.1461 4.9545L17.9806 7.06491C17.6959 7.1799 17.6417 7.5587 17.8824 7.74809L26.8033 14.8673C26.9084 14.9519 26.9626 15.0804 26.9524 15.2157L26.5932 19.3824C26.566 19.6969 26.2034 19.8559 25.9526 19.6665L12.3135 9.38163C12.1983 9.2937 12.0458 9.27679 11.9136 9.33428L4.3857 12.5472C4.24335 12.6081 4.08065 12.5811 3.96541 12.483L1.17931 10.0885C0.728521 9.70293 0.84715 8.97917 1.39624 8.75595L22.8445 0.030209C23.1088 -0.0780173 23.3969 0.118143 23.3969 0.402237V0.398855Z" fill="#4b5563"/>
                            <path d="M7.94428 13.5109L11.7303 11.8571C11.8591 11.803 12.0048 11.8199 12.1133 11.9078L25.1795 22.1724C25.2845 22.2536 25.3354 22.3821 25.3218 22.514L24.0169 33.0559C24 33.1844 24.0542 33.3129 24.1559 33.3941L33.5513 40.9091C33.6394 40.9801 33.6937 41.0883 33.6937 41.2033V46.2122C33.6937 46.5301 33.3242 46.7026 33.0802 46.503L19.3565 35.3963C19.1904 35.2644 19.1056 35.0547 19.1294 34.845L20.2682 24.3471C20.2919 24.134 20.2038 23.9243 20.0343 23.7924L7.85955 14.1434C7.63585 13.9675 7.6833 13.6192 7.94428 13.5042V13.5109Z" fill="#374151"/>
                            <path d="M46.6046 0.398855V4.58248C46.6046 4.74481 46.7029 4.89024 46.8554 4.9545L52.0208 7.06491C52.3056 7.1799 52.3598 7.5587 52.1191 7.74809L43.1982 14.8673C43.0931 14.9519 43.0389 15.0804 43.0491 15.2157L43.4083 19.3824C43.4355 19.6969 43.7981 19.8559 44.0489 19.6665L57.688 9.38163C57.8032 9.2937 57.9557 9.27679 58.0879 9.33428L65.6158 12.5472C65.7582 12.6081 65.9208 12.5811 66.0361 12.483L68.8222 10.0885C69.273 9.70293 69.1544 8.97917 68.6053 8.75595L47.157 0.030209C46.8927 -0.0780173 46.6046 0.118143 46.6046 0.402237V0.398855Z" fill="#4b5563"/>
                            <path d="M62.0565 13.5109L58.2705 11.8571C58.1418 11.803 57.996 11.8199 57.8875 11.9078L44.8213 22.1724C44.7163 22.2536 44.6654 22.3821 44.679 22.514L45.9839 33.0559C46.0009 33.1844 45.9466 33.3129 45.8449 33.3941L36.4495 40.9091C36.3614 40.9801 36.3071 41.0883 36.3071 41.2033V46.2122C36.3071 46.5301 36.6766 46.7026 36.9206 46.503L50.6444 35.3963C50.8104 35.2644 50.8952 35.0547 50.8715 34.845L49.7326 24.3471C49.7089 24.134 49.797 23.9243 49.9665 23.7924L62.1413 14.1434C62.365 13.9675 62.3175 13.6192 62.0565 13.5042V13.5109Z" fill="#374151"/>
                        </svg>
                        <span class="text-gray-700">trophyscan</span>
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>

<div class="max-w-2xl mx-auto">
    @if($plainToken)
        <!-- Token Display (shown once after creation) -->
        <div class="bg-zinc-800 rounded-lg p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-emerald-600 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-lg">Server Created!</h3>
                    <p class="text-zinc-400 text-sm">{{ $server->name }}</p>
                </div>
            </div>

            <div class="bg-red-900/20 border border-red-800 rounded-lg p-4 mb-6">
                <p class="text-red-400 text-sm font-semibold mb-2">Save this token now - you won't see it again!</p>
                <div
                    x-data="{ copied: false }"
                    class="flex items-center gap-2 bg-zinc-900 p-3 rounded"
                >
                    <code class="flex-1 text-sm font-mono break-all text-zinc-300">{{ $plainToken }}</code>
                    <button
                        @click="navigator.clipboard.writeText('{{ $plainToken }}'); copied = true; setTimeout(() => copied = false, 2000)"
                        class="flex-shrink-0 p-2 hover:bg-zinc-800 rounded transition-colors"
                        title="Copy token"
                    >
                        <svg x-show="!copied" class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                        </svg>
                        <svg x-show="copied" x-cloak class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="bg-zinc-900 rounded-lg p-4 mb-6">
                <p class="text-zinc-400 text-sm mb-2">Install the agent on your server:</p>
                <code class="block bg-zinc-950 p-3 rounded text-sm font-mono text-emerald-400 overflow-x-auto">curl -fsSL https://getneddy.com/install.sh | bash -s -- --token {{ $plainToken }} --api {{ config('app.url') }}</code>
            </div>

            <div class="flex gap-4">
                <a href="{{ route('servers.show', $server) }}" class="flex-1 px-4 py-2 bg-emerald-600 hover:bg-emerald-500 rounded-lg text-center font-semibold transition-colors">
                    View Server
                </a>
                <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-zinc-700 hover:bg-zinc-600 rounded-lg font-semibold transition-colors">
                    Dashboard
                </a>
            </div>
        </div>
    @else
        <!-- Create Form -->
        <div class="bg-zinc-800 rounded-lg p-6">
            <h2 class="text-xl font-semibold mb-6">Add New Server</h2>

            <form wire:submit="create" class="space-y-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-zinc-300 mb-2">Server Name</label>
                    <input
                        type="text"
                        id="name"
                        wire:model="name"
                        class="w-full px-4 py-2 bg-zinc-900 border border-zinc-700 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent text-white placeholder-zinc-500"
                        placeholder="Production Web Server"
                        required
                    >
                    @error('name') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label for="hostname" class="block text-sm font-medium text-zinc-300 mb-2">Hostname (optional)</label>
                    <input
                        type="text"
                        id="hostname"
                        wire:model="hostname"
                        class="w-full px-4 py-2 bg-zinc-900 border border-zinc-700 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-transparent font-mono text-white placeholder-zinc-500"
                        placeholder="web-01.example.com"
                    >
                    @error('hostname') <span class="text-red-400 text-sm mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="flex gap-4">
                    <button type="submit" class="flex-1 px-4 py-2 bg-emerald-600 hover:bg-emerald-500 rounded-lg font-semibold transition-colors">
                        Create Server
                    </button>
                    <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-zinc-700 hover:bg-zinc-600 rounded-lg font-semibold transition-colors">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    @endif
</div>

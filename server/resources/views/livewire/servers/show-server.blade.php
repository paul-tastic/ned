<div>
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
        <div class="flex items-center gap-4">
            <a href="{{ route('dashboard') }}" class="text-zinc-400 hover:text-white transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div class="flex items-center gap-3">
                <div class="w-4 h-4 rounded-full
                    @if($server->status === 'online') bg-emerald-400
                    @elseif($server->status === 'warning') bg-amber-400
                    @elseif($server->status === 'critical') bg-red-400 animate-pulse
                    @else bg-zinc-500
                    @endif
                "></div>
                <h1 class="text-2xl font-bold">{{ $server->name }}</h1>
            </div>
            @if($server->hostname)
                <span class="text-zinc-400 font-mono text-sm">{{ $server->hostname }}</span>
            @endif
        </div>
        <div class="flex items-center gap-2">
            <button wire:click="regenerateToken" class="px-4 py-2 bg-zinc-700 hover:bg-zinc-600 rounded-lg text-sm font-semibold transition-colors">
                Regenerate Token
            </button>
            <button wire:click="$set('showDeleteModal', true)" class="px-4 py-2 bg-red-600/20 hover:bg-red-600/30 text-red-400 rounded-lg text-sm font-semibold transition-colors">
                Delete
            </button>
        </div>
    </div>

    <!-- New Token Alert -->
    @if($newToken)
        <div class="bg-red-900/20 border border-red-800 rounded-lg p-4 mb-6">
            <p class="text-red-400 text-sm font-semibold mb-2">New token generated - save it now!</p>
            <code class="block bg-zinc-900 p-3 rounded text-sm font-mono break-all text-zinc-300">{{ $newToken }}</code>
        </div>
    @endif

    <!-- Active Issues -->
    @if($latestMetric && $server->status !== 'online')
        <div class="rounded-lg p-4 mb-6 @if($server->status === 'critical') bg-red-900/20 border border-red-800 @else bg-amber-900/20 border border-amber-800 @endif">
            <h3 class="font-semibold mb-3 flex items-center gap-2 @if($server->status === 'critical') text-red-400 @else text-amber-400 @endif">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
                {{ ucfirst($server->status) }} Status
            </h3>
            <ul class="space-y-1 text-sm">
                @if($latestMetric->memory_percent >= 95)
                    <li class="text-red-400">• Memory critical: {{ number_format($latestMetric->memory_percent, 0) }}% used</li>
                @elseif($latestMetric->memory_percent >= 80)
                    <li class="text-amber-400">• Memory high: {{ number_format($latestMetric->memory_percent, 0) }}% used</li>
                @endif
                @if($latestMetric->max_disk_percent >= 95)
                    <li class="text-red-400">• Disk critical: {{ number_format($latestMetric->max_disk_percent, 0) }}% full</li>
                @elseif($latestMetric->max_disk_percent >= 80)
                    <li class="text-amber-400">• Disk high: {{ number_format($latestMetric->max_disk_percent, 0) }}% full</li>
                @endif
                @if($latestMetric->normalized_load >= 2)
                    <li class="text-red-400">• CPU overloaded: {{ number_format($latestMetric->normalized_load * 100, 0) }}% ({{ $latestMetric->load_1m }} load on {{ $latestMetric->cpu_cores }} cores)</li>
                @elseif($latestMetric->normalized_load >= 1.5)
                    <li class="text-amber-400">• CPU load high: {{ number_format($latestMetric->normalized_load * 100, 0) }}% ({{ $latestMetric->load_1m }} load on {{ $latestMetric->cpu_cores }} cores)</li>
                @endif
                @if($latestMetric->failed_services_count > 0)
                    <li class="text-amber-400">• {{ $latestMetric->failed_services_count }} service(s) not running</li>
                @endif
            </ul>
        </div>
    @endif

    <!-- Stats Grid -->
    @if($latestMetric)
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-zinc-800 rounded-lg p-4">
                <div class="text-zinc-400 text-sm mb-1">CPU Load</div>
                <div class="text-2xl font-bold @if($latestMetric->normalized_load >= 1.5) text-amber-400 @elseif($latestMetric->normalized_load >= 2) text-red-400 @endif">
                    {{ number_format($latestMetric->normalized_load * 100, 0) }}%
                </div>
                <div class="text-zinc-500 text-xs">{{ $latestMetric->load_1m }} / {{ $latestMetric->cpu_cores }} cores</div>
            </div>
            <div class="bg-zinc-800 rounded-lg p-4">
                <div class="text-zinc-400 text-sm mb-1">Memory</div>
                <div class="text-2xl font-bold @if($latestMetric->memory_percent >= 80) text-amber-400 @elseif($latestMetric->memory_percent >= 95) text-red-400 @endif">
                    {{ number_format($latestMetric->memory_percent, 0) }}%
                </div>
                <div class="text-zinc-500 text-xs">{{ number_format($latestMetric->memory_used / 1024, 1) }}GB / {{ number_format($latestMetric->memory_total / 1024, 1) }}GB</div>
            </div>
            <div class="bg-zinc-800 rounded-lg p-4">
                <div class="text-zinc-400 text-sm mb-1">Disk</div>
                <div class="text-2xl font-bold @if($latestMetric->max_disk_percent >= 80) text-amber-400 @elseif($latestMetric->max_disk_percent >= 95) text-red-400 @endif">
                    {{ number_format($latestMetric->max_disk_percent, 0) }}%
                </div>
                <div class="text-zinc-500 text-xs">Highest partition</div>
            </div>
            <div class="bg-zinc-800 rounded-lg p-4">
                <div class="text-zinc-400 text-sm mb-1">Uptime</div>
                <div class="text-2xl font-bold">
                    {{ floor($latestMetric->uptime / 86400) }}d
                </div>
                <div class="text-zinc-500 text-xs">{{ gmdate('H:i:s', $latestMetric->uptime % 86400) }}</div>
            </div>
        </div>

        <!-- Disks -->
        @if($latestMetric->disks)
            <div class="bg-zinc-800 rounded-lg p-6 mb-8">
                <h3 class="font-semibold mb-4">Disk Usage</h3>
                <div class="space-y-4">
                    @foreach($latestMetric->disks as $disk)
                        <div>
                            <div class="flex justify-between text-sm mb-1">
                                <span class="font-mono text-zinc-400">{{ $disk['mount'] }}</span>
                                <span class="@if($disk['percent'] >= 80) text-amber-400 @elseif($disk['percent'] >= 95) text-red-400 @endif">
                                    {{ $disk['percent'] }}% ({{ number_format($disk['used_mb'] / 1024, 1) }}GB / {{ number_format($disk['total_mb'] / 1024, 1) }}GB)
                                </span>
                            </div>
                            <div class="h-2 bg-zinc-700 rounded-full overflow-hidden">
                                <div class="h-full rounded-full transition-all
                                    @if($disk['percent'] >= 95) bg-red-500
                                    @elseif($disk['percent'] >= 80) bg-amber-500
                                    @else bg-emerald-500
                                    @endif
                                " style="width: {{ $disk['percent'] }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Services -->
        @if($latestMetric->services)
            <div class="bg-zinc-800 rounded-lg p-6 mb-8">
                <h3 class="font-semibold mb-4">Services</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                    @foreach($latestMetric->services as $service)
                        <div class="flex items-center gap-2 bg-zinc-900 rounded-lg px-3 py-2">
                            <div class="w-2 h-2 rounded-full {{ $service['status'] === 'running' ? 'bg-emerald-400' : 'bg-red-400' }}"></div>
                            <span class="text-sm font-mono">{{ $service['name'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Security -->
        @if($latestMetric->security)
            <div class="bg-zinc-800 rounded-lg p-6 mb-8">
                <h3 class="font-semibold mb-4">Security</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @if(isset($latestMetric->security['fail2ban']))
                        <div class="bg-zinc-900 rounded-lg p-4">
                            <div class="text-zinc-400 text-sm mb-1">Fail2ban Jails</div>
                            <div class="text-xl font-bold">{{ $latestMetric->security['fail2ban']['jails'] ?? 0 }}</div>
                        </div>
                        <div class="bg-zinc-900 rounded-lg p-4">
                            <div class="text-zinc-400 text-sm mb-1">Banned IPs</div>
                            <div class="text-xl font-bold">{{ $latestMetric->security['fail2ban']['banned'] ?? 0 }}</div>
                        </div>
                    @endif
                    @if(isset($latestMetric->security['ssh_failed_24h']))
                        <div class="bg-zinc-900 rounded-lg p-4">
                            <div class="text-zinc-400 text-sm mb-1">SSH Failed (24h)</div>
                            <div class="text-xl font-bold @if($latestMetric->security['ssh_failed_24h'] > 100) text-amber-400 @endif">
                                {{ number_format($latestMetric->security['ssh_failed_24h']) }}
                            </div>
                        </div>
                    @endif
                    @if(isset($latestMetric->security['last_attack']) && $latestMetric->security['last_attack'])
                        <div class="bg-zinc-900 rounded-lg p-4">
                            <div class="text-zinc-400 text-sm mb-1">Last Attack</div>
                            <div class="text-sm font-mono text-zinc-300">{{ $latestMetric->security['last_attack'] }}</div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    @else
        <div class="bg-zinc-800 rounded-lg p-12 text-center">
            <div class="text-4xl mb-4">⏳</div>
            <h3 class="text-xl font-semibold mb-2">Waiting for metrics</h3>
            <p class="text-zinc-400 mb-6">Install the agent on your server to start receiving data.</p>
            <code class="block bg-zinc-900 p-3 rounded text-sm font-mono text-emerald-400 max-w-xl mx-auto overflow-x-auto">
                curl -fsSL https://getneddy.com/install.sh | bash -s -- --token YOUR_TOKEN --api {{ config('app.url') }}
            </code>
        </div>
    @endif

    <!-- Last Seen -->
    <div class="text-center text-zinc-500 text-sm">
        @if($server->last_seen_at)
            Last seen {{ $server->last_seen_at->diffForHumans() }}
        @else
            Never connected
        @endif
    </div>

    <!-- Delete Modal -->
    @if($showDeleteModal)
        <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
            <div class="bg-zinc-800 rounded-lg p-6 max-w-md w-full mx-4">
                <h3 class="text-xl font-semibold mb-4">Delete Server?</h3>
                <p class="text-zinc-400 mb-6">This will permanently delete <strong>{{ $server->name }}</strong> and all its metrics. This cannot be undone.</p>
                <div class="flex gap-4">
                    <button wire:click="delete" class="flex-1 px-4 py-2 bg-red-600 hover:bg-red-500 rounded-lg font-semibold transition-colors">
                        Delete
                    </button>
                    <button wire:click="$set('showDeleteModal', false)" class="px-4 py-2 bg-zinc-700 hover:bg-zinc-600 rounded-lg font-semibold transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

<div wire:poll.30s>
    <!-- Stats Overview -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        <div class="bg-zinc-800 rounded-lg p-4">
            <div class="text-2xl font-bold">{{ $stats['total'] }}</div>
            <div class="text-zinc-400 text-sm">Total Servers</div>
        </div>
        <div class="bg-zinc-800 rounded-lg p-4">
            <div class="text-2xl font-bold text-emerald-400">{{ $stats['online'] }}</div>
            <div class="text-zinc-400 text-sm">Online</div>
        </div>
        <div class="bg-zinc-800 rounded-lg p-4">
            <div class="text-2xl font-bold text-amber-400">{{ $stats['warning'] }}</div>
            <div class="text-zinc-400 text-sm">Warning</div>
        </div>
        <div class="bg-zinc-800 rounded-lg p-4">
            <div class="text-2xl font-bold text-red-400">{{ $stats['critical'] }}</div>
            <div class="text-zinc-400 text-sm">Critical</div>
        </div>
        <div class="bg-zinc-800 rounded-lg p-4">
            <div class="text-2xl font-bold text-zinc-500">{{ $stats['offline'] }}</div>
            <div class="text-zinc-400 text-sm">Offline</div>
        </div>
    </div>

    <!-- Server Cards -->
    @if($servers->isEmpty())
        <div class="bg-zinc-800 rounded-lg p-12 text-center">
            <div class="text-4xl mb-4">🖥️</div>
            <h3 class="text-xl font-semibold mb-2">No servers yet</h3>
            <p class="text-zinc-400 mb-6">Add your first server to start monitoring.</p>
            <a href="{{ route('servers.create') }}" class="inline-block px-6 py-2 bg-emerald-600 hover:bg-emerald-500 rounded-lg font-semibold transition-colors">
                Add Server
            </a>
        </div>
    @else
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
            @foreach($servers as $server)
                <a href="{{ route('servers.show', $server) }}" class="block bg-zinc-800 hover:bg-zinc-750 rounded-lg p-6 transition-colors border border-zinc-700 hover:border-zinc-600">
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="w-3 h-3 rounded-full
                                @if($server->status === 'online') bg-emerald-400
                                @elseif($server->status === 'warning') bg-amber-400
                                @elseif($server->status === 'critical') bg-red-400 animate-pulse
                                @else bg-zinc-500
                                @endif
                            "></div>
                            <h3 class="font-semibold text-lg">{{ $server->name }}</h3>
                        </div>
                        <span class="text-xs
                            @if(!$server->last_seen_at)
                                text-zinc-500
                            @elseif($server->last_seen_at->diffInMinutes(now()) < 2)
                                text-emerald-400
                            @elseif($server->last_seen_at->diffInMinutes(now()) < 5)
                                text-amber-400
                            @else
                                text-red-400
                            @endif
                        ">
                            @if($server->last_seen_at)
                                {{ $server->last_seen_at->diffForHumans() }}
                            @else
                                Never
                            @endif
                        </span>
                    </div>

                    <!-- Hostname -->
                    @if($server->hostname)
                        <div class="text-sm text-zinc-400 mb-4 font-mono">{{ $server->hostname }}</div>
                    @endif

                    <!-- Metrics -->
                    @if($server->latest_metric)
                        <div class="grid grid-cols-3 gap-4 text-center">
                            <!-- CPU -->
                            <div>
                                <div class="text-xs text-zinc-500 mb-1">CPU</div>
                                <div class="font-semibold @if($server->latest_metric->normalized_load >= 1.5) text-amber-400 @elseif($server->latest_metric->normalized_load >= 2) text-red-400 @endif">
                                    {{ number_format($server->latest_metric->normalized_load * 100, 0) }}%
                                </div>
                            </div>
                            <!-- Memory -->
                            <div>
                                <div class="text-xs text-zinc-500 mb-1">RAM</div>
                                <div class="font-semibold @if($server->latest_metric->memory_percent >= 80) text-amber-400 @elseif($server->latest_metric->memory_percent >= 95) text-red-400 @endif">
                                    {{ number_format($server->latest_metric->memory_percent, 0) }}%
                                </div>
                            </div>
                            <!-- Disk -->
                            <div>
                                <div class="text-xs text-zinc-500 mb-1">Disk</div>
                                <div class="font-semibold @if($server->latest_metric->max_disk_percent >= 80) text-amber-400 @elseif($server->latest_metric->max_disk_percent >= 95) text-red-400 @endif">
                                    {{ number_format($server->latest_metric->max_disk_percent, 0) }}%
                                </div>
                            </div>
                        </div>

                        <!-- Issue Summary -->
                        @if($server->status === 'warning' || $server->status === 'critical')
                            @php
                                $issues = [];
                                if ($server->latest_metric->memory_percent >= 80) $issues[] = 'Memory';
                                if ($server->latest_metric->max_disk_percent >= 80) $issues[] = 'Disk';
                                if ($server->latest_metric->normalized_load >= 1.5) $issues[] = 'CPU';
                                if ($server->latest_metric->failed_services_count > 0) $issues[] = $server->latest_metric->failed_services_count . ' service(s)';
                            @endphp
                            <div class="mt-3 pt-3 border-t border-zinc-700 text-xs @if($server->status === 'critical') text-red-400 @else text-amber-400 @endif">
                                {{ implode(', ', $issues) }} high
                            </div>
                        @endif
                    @else
                        <div class="text-sm text-zinc-500 text-center py-4">
                            Waiting for first metrics...
                        </div>
                    @endif
                </a>
            @endforeach
        </div>
    @endif
</div>

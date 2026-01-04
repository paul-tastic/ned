<div
    x-data="{
        autoRefresh: localStorage.getItem('ned-auto-refresh') !== 'false',
        interval: null,
        startPolling() {
            if (this.interval) clearInterval(this.interval);
            this.interval = setInterval(() => {
                if (this.autoRefresh) $wire.$refresh();
            }, 30000);
        }
    }"
    x-init="startPolling(); $watch('autoRefresh', val => localStorage.setItem('ned-auto-refresh', val))"
>
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
        <label class="flex items-center gap-2 text-sm text-zinc-400 cursor-pointer">
            <span>Auto-refresh</span>
            <button
                @click="autoRefresh = !autoRefresh"
                :class="autoRefresh ? 'bg-emerald-600' : 'bg-zinc-600'"
                class="relative inline-flex h-5 w-9 items-center rounded-full transition-colors"
            >
                <span
                    :class="autoRefresh ? 'translate-x-5' : 'translate-x-1'"
                    class="inline-block h-3 w-3 transform rounded-full bg-white transition-transform"
                ></span>
            </button>
        </label>
    </div>

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
            @php
                $totalDiskUsed = collect($latestMetric->disks)->sum('used_mb');
                $totalDiskSize = collect($latestMetric->disks)->sum('total_mb');
                $totalDiskPercent = $totalDiskSize > 0 ? ($totalDiskUsed / $totalDiskSize) * 100 : 0;
            @endphp
            <div class="bg-zinc-800 rounded-lg p-4">
                <div class="text-zinc-400 text-sm mb-1">Disk</div>
                <div class="text-2xl font-bold @if($totalDiskPercent >= 80) text-amber-400 @elseif($totalDiskPercent >= 95) text-red-400 @endif">
                    {{ number_format($totalDiskPercent, 0) }}%
                </div>
                <div class="text-zinc-500 text-xs">{{ number_format($totalDiskUsed / 1024, 0) }}GB / {{ number_format($totalDiskSize / 1024, 0) }}GB</div>
            </div>
            <div class="bg-zinc-800 rounded-lg p-4">
                <div class="text-zinc-400 text-sm mb-1">Uptime</div>
                <div class="text-2xl font-bold">
                    {{ floor($latestMetric->uptime / 86400) }}d
                </div>
                <div class="text-zinc-500 text-xs">{{ gmdate('H:i:s', $latestMetric->uptime % 86400) }}</div>
            </div>
        </div>

        <!-- Resource History Charts -->
        @if(count($cpuChartData) > 1)
            <div class="bg-zinc-800 rounded-lg p-6 mb-8">
                <h3 class="font-semibold mb-4">Resource History</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- CPU Chart -->
                    <div>
                        <h4 class="text-sm text-zinc-400 mb-2">CPU Load</h4>
                        <div
                            x-data="{
                                data: {{ json_encode($cpuChartData) }},
                                hoveredIndex: null,
                                get maxValue() {
                                    return Math.max(100, ...this.data.map(d => d.value));
                                }
                            }"
                            class="relative"
                        >
                            <div class="flex items-end gap-px h-20 bg-zinc-900 rounded-lg p-2">
                                <template x-for="(point, index) in data" :key="index">
                                    <div
                                        class="flex-1 relative cursor-pointer h-full flex items-end"
                                        @mouseenter="hoveredIndex = index"
                                        @mouseleave="hoveredIndex = null"
                                    >
                                        <div
                                            class="w-full rounded-t transition-all"
                                            :class="point.value >= 150 ? 'bg-red-500' : point.value >= 100 ? 'bg-amber-500' : 'bg-cyan-500/70'"
                                            :style="'height: ' + Math.max(2, (point.value / maxValue) * 100) + '%'"
                                        ></div>
                                        <div
                                            x-show="hoveredIndex === index"
                                            x-transition
                                            class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 bg-zinc-700 text-xs text-zinc-200 rounded whitespace-nowrap z-10"
                                        >
                                            <span x-text="point.time"></span><br>
                                            <span x-text="point.value + '%'" class="font-bold"></span>
                                            <span class="text-zinc-400" x-text="'(' + point.load + '/' + point.cores + ')'"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <div class="flex justify-between text-xs text-zinc-500 mt-1">
                                <span x-text="data[0]?.time || ''"></span>
                                <span x-text="data[data.length - 1]?.time || ''"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Memory Chart -->
                    <div>
                        <h4 class="text-sm text-zinc-400 mb-2">Memory</h4>
                        <div
                            x-data="{
                                data: {{ json_encode($memoryChartData) }},
                                hoveredIndex: null,
                                formatMB(mb) {
                                    return (mb / 1024).toFixed(1) + ' GB';
                                }
                            }"
                            class="relative"
                        >
                            <div class="flex items-end gap-px h-20 bg-zinc-900 rounded-lg p-2">
                                <template x-for="(point, index) in data" :key="index">
                                    <div
                                        class="flex-1 relative cursor-pointer h-full flex items-end"
                                        @mouseenter="hoveredIndex = index"
                                        @mouseleave="hoveredIndex = null"
                                    >
                                        <div
                                            class="w-full rounded-t transition-all"
                                            :class="point.value >= 95 ? 'bg-red-500' : point.value >= 80 ? 'bg-amber-500' : 'bg-violet-500/70'"
                                            :style="'height: ' + Math.max(2, point.value) + '%'"
                                        ></div>
                                        <div
                                            x-show="hoveredIndex === index"
                                            x-transition
                                            class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 bg-zinc-700 text-xs text-zinc-200 rounded whitespace-nowrap z-10"
                                        >
                                            <span x-text="point.time"></span><br>
                                            <span x-text="point.value + '%'" class="font-bold"></span>
                                            <span class="text-zinc-400" x-text="'(' + formatMB(point.used) + '/' + formatMB(point.total) + ')'"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <div class="flex justify-between text-xs text-zinc-500 mt-1">
                                <span x-text="data[0]?.time || ''"></span>
                                <span x-text="data[data.length - 1]?.time || ''"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Disk Chart -->
                    <div>
                        <h4 class="text-sm text-zinc-400 mb-2">Disk (max)</h4>
                        <div
                            x-data="{
                                data: {{ json_encode($diskChartData) }},
                                hoveredIndex: null
                            }"
                            class="relative"
                        >
                            <div class="flex items-end gap-px h-20 bg-zinc-900 rounded-lg p-2">
                                <template x-for="(point, index) in data" :key="index">
                                    <div
                                        class="flex-1 relative cursor-pointer h-full flex items-end"
                                        @mouseenter="hoveredIndex = index"
                                        @mouseleave="hoveredIndex = null"
                                    >
                                        <div
                                            class="w-full rounded-t transition-all"
                                            :class="point.value >= 95 ? 'bg-red-500' : point.value >= 80 ? 'bg-amber-500' : 'bg-orange-500/70'"
                                            :style="'height: ' + Math.max(2, point.value) + '%'"
                                        ></div>
                                        <div
                                            x-show="hoveredIndex === index"
                                            x-transition
                                            class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 bg-zinc-700 text-xs text-zinc-200 rounded whitespace-nowrap z-10"
                                        >
                                            <span x-text="point.time"></span><br>
                                            <span x-text="point.value + '%'" class="font-bold"></span>
                                            <span class="text-zinc-400" x-text="point.mount"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <div class="flex justify-between text-xs text-zinc-500 mt-1">
                                <span x-text="data[0]?.time || ''"></span>
                                <span x-text="data[data.length - 1]?.time || ''"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

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
                        @php
                            $description = \App\Support\ServiceInfo::get($service['name']);
                        @endphp
                        <div
                            x-data="{ showTooltip: false, timeout: null }"
                            @mouseenter="timeout = setTimeout(() => showTooltip = true, 800)"
                            @mouseleave="clearTimeout(timeout); showTooltip = false"
                            class="flex items-center gap-2 bg-zinc-900 rounded-lg px-3 py-2 relative"
                        >
                            <div class="w-2 h-2 rounded-full {{ $service['status'] === 'running' ? 'bg-emerald-400' : 'bg-red-400' }}"></div>
                            <span class="text-sm font-mono">{{ $service['name'] }}</span>
                            @if($description)
                                <div
                                    x-show="showTooltip"
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0"
                                    x-transition:enter-end="opacity-100"
                                    x-transition:leave="transition ease-in duration-100"
                                    x-transition:leave-start="opacity-100"
                                    x-transition:leave-end="opacity-0"
                                    class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 bg-zinc-700 text-xs text-zinc-200 rounded-lg pointer-events-none whitespace-nowrap z-10"
                                >
                                    {{ $description }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Network I/O -->
        @if($latestMetric->network && count($latestMetric->network) > 0)
            @php
                $iface = $latestMetric->network[0];
                $formatBytes = function($bytes) {
                    if ($bytes >= 1099511627776) return number_format($bytes / 1099511627776, 2) . ' TB';
                    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
                    if ($bytes >= 1048576) return number_format($bytes / 1048576, 1) . ' MB';
                    if ($bytes >= 1024) return number_format($bytes / 1024, 0) . ' KB';
                    return $bytes . ' B';
                };
                $totalRx = collect($networkChartData)->sum('rx');
                $totalTx = collect($networkChartData)->sum('tx');
            @endphp
            <div class="bg-zinc-800 rounded-lg p-6 mb-8">
                <h3 class="font-semibold mb-4">Network I/O <span class="text-zinc-500 font-normal text-sm font-mono">({{ $iface['interface'] }})</span></h3>

                <!-- Summary stats -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-zinc-900 rounded-lg p-3">
                        <div class="text-zinc-500 text-xs mb-1">↓ Downloaded (24h)</div>
                        <div class="text-lg font-bold text-emerald-400">{{ $formatBytes($totalRx) }}</div>
                    </div>
                    <div class="bg-zinc-900 rounded-lg p-3">
                        <div class="text-zinc-500 text-xs mb-1">↑ Uploaded (24h)</div>
                        <div class="text-lg font-bold text-blue-400">{{ $formatBytes($totalTx) }}</div>
                    </div>
                    <div class="bg-zinc-900 rounded-lg p-3">
                        <div class="text-zinc-500 text-xs mb-1">↓ Total since boot</div>
                        <div class="text-lg font-bold text-emerald-400">{{ $formatBytes($iface['rx_bytes']) }}</div>
                    </div>
                    <div class="bg-zinc-900 rounded-lg p-3">
                        <div class="text-zinc-500 text-xs mb-1">↑ Total since boot</div>
                        <div class="text-lg font-bold text-blue-400">{{ $formatBytes($iface['tx_bytes']) }}</div>
                    </div>
                </div>

                <!-- Network Traffic Chart -->
                @if(count($networkChartData) > 1)
                    <div
                        x-data="{
                            data: {{ json_encode($networkChartData) }},
                            hoveredIndex: null,
                            formatBytes(bytes) {
                                if (bytes >= 1073741824) return (bytes / 1073741824).toFixed(2) + ' GB';
                                if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
                                if (bytes >= 1024) return Math.round(bytes / 1024) + ' KB';
                                return bytes + ' B';
                            },
                            get maxBytes() {
                                return Math.max(1, ...this.data.map(d => Math.max(d.rx, d.tx)));
                            }
                        }"
                        class="relative"
                    >
                        <div class="flex items-end gap-px h-32 bg-zinc-900 rounded-lg p-2">
                            <template x-for="(point, index) in data" :key="index">
                                <div
                                    class="flex-1 relative group cursor-pointer h-full flex flex-col justify-end gap-px"
                                    @mouseenter="hoveredIndex = index"
                                    @mouseleave="hoveredIndex = null"
                                >
                                    <!-- RX bar (download - green) -->
                                    <div
                                        class="w-full bg-emerald-500/70 rounded-t transition-all"
                                        :style="'height: ' + (point.rx > 0 ? Math.max(2, (point.rx / maxBytes) * 50) : 1) + '%'"
                                    ></div>
                                    <!-- TX bar (upload - blue) -->
                                    <div
                                        class="w-full bg-blue-500/70 rounded-t transition-all"
                                        :style="'height: ' + (point.tx > 0 ? Math.max(2, (point.tx / maxBytes) * 50) : 1) + '%'"
                                    ></div>
                                    <!-- Tooltip -->
                                    <div
                                        x-show="hoveredIndex === index"
                                        x-transition
                                        class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 bg-zinc-700 text-xs text-zinc-200 rounded whitespace-nowrap z-10"
                                    >
                                        <span x-text="point.time"></span><br>
                                        <span class="text-emerald-400">↓</span> <span x-text="formatBytes(point.rx)"></span>
                                        <span class="text-blue-400 ml-2">↑</span> <span x-text="formatBytes(point.tx)"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                        <div class="flex justify-between text-xs text-zinc-500 mt-1 px-2">
                            <span x-text="data[0]?.time || ''"></span>
                            <div class="flex gap-4">
                                <span><span class="inline-block w-2 h-2 bg-emerald-500/70 rounded mr-1"></span>Download</span>
                                <span><span class="inline-block w-2 h-2 bg-blue-500/70 rounded mr-1"></span>Upload</span>
                            </div>
                            <span x-text="data[data.length - 1]?.time || ''"></span>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <!-- Security -->
        @if($latestMetric->security)
            <div class="bg-zinc-800 rounded-lg p-6 mb-8">
                <h3 class="font-semibold mb-4">Security</h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    @if(isset($latestMetric->security['ssh_failed_24h']))
                        <div class="bg-zinc-900 rounded-lg p-4 group relative">
                            <div class="text-zinc-400 text-sm mb-1 flex items-center gap-1">
                                SSH Attacks (24h)
                                <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="text-xl font-bold @if($latestMetric->security['ssh_failed_24h'] > 500) text-red-400 @elseif($latestMetric->security['ssh_failed_24h'] > 100) text-amber-400 @endif">
                                {{ number_format($latestMetric->security['ssh_failed_24h']) }}
                            </div>
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 bg-zinc-700 text-xs text-zinc-200 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-10">
                                SSH brute-force login attempts in the last 24 hours.<br>These are blocked by fail2ban after repeated failures.
                            </div>
                        </div>
                    @endif
                    @if(isset($latestMetric->security['f2b_currently_banned']))
                        <div class="bg-zinc-900 rounded-lg p-4 group relative">
                            <div class="text-zinc-400 text-sm mb-1 flex items-center gap-1">
                                Currently Banned
                                <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="text-xl font-bold @if($latestMetric->security['f2b_currently_banned'] > 0) text-emerald-400 @endif">
                                {{ number_format($latestMetric->security['f2b_currently_banned']) }}
                            </div>
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 bg-zinc-700 text-xs text-zinc-200 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-10">
                                IPs currently blocked by fail2ban.<br>Green = fail2ban is actively protecting.
                            </div>
                        </div>
                    @endif
                    @if(isset($latestMetric->security['f2b_total_banned']))
                        <div class="bg-zinc-900 rounded-lg p-4 group relative">
                            <div class="text-zinc-400 text-sm mb-1 flex items-center gap-1">
                                Total Banned
                                <svg class="w-3.5 h-3.5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="text-xl font-bold">{{ number_format($latestMetric->security['f2b_total_banned']) }}</div>
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-3 py-2 bg-zinc-700 text-xs text-zinc-200 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none whitespace-nowrap z-10">
                                Total IPs banned by fail2ban since last restart.
                            </div>
                        </div>
                    @endif
                </div>

                <!-- fail2ban Settings -->
                @if(isset($latestMetric->security['f2b_bantime']) && $latestMetric->security['f2b_bantime'] > 0)
                    <div class="mt-4 p-3 bg-zinc-900 rounded-lg">
                        <h4 class="text-xs text-zinc-500 mb-2 uppercase tracking-wide">fail2ban Settings</h4>
                        <div class="flex flex-wrap gap-4 text-sm">
                            <div>
                                <span class="text-zinc-400">Ban time:</span>
                                <span class="text-zinc-200 font-mono">{{ floor($latestMetric->security['f2b_bantime'] / 60) }} min</span>
                            </div>
                            <div>
                                <span class="text-zinc-400">Max retry:</span>
                                <span class="text-zinc-200 font-mono">{{ $latestMetric->security['f2b_maxretry'] ?? 'N/A' }}</span>
                            </div>
                            <div>
                                <span class="text-zinc-400">Find time:</span>
                                <span class="text-zinc-200 font-mono">{{ floor(($latestMetric->security['f2b_findtime'] ?? 0) / 60) }} min</span>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Banned IPs -->
                @if(!empty($latestMetric->security['banned_ips']))
                    <div class="mt-4">
                        <h4 class="text-sm text-zinc-400 mb-3">Currently Banned IPs</h4>
                        <div class="space-y-2">
                            @foreach($latestMetric->security['banned_ips'] as $banned)
                                @php
                                    // Handle both old format (string) and new format (object with ip/unban_at)
                                    $ip = is_array($banned) ? ($banned['ip'] ?? null) : $banned;
                                    $unbanAt = is_array($banned) ? ($banned['unban_at'] ?? null) : null;
                                    $geo = $ip ? ($bannedIpGeo[$ip] ?? null) : null;
                                    $banCount = $ip ? ($bannedIpCounts[$ip] ?? 0) : 0;

                                    // Calculate time remaining
                                    $timeRemaining = null;
                                    if ($unbanAt) {
                                        try {
                                            $unbanTime = \Carbon\Carbon::parse($unbanAt);
                                            $now = now();
                                            $mins = (int) $now->diffInMinutes($unbanTime, false);
                                            if ($mins > 0) {
                                                $timeRemaining = $mins . 'm remaining';
                                            }
                                        } catch (\Exception $e) {}
                                    }
                                @endphp
                                @if($ip)
                                <div class="flex items-center justify-between bg-zinc-900 rounded-lg px-4 py-2">
                                    <div class="flex items-center gap-3">
                                        <code class="text-sm font-mono text-red-400">{{ $ip }}</code>
                                        @if($banCount > 1)
                                            <span class="text-xs text-orange-400/80">(banned {{ $banCount }}×)</span>
                                        @endif
                                        @if($geo)
                                            <span class="text-xs text-zinc-500">
                                                {{ $geo['city'] ? $geo['city'] . ', ' : '' }}{{ $geo['country'] ?? 'Unknown' }}
                                                @if($geo['isp'])
                                                    <span class="text-zinc-600">· {{ $geo['isp'] }}</span>
                                                @endif
                                            </span>
                                        @endif
                                    </div>
                                    <span class="text-xs text-zinc-500">
                                        @if($timeRemaining)
                                            {{ $timeRemaining }}
                                        @else
                                            banned for {{ floor(($latestMetric->security['f2b_bantime'] ?? 3600) / 60) }}m
                                        @endif
                                    </span>
                                </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- SSH Attack Timeline -->
                @if(count($securityChartData) > 1)
                    @php
                        $totalMinutes = count($securityChartData) * 5;
                        if ($totalMinutes >= 1440) {
                            $duration = round($totalMinutes / 1440, 1) . ' day' . ($totalMinutes >= 2880 ? 's' : '');
                        } elseif ($totalMinutes >= 60) {
                            $hours = round($totalMinutes / 60, 1);
                            $duration = $hours . ' hour' . ($hours != 1 ? 's' : '');
                        } else {
                            $duration = $totalMinutes . ' min';
                        }
                    @endphp
                    <div class="mt-6">
                        <h4 class="text-sm text-zinc-400 mb-3">SSH Attack Timeline (last {{ $duration }})</h4>
                        <div
                            x-data="{
                                data: {{ json_encode($securityChartData) }},
                                hoveredIndex: null,
                                get maxAttacks() {
                                    return Math.max(1, ...this.data.map(d => d.attacks));
                                }
                            }"
                            class="relative"
                        >
                            <div class="flex items-end gap-px h-24 bg-zinc-900 rounded-lg p-2">
                                <template x-for="(point, index) in data" :key="index">
                                    <div
                                        class="flex-1 relative group cursor-pointer h-full flex items-end"
                                        @mouseenter="hoveredIndex = index"
                                        @mouseleave="hoveredIndex = null"
                                    >
                                        <div
                                            class="w-full rounded-t transition-all"
                                            :class="point.attacks > 10 ? 'bg-red-500' : point.attacks > 0 ? 'bg-amber-500' : 'bg-zinc-700'"
                                            :style="'height: ' + (point.attacks > 0 ? Math.max(15, (point.attacks / maxAttacks) * 100) : 4) + '%'"
                                        ></div>
                                        <!-- Tooltip -->
                                        <div
                                            x-show="hoveredIndex === index"
                                            x-transition
                                            class="absolute bottom-full left-1/2 -translate-x-1/2 mb-2 px-2 py-1 bg-zinc-700 text-xs text-zinc-200 rounded whitespace-nowrap z-10"
                                        >
                                            <span x-text="point.time"></span>: <span x-text="point.attacks" class="font-bold"></span> new
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <div class="flex justify-between text-xs text-zinc-500 mt-1 px-2">
                                <span x-text="data[0]?.time || ''"></span>
                                <span x-text="data[data.length - 1]?.time || ''"></span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        @endif

        <!-- Raw Data Accordion -->
        <div x-data="{ open: false }" class="bg-zinc-800 rounded-lg mb-8">
            <button @click="open = !open" class="w-full p-4 flex items-center justify-between text-left">
                <span class="font-semibold text-zinc-300">Raw Metric Data</span>
                <svg :class="{ 'rotate-180': open }" class="w-5 h-5 text-zinc-400 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </button>
            <div x-show="open" x-collapse class="px-4 pb-4">
                <pre class="bg-zinc-900 rounded-lg p-4 text-xs font-mono text-zinc-300 overflow-x-auto max-h-96 overflow-y-auto">{{ json_encode([
                    'recorded_at' => $latestMetric->recorded_at->toIso8601String(),
                    'uptime' => $latestMetric->uptime,
                    'load' => [
                        '1m' => $latestMetric->load_1m,
                        '5m' => $latestMetric->load_5m,
                        '15m' => $latestMetric->load_15m,
                    ],
                    'cpu_cores' => $latestMetric->cpu_cores,
                    'memory' => [
                        'total_mb' => $latestMetric->memory_total,
                        'used_mb' => $latestMetric->memory_used,
                        'available_mb' => $latestMetric->memory_available,
                    ],
                    'swap' => [
                        'total_mb' => $latestMetric->swap_total,
                        'used_mb' => $latestMetric->swap_used,
                    ],
                    'disks' => $latestMetric->disks,
                    'network' => $latestMetric->network,
                    'services' => $latestMetric->services,
                    'security' => $latestMetric->security,
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
            </div>
        </div>
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

    <!-- Agent Version Warning -->
    @php
        $latestVersion = trim(file_get_contents(base_path('VERSION')));
        $agentOutdated = $server->agent_version && version_compare($server->agent_version, $latestVersion, '<');
    @endphp
    @if($agentOutdated)
        <div class="bg-amber-900/20 border border-amber-800 rounded-lg p-4 mb-4">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-amber-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                </svg>
                <div>
                    <p class="text-amber-400 font-semibold">Agent Update Available</p>
                    <p class="text-zinc-400 text-sm">Running v{{ $server->agent_version }}, latest is v{{ $latestVersion }}</p>
                </div>
            </div>
            <div class="mt-3">
                <code class="block bg-zinc-900 p-2 rounded text-xs font-mono text-zinc-300 overflow-x-auto">
                    curl -fsSL https://getneddy.com/install.sh | sudo bash
                </code>
            </div>
        </div>
    @endif

    <!-- Last Update -->
    <div class="text-center text-sm">
        @if($server->last_seen_at)
            <span class="
                @if($server->last_seen_at->diffInMinutes(now()) < 2)
                    text-emerald-400
                @elseif($server->last_seen_at->diffInMinutes(now()) < 5)
                    text-amber-400
                @else
                    text-red-400
                @endif
            ">
                Last update {{ $server->last_seen_at->diffForHumans() }}
            </span>
            <span class="mx-2 text-zinc-500">•</span>
            <span class="text-zinc-500">Dashboard v{{ trim(file_get_contents(base_path('VERSION'))) }}</span>
            @if($server->agent_version)
                <span class="mx-2 text-zinc-500">•</span>
                <span class="text-zinc-500">Agent v{{ $server->agent_version }}</span>
            @endif
        @else
            <span class="text-zinc-500">Never connected</span>
        @endif
    </div>

    <!-- Danger Zone -->
    <div class="mt-12 pt-6 border-t border-zinc-800 text-center">
        <button wire:click="$set('showDeleteModal', true)" class="text-sm text-zinc-500 hover:text-red-400 transition-colors">
            Delete this server
        </button>
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

<div class="max-w-2xl mx-auto">
    <div class="bg-zinc-800 rounded-lg p-6">
        <div class="flex items-center gap-3 mb-4">
            <div class="w-10 h-10 rounded-full bg-emerald-600 flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                </svg>
            </div>
            <div>
                <h3 class="font-semibold text-lg text-white">Connect Mobile App</h3>
                <p class="text-zinc-400 text-sm">Scan this QR code with the Ned mobile app to connect instantly.</p>
            </div>
        </div>

        @if ($showQr)
            <div class="flex flex-col items-center py-6 space-y-4">
                {{-- QR Code --}}
                <div class="bg-zinc-800 p-4 rounded-lg">
                    {!! $qrSvg !!}
                </div>

                <p class="text-zinc-400 text-sm">
                    <svg class="inline w-4 h-4 mr-1 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    This code expires in 5 minutes
                </p>

                <div class="flex gap-3 mt-4">
                    <button
                        wire:click="done"
                        class="px-6 py-2 bg-emerald-600 hover:bg-emerald-500 rounded-lg font-semibold text-white transition-colors"
                    >
                        Done
                    </button>
                    <button
                        wire:click="revokeAndClose"
                        class="px-6 py-2 bg-zinc-700 hover:bg-zinc-600 rounded-lg font-semibold text-zinc-300 transition-colors"
                    >
                        Cancel
                    </button>
                </div>
            </div>
        @else
            <button
                wire:click="generateQr"
                class="mt-4 px-6 py-2 bg-emerald-600 hover:bg-emerald-500 rounded-lg font-semibold text-white transition-colors"
            >
                <svg class="inline w-4 h-4 mr-2 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                </svg>
                Generate QR Code
            </button>
        @endif
    </div>
</div>

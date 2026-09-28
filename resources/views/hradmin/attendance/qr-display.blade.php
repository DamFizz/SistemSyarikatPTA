<x-app-layout title="Attendance QR — {{ $office->name }}">
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-800">Attendance QR Checkpoint</h2>
        <p class="text-sm text-slate-500 mt-1">{{ $office->name }} &middot; refreshes every {{ $rotateSeconds }} seconds</p>
    </x-slot>

    <div class="bg-white rounded-xl border border-slate-200 p-10 flex flex-col items-center gap-6" x-data="qrDisplay('{{ route('hr.attendance.qr-current', $office) }}', {{ $rotateSeconds }})" x-init="init()">
        <div id="qr-container" class="p-4 border-4 border-emerald-500 rounded-2xl" x-html="svg"></div>
        <div class="text-slate-500 text-sm">Scan this code from the SEMS Employee attendance page.</div>
        <div class="text-3xl font-mono font-semibold text-emerald-600" x-text="Math.ceil(secondsRemaining) + 's'"></div>
    </div>

    <script>
        function qrDisplay(url, rotateSeconds) {
            return {
                svg: '',
                secondsRemaining: rotateSeconds,
                init() {
                    this.fetchToken();
                    setInterval(() => this.tick(), 1000);
                },
                tick() {
                    this.secondsRemaining--;
                    if (this.secondsRemaining <= 0) {
                        this.fetchToken();
                    }
                },
                async fetchToken() {
                    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    const data = await res.json();
                    this.svg = data.svg;
                    this.secondsRemaining = data.seconds_remaining;
                    window.currentAttendanceQrToken = data.token;
                }
            }
        }
    </script>
</x-app-layout>

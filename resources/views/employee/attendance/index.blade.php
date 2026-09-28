<x-app-layout title="Attendance">
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-800">Attendance</h2>
        <p class="text-sm text-slate-500 mt-1">{{ $office->name }} &middot; allowed radius {{ $office->allowed_radius_meters }}m</p>
    </x-slot>

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="bg-white rounded-xl border border-slate-200 p-6">
        @if (! $today || ! $today->clock_in_time)
            {{-- CLOCK IN WIZARD --}}
            <div x-data="attendanceWizard({{ $office->latitude }}, {{ $office->longitude }}, {{ $office->allowed_radius_meters }})">
                <h3 class="font-semibold text-slate-800 mb-1">Clock In</h3>
                <p class="text-sm text-slate-500 mb-4">GPS verification &rarr; Selfie &rarr; NFC / QR checkpoint</p>

                <div x-show="error" x-cloak class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm" x-text="error"></div>
                <div x-show="distanceInfo && step !== 'start'" x-cloak class="mb-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm" x-text="distanceInfo"></div>

                <template x-if="step === 'start'">
                    <button type="button" @click="requestLocation()" class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700">
                        1. Verify My Location
                    </button>
                </template>

                <div x-show="step === 'selfie'" x-cloak>
                    <p class="text-sm font-medium text-slate-700 mb-2">2. Take a selfie (camera only, no gallery upload)</p>
                    <video x-ref="selfieVideo" autoplay playsinline muted class="w-full max-w-sm rounded-lg bg-slate-900"></video>
                    <canvas x-ref="canvas" class="hidden"></canvas>
                    <div class="mt-3 flex gap-2">
                        <button type="button" @click="captureSelfie()" :disabled="!cameraReady"
                                :class="cameraReady ? 'bg-emerald-600 hover:bg-emerald-700' : 'bg-slate-300 cursor-not-allowed'"
                                class="px-4 py-2 text-white text-sm font-medium rounded-lg">
                            Capture Selfie
                        </button>
                        <button type="button" x-show="!cameraReady" x-cloak @click="retrySelfieCamera()" class="px-4 py-2 bg-slate-800 text-white text-sm font-medium rounded-lg hover:bg-slate-700">
                            Try Camera Again
                        </button>
                    </div>
                </div>

                <div x-show="step === 'checkpoint' && checkpointMethod === 'nfc'" x-cloak>
                    <p class="text-sm font-medium text-slate-700 mb-2">3. Tap your phone on the attendance NFC tag</p>
                    <div class="flex items-center gap-3 text-sm text-slate-500 mb-3">
                        <svg class="w-8 h-8 text-emerald-500 animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.14 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" /></svg>
                        <span x-text="nfcStatus === 'error' ? 'NFC scan failed or was cancelled.' : 'Waiting for NFC tag...'"></span>
                    </div>
                    <button type="button" @click="switchToQr()" class="text-sm text-emerald-600 underline">
                        Phone doesn't support NFC? Use QR code instead
                    </button>
                </div>

                <div x-show="step === 'checkpoint' && checkpointMethod === 'qr'" x-cloak>
                    <p class="text-sm font-medium text-slate-700 mb-2">3. Scan the attendance QR at your checkpoint</p>
                    <video x-ref="qrVideo" autoplay playsinline muted class="w-full max-w-sm rounded-lg bg-slate-900"></video>
                    <p class="text-xs text-slate-400 mt-2">Point your camera at the QR code displayed at the checkpoint screen.</p>
                    <button type="button" x-show="!cameraReady" x-cloak @click="retryQrCamera()" class="mt-3 px-4 py-2 bg-slate-800 text-white text-sm font-medium rounded-lg hover:bg-slate-700">
                        Try Camera Again
                    </button>
                </div>

                <div x-show="step === 'ready'" x-cloak>
                    <p class="text-sm font-medium text-emerald-700 mb-2">All verifications complete.</p>
                    <img :src="selfieDataUrl" class="w-32 h-32 object-cover rounded-lg border border-slate-200 mb-3">
                    <p class="text-xs text-slate-400 mb-3">Checkpoint verified via <strong x-text="checkpointMethod === 'nfc' ? 'NFC' : 'QR code'"></strong>.</p>
                    <form x-ref="clockInForm" method="POST" action="{{ route('employee.attendance.clock-in') }}">
                        @csrf
                        <input type="hidden" name="latitude" x-ref="latInput">
                        <input type="hidden" name="longitude" x-ref="lngInput">
                        <input type="hidden" name="selfie" x-ref="selfieInput">
                        <input type="hidden" name="qr_token" x-ref="qrInput">
                        <input type="hidden" name="nfc_tag_id" x-ref="nfcInput">
                        <button type="button" @click="submitClockIn()" class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700">
                            Confirm Clock In
                        </button>
                    </form>
                </div>
            </div>
        @elseif (! $today->clock_out_time)
            {{-- CLOCK OUT --}}
            <div x-data="clockOutWidget({{ $office->latitude }}, {{ $office->longitude }}, {{ $office->allowed_radius_meters }})">
                <h3 class="font-semibold text-slate-800 mb-1">You're clocked in</h3>
                <p class="text-sm text-slate-500 mb-4">Since {{ $today->clock_in_time->format('H:i') }} &middot; Status: {{ str($today->status)->replace('_',' ')->title() }}</p>

                <div x-show="error" x-cloak class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm" x-text="error"></div>

                <form x-ref="form" method="POST" action="{{ route('employee.attendance.clock-out') }}">
                    @csrf
                    <input type="hidden" name="latitude" x-ref="latInput">
                    <input type="hidden" name="longitude" x-ref="lngInput">
                    <button type="button" @click="clockOut()" class="px-4 py-2 bg-slate-800 text-white text-sm font-medium rounded-lg hover:bg-slate-700">
                        Clock Out
                    </button>
                </form>
            </div>
        @else
            <div class="text-sm text-slate-600">
                <p class="font-semibold text-emerald-700 mb-1">Attendance completed for today.</p>
                <p>Clock In: {{ $today->clock_in_time->format('H:i') }} &middot; Clock Out: {{ $today->clock_out_time->format('H:i') }}</p>
                @if ($today->working_minutes)
                    <p>Working hours: {{ intdiv($today->working_minutes, 60) }}h {{ $today->working_minutes % 60 }}m</p>
                @endif
            </div>
        @endif
    </div>

    <div class="mt-6 bg-white rounded-xl border border-slate-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-100 font-semibold text-slate-700 text-sm">Recent Attendance</div>
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr class="text-left text-xs uppercase tracking-wide text-slate-500">
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Clock In</th>
                    <th class="px-4 py-3">Clock Out</th>
                    <th class="px-4 py-3">Working Hours</th>
                    <th class="px-4 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($history as $record)
                    <tr>
                        <td class="px-4 py-3">{{ $record->attendance_date->format('d M Y') }}</td>
                        <td class="px-4 py-3">{{ $record->clock_in_time?->format('H:i') ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $record->clock_out_time?->format('H:i') ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $record->working_minutes ? intdiv($record->working_minutes, 60).'h '.($record->working_minutes % 60).'m' : '-' }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$record->status" /></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-slate-400">No attendance history yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
    <script>
        function haversine(lat1, lon1, lat2, lon2) {
            const R = 6371000;
            const toRad = d => d * Math.PI / 180;
            const dLat = toRad(lat2 - lat1);
            const dLon = toRad(lon2 - lon1);
            const a = Math.sin(dLat / 2) ** 2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLon / 2) ** 2;
            return 2 * R * Math.asin(Math.sqrt(a));
        }

        function attendanceWizard(officeLat, officeLng, radius) {
            return {
                step: 'start',
                error: '',
                distanceInfo: '',
                lat: null,
                lng: null,
                selfieDataUrl: null,
                qrToken: null,
                nfcTagId: null,
                checkpointMethod: null,
                nfcStatus: '',
                stream: null,
                scanning: false,
                nfcCancelled: false,
                cameraReady: false,

                requestLocation() {
                    this.error = '';
                    if (!navigator.geolocation) {
                        this.error = 'GPS is not available on this device/browser.';
                        return;
                    }
                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            this.lat = pos.coords.latitude;
                            this.lng = pos.coords.longitude;
                            const distance = haversine(this.lat, this.lng, officeLat, officeLng);
                            if (distance > radius) {
                                this.error = `You are ${Math.round(distance)} meters away from your workplace. Clock-in is only available within ${radius} meters.`;
                                return;
                            }
                            this.distanceInfo = `You are ${Math.round(distance)} meters from the office. Location verified.`;
                            this.step = 'selfie';
                            this.$nextTick(() => this.startCamera('user', 'selfieVideo'));
                        },
                        (err) => {
                            this.error = 'Unable to get GPS location: ' + err.message;
                        },
                        { enableHighAccuracy: true, timeout: 10000 }
                    );
                },

                async startCamera(facingMode, videoRef) {
                    this.error = '';
                    this.cameraReady = false;
                    this.stopCamera();
                    try {
                        this.stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { ideal: facingMode } } });
                        this.$refs[videoRef].srcObject = this.stream;
                        this.cameraReady = true;
                    } catch (e) {
                        this.error = 'Camera access was denied or is unavailable. Please allow camera permission for this site in your browser settings, then tap "Try Camera Again".';
                    }
                },

                retrySelfieCamera() {
                    this.startCamera('user', 'selfieVideo');
                },

                retryQrCamera() {
                    this.startCamera('environment', 'qrVideo').then(() => {
                        if (this.cameraReady) this.startQrScan();
                    });
                },

                captureSelfie() {
                    if (!this.cameraReady) return;
                    const video = this.$refs.selfieVideo;
                    const canvas = this.$refs.canvas;
                    canvas.width = video.videoWidth || 480;
                    canvas.height = video.videoHeight || 360;
                    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
                    this.selfieDataUrl = canvas.toDataURL('image/jpeg', 0.8);
                    this.stopCamera();
                    this.step = 'checkpoint';
                    this.$nextTick(() => this.startCheckpoint());
                },

                startCheckpoint() {
                    if ('NDEFReader' in window) {
                        this.checkpointMethod = 'nfc';
                        this.tryNfc();
                    } else {
                        this.switchToQr();
                    }
                },

                async tryNfc() {
                    this.nfcStatus = 'scanning';
                    this.nfcCancelled = false;
                    try {
                        const reader = new NDEFReader();
                        await reader.scan();
                        reader.onreading = (event) => {
                            if (this.nfcCancelled) return;
                            const decoder = new TextDecoder();
                            for (const record of event.message.records) {
                                const text = decoder.decode(record.data);
                                if (text) {
                                    this.nfcTagId = text;
                                    this.step = 'ready';
                                    return;
                                }
                            }
                        };
                        reader.onreadingerror = () => {
                            this.nfcStatus = 'error';
                        };
                    } catch (e) {
                        this.nfcStatus = 'error';
                    }
                },

                switchToQr() {
                    this.nfcCancelled = true;
                    this.checkpointMethod = 'qr';
                    this.startCamera('environment', 'qrVideo').then(() => {
                        if (this.cameraReady) this.startQrScan();
                    });
                },

                startQrScan() {
                    this.scanning = true;
                    const video = this.$refs.qrVideo;
                    const canvas = document.createElement('canvas');
                    const ctx = canvas.getContext('2d');

                    const scanFrame = () => {
                        if (!this.scanning) return;
                        if (video.readyState === video.HAVE_ENOUGH_DATA) {
                            canvas.width = video.videoWidth;
                            canvas.height = video.videoHeight;
                            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                            const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                            const code = window.jsQR(imageData.data, imageData.width, imageData.height);
                            if (code) {
                                this.qrToken = code.data;
                                this.scanning = false;
                                this.stopCamera();
                                this.step = 'ready';
                                return;
                            }
                        }
                        requestAnimationFrame(scanFrame);
                    };
                    requestAnimationFrame(scanFrame);
                },

                stopCamera() {
                    if (this.stream) {
                        this.stream.getTracks().forEach(t => t.stop());
                    }
                },

                submitClockIn() {
                    this.$refs.latInput.value = this.lat;
                    this.$refs.lngInput.value = this.lng;
                    this.$refs.selfieInput.value = this.selfieDataUrl;
                    this.$refs.qrInput.value = this.qrToken || '';
                    this.$refs.nfcInput.value = this.nfcTagId || '';
                    this.$refs.clockInForm.submit();
                },
            };
        }

        function clockOutWidget(officeLat, officeLng, radius) {
            return {
                error: '',
                clockOut() {
                    this.error = '';
                    if (!navigator.geolocation) {
                        this.error = 'GPS is not available on this device/browser.';
                        return;
                    }
                    navigator.geolocation.getCurrentPosition(
                        (pos) => {
                            const distance = haversine(pos.coords.latitude, pos.coords.longitude, officeLat, officeLng);
                            if (distance > radius) {
                                this.error = `You are ${Math.round(distance)} meters away from your workplace. Clock-out is only available within ${radius} meters.`;
                                return;
                            }
                            this.$refs.latInput.value = pos.coords.latitude;
                            this.$refs.lngInput.value = pos.coords.longitude;
                            this.$refs.form.submit();
                        },
                        (err) => {
                            this.error = 'Unable to get GPS location: ' + err.message;
                        },
                        { enableHighAccuracy: true, timeout: 10000 }
                    );
                },
            };
        }
    </script>
</x-app-layout>

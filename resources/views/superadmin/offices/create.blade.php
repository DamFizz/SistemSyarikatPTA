<x-app-layout title="Add Branch">
    <x-slot name="header">
        <h2 class="page-title">Add Office / Branch</h2>
    </x-slot>

    <form method="POST" action="{{ route('super-admin.offices.store') }}" x-data="{ locating: false, locateError: '' }" class="form-card max-w-2xl">
        @csrf
        <div>
            <x-input-label for="name" value="Branch Name" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name') }}" required autofocus placeholder="e.g. HQ Penang, Branch Johor Bahru" />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="address" value="Address" />
            <textarea id="address" name="address" rows="2" class="input mt-1.5 block w-full" required>{{ old('address') }}</textarea>
            <x-input-error :messages="$errors->get('address')" class="mt-1" />
        </div>

        <div>
            <button type="button" @click="
                locating = true; locateError = '';
                navigator.geolocation.getCurrentPosition(
                    (pos) => { document.getElementById('latitude').value = pos.coords.latitude.toFixed(7); document.getElementById('longitude').value = pos.coords.longitude.toFixed(7); locating = false; },
                    (err) => { locateError = 'Unable to get location: ' + err.message; locating = false; }
                )"
                class="btn-success-soft btn-sm">
                <x-icon name="map-pin" class="h-4 w-4" />
                <span x-show="!locating">Use my current GPS location</span>
                <span x-show="locating" x-cloak>Getting location...</span>
            </button>
            <p x-show="locateError" x-cloak x-text="locateError" class="text-xs text-rose-600 mt-1"></p>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <x-input-label for="latitude" value="Latitude" />
                <x-text-input id="latitude" name="latitude" type="text" inputmode="decimal" class="mt-1 block w-full" value="{{ old('latitude') }}" required />
                <x-input-error :messages="$errors->get('latitude')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="longitude" value="Longitude" />
                <x-text-input id="longitude" name="longitude" type="text" inputmode="decimal" class="mt-1 block w-full" value="{{ old('longitude') }}" required />
                <x-input-error :messages="$errors->get('longitude')" class="mt-1" />
            </div>
        </div>
        <div>
            <x-input-label for="allowed_radius_meters" value="Allowed Attendance Radius (meters)" />
            <x-text-input id="allowed_radius_meters" name="allowed_radius_meters" type="number" min="10" max="5000" class="mt-1 block w-full" value="{{ old('allowed_radius_meters', 150) }}" required />
            <x-input-error :messages="$errors->get('allowed_radius_meters')" class="mt-1" />
        </div>
        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('super-admin.offices.index') }}" class="btn-ghost">Cancel</a>
            <button type="submit" class="btn-primary">Create Branch</button>
        </div>
    </form>
</x-app-layout>

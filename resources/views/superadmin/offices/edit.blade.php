<x-app-layout title="Edit Branch">
    <x-slot name="header">
        <h2 class="text-xl font-semibold text-slate-800">Edit Office / Branch</h2>
    </x-slot>

    <form method="POST" action="{{ route('super-admin.offices.update', $office) }}" x-data="{ locating: false, locateError: '' }" class="bg-white rounded-xl border border-slate-200 p-6 space-y-4 max-w-xl">
        @csrf
        @method('PUT')
        <div>
            <x-input-label for="name" value="Branch Name" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" value="{{ old('name', $office->name) }}" required autofocus />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="address" value="Address" />
            <textarea id="address" name="address" rows="2" class="mt-1 block w-full rounded-md border-gray-300 focus:border-emerald-500 focus:ring-emerald-500" required>{{ old('address', $office->address) }}</textarea>
            <x-input-error :messages="$errors->get('address')" class="mt-1" />
        </div>

        <div>
            <button type="button" @click="
                locating = true; locateError = '';
                navigator.geolocation.getCurrentPosition(
                    (pos) => { document.getElementById('latitude').value = pos.coords.latitude.toFixed(7); document.getElementById('longitude').value = pos.coords.longitude.toFixed(7); locating = false; },
                    (err) => { locateError = 'Unable to get location: ' + err.message; locating = false; }
                )"
                class="text-sm text-emerald-600 underline">
                <span x-show="!locating">Use my current GPS location</span>
                <span x-show="locating" x-cloak>Getting location...</span>
            </button>
            <p x-show="locateError" x-cloak x-text="locateError" class="text-xs text-red-600 mt-1"></p>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <x-input-label for="latitude" value="Latitude" />
                <x-text-input id="latitude" name="latitude" type="text" inputmode="decimal" class="mt-1 block w-full" value="{{ old('latitude', $office->latitude) }}" required />
                <x-input-error :messages="$errors->get('latitude')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="longitude" value="Longitude" />
                <x-text-input id="longitude" name="longitude" type="text" inputmode="decimal" class="mt-1 block w-full" value="{{ old('longitude', $office->longitude) }}" required />
                <x-input-error :messages="$errors->get('longitude')" class="mt-1" />
            </div>
        </div>
        <div>
            <x-input-label for="allowed_radius_meters" value="Allowed Attendance Radius (meters)" />
            <x-text-input id="allowed_radius_meters" name="allowed_radius_meters" type="number" min="10" max="5000" class="mt-1 block w-full" value="{{ old('allowed_radius_meters', $office->allowed_radius_meters) }}" required />
            <x-input-error :messages="$errors->get('allowed_radius_meters')" class="mt-1" />
        </div>
        <div>
            <x-input-label for="nfc_tag_id" value="NFC Tag ID (optional)" />
            <x-text-input id="nfc_tag_id" name="nfc_tag_id" type="text" class="mt-1 block w-full" value="{{ old('nfc_tag_id', $office->nfc_tag_id) }}" placeholder="Text written to the physical NFC tag at this checkpoint" />
            <x-input-error :messages="$errors->get('nfc_tag_id')" class="mt-1" />
        </div>
        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('super-admin.offices.index') }}" class="px-4 py-2 text-sm text-slate-600 hover:text-slate-900">Cancel</a>
            <button type="submit" class="px-4 py-2 bg-emerald-600 text-white text-sm font-medium rounded-lg hover:bg-emerald-700">Save Changes</button>
        </div>
    </form>
</x-app-layout>

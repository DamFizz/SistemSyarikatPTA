{{--
 | Profile photo. The phone crops the picture to a centred square and shrinks it to a
 | 512 px JPEG before uploading (fast on mobile data); the server validates it again.
 --}}
<div x-data="{
        preview: null,
        name: '',
        busy: false,
        async pick(event) {
            const file = event.target.files[0];
            if (! file) return;
            this.name = file.name;
            try {
                const blob = await this.squareJpeg(file);
                const transfer = new DataTransfer();
                transfer.items.add(new File([blob], 'profile-photo.jpg', { type: 'image/jpeg' }));
                this.$refs.input.files = transfer.files;
                this.preview = URL.createObjectURL(blob);
            } catch (e) {
                // The browser could not decode it (e.g. an unusual format): send it as is, the server checks it.
                this.preview = null;
            }
        },
        squareJpeg(file) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                img.onload = () => {
                    const side = Math.min(img.naturalWidth, img.naturalHeight);
                    const size = Math.min(512, side);
                    const canvas = document.createElement('canvas');
                    canvas.width = canvas.height = size;
                    const ctx = canvas.getContext('2d');
                    ctx.fillStyle = '#ffffff';
                    ctx.fillRect(0, 0, size, size);
                    ctx.drawImage(img, (img.naturalWidth - side) / 2, (img.naturalHeight - side) / 2, side, side, 0, 0, size, size);
                    URL.revokeObjectURL(img.src);
                    canvas.toBlob((blob) => (blob ? resolve(blob) : reject()), 'image/jpeg', 0.88);
                };
                img.onerror = reject;
                img.src = URL.createObjectURL(file);
            });
        },
        cancel() {
            this.preview = null;
            this.name = '';
            this.$refs.input.value = '';
        },
     }">
    <form method="POST" action="{{ route('profile.photo.update') }}" enctype="multipart/form-data" @submit="busy = true">
        @csrf
        <div class="flex items-center gap-4">
            {{-- Tap the photo to choose a new one --}}
            <label class="lg-press group relative block shrink-0 cursor-pointer" title="Change photo">
                <template x-if="preview">
                    <img :src="preview" alt="New profile photo" class="h-20 w-20 rounded-full object-cover ring-2 ring-emerald-400/70">
                </template>
                <div x-show="! preview">
                    <x-avatar :user="$user" size="h-20 w-20" text="text-3xl" class="ring-2 ring-white/15" />
                </div>
                <span class="absolute -bottom-0.5 -right-0.5 flex h-8 w-8 items-center justify-center rounded-full bg-white text-slate-700 shadow-lg ring-2 ring-ink-900 transition group-hover:scale-110">
                    <x-icon name="camera" class="h-4 w-4" />
                </span>
                <input x-ref="input" type="file" name="photo" accept="image/jpeg,image/png,image/webp,image/*" class="sr-only" @change="pick($event)">
            </label>

            <div class="min-w-0 text-sm">
                <div class="font-semibold text-white">Profile photo</div>
                <p class="text-xs text-slate-400" x-show="! name">Tap the photo to choose a picture. It's cropped to a square.</p>
                <p class="truncate text-xs text-emerald-300" x-show="name" x-cloak x-text="'Selected: ' + name"></p>

                <div class="mt-2 flex flex-wrap gap-2" x-show="name" x-cloak>
                    <button type="submit" class="btn-primary btn-sm" :disabled="busy">
                        <span x-text="busy ? 'Saving…' : 'Save photo'">Save photo</span>
                    </button>
                    <button type="button" @click="cancel()" class="btn-sm btn rounded-full bg-white/10 text-slate-200 hover:bg-white/15">Cancel</button>
                </div>
            </div>
        </div>
        <x-input-error :messages="$errors->get('photo')" class="mt-2 !text-rose-300" />
    </form>

    @if ($user->avatar)
        <form method="POST" action="{{ route('profile.photo.destroy') }}" class="mt-2" x-show="! name"
              onsubmit="return confirm('Remove your profile photo?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-xs font-medium text-slate-400 underline-offset-2 hover:text-rose-300 hover:underline">Remove photo</button>
        </form>
    @endif
</div>

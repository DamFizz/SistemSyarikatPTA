{{-- Glass photo popup used by every a[data-lightbox] link on the page (see app.js). --}}
<div x-data="lightbox" @keydown.escape.window="close()" @keydown.arrow-left.window="open && step(-1)" @keydown.arrow-right.window="open && step(1)">
    <template x-teleport="body">
        <div x-show="open" x-cloak role="dialog" aria-modal="true" aria-label="Photo"
             class="fixed inset-0 z-[70] flex flex-col items-center justify-center p-5"
             @touchstart.passive="swipeStart($event)" @touchend="swipeEnd($event)">
            {{-- Dimmed, blurred page (fades out together with the closing photo) --}}
            <div x-show="open" x-transition.opacity.duration.250ms @click="close()"
                 class="absolute inset-0 bg-slate-950/60 backdrop-blur-md transition-opacity duration-300"
                 :class="closing && 'opacity-0'"></div>

            <div class="relative flex max-h-full w-full max-w-md flex-col items-center">
                {{-- Re-created on every opening, so the same photo can be opened again (its load event fires anew). --}}
                <template x-if="open && item">
                    <img x-ref="img" :src="item.src" :alt="item.caption" @load="reveal()" draggable="false"
                         class="max-h-[72vh] w-auto max-w-full select-none rounded-3xl bg-slate-900/40 object-contain shadow-[0_30px_80px_-20px_rgba(0,0,0,0.6)] ring-1 ring-white/20"
                         :class="loaded ? 'opacity-100' : 'opacity-0'">
                </template>

                <div x-show="loaded && ! closing"
                     x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-y-3 opacity-0" x-transition:enter-end="translate-y-0 opacity-100"
                     x-transition:leave="transition duration-150 ease-in" x-transition:leave-start="translate-y-0 opacity-100" x-transition:leave-end="translate-y-3 opacity-0"
                     class="mt-4 flex items-center gap-2">
                    <button type="button" x-show="items.length > 1" @click="step(-1)" class="lg lg-press flex h-10 w-10 items-center justify-center rounded-full text-white" aria-label="Previous photo">
                        <x-icon name="arrow-left" class="h-4 w-4" />
                    </button>
                    <div class="lg rounded-full px-4 py-2 text-sm font-medium text-white" x-show="item?.caption" x-text="item?.caption"></div>
                    <button type="button" x-show="items.length > 1" @click="step(1)" class="lg lg-press flex h-10 w-10 items-center justify-center rounded-full text-white" aria-label="Next photo">
                        <x-icon name="arrow-right" class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <button type="button" @click="close()" aria-label="Close"
                    class="lg lg-press absolute right-4 top-[max(1rem,env(safe-area-inset-top))] flex h-11 w-11 items-center justify-center rounded-full text-white transition duration-200"
                    :class="closing && 'scale-75 opacity-0'">
                <x-icon name="x" class="h-5 w-5" />
            </button>
        </div>
    </template>
</div>

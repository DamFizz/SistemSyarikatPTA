{{-- Glass photo popup used by every a[data-lightbox] link on the page (see app.js). --}}
<div x-data="lightbox" @keydown.escape.window="close()" @keydown.arrow-left.window="open && step(-1)" @keydown.arrow-right.window="open && step(1)">
    <template x-teleport="body">
        <div x-show="open" x-cloak role="dialog" aria-modal="true" aria-label="Photo"
             class="fixed inset-0 z-[70] flex flex-col items-center justify-center p-5"
             @touchstart.passive="swipeStart($event)" @touchend="swipeEnd($event)">
            {{-- Dimmed, blurred page --}}
            <div x-show="open" x-transition.opacity.duration.250ms class="absolute inset-0 bg-slate-950/60 backdrop-blur-md" @click="close()"></div>

            <div class="relative flex max-h-full w-full max-w-md flex-col items-center">
                <template x-if="item">
                    <img x-ref="img" :src="item.src" :alt="item.caption" @load="zoomIn()"
                         class="max-h-[72vh] w-auto max-w-full rounded-3xl bg-slate-900/40 object-contain shadow-[0_30px_80px_-20px_rgba(0,0,0,0.6)] ring-1 ring-white/20"
                         :class="loaded ? 'opacity-100' : 'opacity-0'">
                </template>

                <div x-show="loaded" x-transition.opacity.duration.200ms class="mt-4 flex items-center gap-2">
                    <button type="button" x-show="items.length > 1" @click="step(-1)" class="lg lg-press flex h-10 w-10 items-center justify-center rounded-full text-white" aria-label="Previous photo">
                        <x-icon name="arrow-left" class="h-4 w-4" />
                    </button>
                    <div class="lg rounded-full px-4 py-2 text-sm font-medium text-white" x-show="item?.caption" x-text="item?.caption"></div>
                    <button type="button" x-show="items.length > 1" @click="step(1)" class="lg lg-press flex h-10 w-10 items-center justify-center rounded-full text-white" aria-label="Next photo">
                        <x-icon name="arrow-right" class="h-4 w-4" />
                    </button>
                </div>
            </div>

            <button type="button" @click="close()" class="lg lg-press absolute right-4 top-[max(1rem,env(safe-area-inset-top))] flex h-11 w-11 items-center justify-center rounded-full text-white" aria-label="Close">
                <x-icon name="x" class="h-5 w-5" />
            </button>
        </div>
    </template>
</div>

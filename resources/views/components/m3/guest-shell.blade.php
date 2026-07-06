<x-layouts.m3-base title="Visitantes">
    <x-m3.tricolor-bar class="!h-1.5" />

    <div class="mx-auto flex min-h-[calc(100vh-6px)] max-w-lg flex-col px-4 py-10">
        {{ $slot }}
    </div>

    <x-m3.tricolor-bar class="fixed bottom-0 left-0 right-0 !h-1" />
</x-layouts.m3-base>

@php
    $current = request()->route()?->getName();
@endphp
<nav class="fixed bottom-0 left-0 right-0 z-20 mx-auto max-w-lg border-t border-slate-200 bg-white shadow-[0_-4px_20px_rgba(0,0,0,0.06)]">
    <div class="grid grid-cols-3 gap-1 px-2 py-2">
        <a href="{{ route('anfitrion.dashboard') }}"
           @class([
               'flex flex-col items-center rounded-xl px-2 py-2 text-xs font-medium transition',
               'bg-m3-primary/10 text-m3-primary' => str_starts_with((string) $current, 'anfitrion.dashboard'),
               'text-slate-500 hover:bg-slate-50' => ! str_starts_with((string) $current, 'anfitrion.dashboard'),
           ])>
            <span class="text-lg leading-none">⌂</span>
            Inicio
        </a>
        <a href="{{ route('anfitrion.registrar') }}"
           @class([
               'flex flex-col items-center rounded-xl px-2 py-2 text-xs font-medium transition',
               'bg-m3-primary/10 text-m3-primary' => str_starts_with((string) $current, 'anfitrion.registrar'),
               'text-slate-500 hover:bg-slate-50' => ! str_starts_with((string) $current, 'anfitrion.registrar'),
           ])>
            <span class="text-lg leading-none">＋</span>
            Registrar
        </a>
        <a href="{{ route('anfitrion.invitados') }}"
           @class([
               'flex flex-col items-center rounded-xl px-2 py-2 text-xs font-medium transition',
               'bg-m3-primary/10 text-m3-primary' => str_starts_with((string) $current, 'anfitrion.invitados') || str_starts_with((string) $current, 'anfitrion.invitado'),
               'text-slate-500 hover:bg-slate-50' => ! str_starts_with((string) $current, 'anfitrion.invitados') && ! str_starts_with((string) $current, 'anfitrion.invitado'),
           ])>
            <span class="text-lg leading-none">☰</span>
            Invitados
        </a>
    </div>
</nav>

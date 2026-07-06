<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Visitantes — Anfitrión</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <div class="mx-auto flex min-h-screen max-w-lg flex-col">
        <header class="sticky top-0 z-20 border-b border-slate-200 bg-m3-primary px-4 py-3 text-white shadow-sm">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-xs uppercase tracking-wide text-white/70">Anfitrión</p>
                    <p class="truncate text-sm font-semibold">{{ auth()->user()->name }}</p>
                </div>
                <form method="POST" action="{{ route('anfitrion.logout') }}">
                    @csrf
                    <button type="submit" class="rounded-lg bg-white/10 px-3 py-1.5 text-xs font-medium hover:bg-white/20">
                        Salir
                    </button>
                </form>
            </div>
            @if(auth()->user()->refugio)
                <p class="mt-2 truncate text-xs text-white/80">
                    Refugio: <span class="font-medium text-white">{{ auth()->user()->refugio->nombre }}</span>
                </p>
            @endif
        </header>

        <main class="flex-1 px-4 py-4 pb-24">
            {{ $slot }}
        </main>

        @include('partials.anfitrion-nav')
    </div>

    @livewireScripts
</body>
</html>

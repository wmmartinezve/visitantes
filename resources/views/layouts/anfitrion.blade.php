<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Visitantes' }} — Anfitrión</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased">
    <div class="mx-auto flex min-h-screen max-w-lg flex-col">
        @isset($header)
            <header class="sticky top-0 z-20 border-b border-slate-200 bg-m3-primary px-4 py-3 text-white shadow-sm">
                {{ $header }}
            </header>
        @endisset

        <main class="flex-1 px-4 py-4 pb-24">
            {{ $slot }}
        </main>

        @isset($nav)
            <nav class="fixed bottom-0 left-0 right-0 z-20 mx-auto max-w-lg border-t border-slate-200 bg-white shadow-[0_-4px_20px_rgba(0,0,0,0.06)]">
                {{ $nav }}
            </nav>
        @endisset
    </div>

    @livewireScripts
</body>
</html>

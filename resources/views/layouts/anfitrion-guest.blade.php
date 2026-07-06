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
<body class="min-h-screen bg-slate-100 antialiased">
    <div class="mx-auto flex min-h-screen max-w-lg items-center px-4 py-8">
        {{ $slot }}
    </div>
    @livewireScripts
</body>
</html>

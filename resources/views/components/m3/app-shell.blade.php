@props(['title' => 'Visitantes', 'subtitle' => null, 'userName' => null, 'contextLabel' => null, 'contextValue' => null, 'logoutRoute' => null, 'profileRoute' => null, 'offlineEnabled' => false])

<x-layouts.m3-base :title="$title">
    @if($offlineEnabled)
        <script>
            window.VisitantesOffline = {
                catalogUrl: @json(route('api.offline.catalog')),
                syncUrl: @json(route('api.offline.sync')),
            };
        </script>
    @endif

    <div class="mx-auto flex min-h-screen max-w-lg flex-col">
        <x-m3.tricolor-bar class="!h-1.5" />

        @if($offlineEnabled)
            <div id="offline-status-banner" class="hidden text-sm"></div>
        @endif

        <header class="m3-top-bar">
            <div class="flex items-start gap-3">
                <div class="m3-top-bar-brand">
                    <span class="material-symbols-outlined filled text-m3-primary">volunteer_activism</span>
                </div>
                <div class="min-w-0 flex-1">
                    @if($subtitle)
                        <p class="text-xs font-medium uppercase tracking-wider text-m3-on-primary/70">{{ $subtitle }}</p>
                    @endif
                    <h1 class="truncate text-lg font-semibold">{{ $userName ?? auth()->user()?->name }}</h1>
                    @if($contextLabel && $contextValue)
                        <p class="mt-1 flex items-center gap-1 truncate text-xs text-m3-on-primary/85">
                            <span class="material-symbols-outlined text-base">location_on</span>
                            <span>{{ $contextLabel }}: <strong class="font-medium">{{ $contextValue }}</strong></span>
                        </p>
                    @endif
                </div>
                @if($profileRoute)
                    <a href="{{ $profileRoute }}" class="m3-btn-text !min-h-0 !px-3 !py-2 !text-m3-on-primary hover:bg-white/10" title="Mi perfil">
                        <span class="material-symbols-outlined text-xl">manage_accounts</span>
                    </a>
                @endif
                @if($logoutRoute)
                    <form method="POST" action="{{ $logoutRoute }}">
                        @csrf
                        <button type="submit" class="m3-btn-text !min-h-0 !px-3 !py-2 !text-m3-on-primary hover:bg-white/10">
                            <span class="material-symbols-outlined text-xl">logout</span>
                        </button>
                    </form>
                @endif
            </div>
        </header>

        <main class="flex-1 px-4 py-4 pb-28">
            {{ $slot }}
        </main>

        @isset($navigation)
            <nav class="m3-nav-bar">
                <x-m3.tricolor-bar class="!h-0.5" />
                <div class="flex px-1 py-1">{{ $navigation }}</div>
            </nav>
        @endisset
    </div>
</x-layouts.m3-base>

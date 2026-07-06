<div class="w-full space-y-6">
    <div class="m3-login-hero">
        <div class="m3-login-icon overflow-hidden p-0">
            <x-visitantes-logo size="h-14 w-14" />
        </div>
        <p class="text-xs font-medium uppercase tracking-wider text-m3-on-primary/80">Visitantes · {{ config('visitantes.estado') }}</p>
        <h1 class="mt-1 text-xl font-semibold">{{ $title }}</h1>
        <p class="mt-1 text-sm text-m3-on-primary/85">{{ $subtitle }}</p>
    </div>

    @if (session('reset_status'))
        <x-m3.banner type="success">{{ session('reset_status') }}</x-m3.banner>
    @endif

    <div class="m3-card space-y-4 !p-6">
        <p class="text-sm text-m3-on-surface-variant">
            Ingrese su correo y le enviaremos un enlace para restablecer su contraseña.
        </p>

        <form wire:submit="sendResetLink" class="space-y-4">
            <x-m3.text-field label="Correo electrónico" icon="mail" type="email"
                wire:model="email" autocomplete="username"
                :error="$errors->first('email')" />

            <x-m3.button icon="mail" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="sendResetLink">Enviar enlace</span>
                <span wire:loading wire:target="sendResetLink">Enviando…</span>
            </x-m3.button>
        </form>

        <a href="{{ $loginRoute }}" class="block text-center text-sm font-medium text-m3-primary hover:underline">
            Volver al inicio de sesión
        </a>
    </div>
</div>

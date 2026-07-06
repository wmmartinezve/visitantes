<div class="w-full space-y-6">
    <div class="m3-login-hero">
        <div class="m3-login-icon overflow-hidden p-0">
            <x-visitantes-logo size="h-14 w-14" />
        </div>
        <p class="text-xs font-medium uppercase tracking-wider text-m3-on-primary/80">Visitantes · {{ config('visitantes.estado') }}</p>
        <h1 class="mt-1 text-xl font-semibold">Centro de Acopio</h1>
        <p class="mt-1 text-sm text-m3-on-primary/85">Gestiona inventario y entregas de insumos.</p>
    </div>

    <div class="m3-card space-y-4 !p-6">
        <form wire:submit="login" class="space-y-4">
            <x-m3.text-field label="Correo electrónico" icon="mail" type="email"
                wire:model="email" autocomplete="username"
                :error="$errors->first('email')" />

            <x-m3.text-field label="Contraseña" icon="lock" type="password"
                wire:model="password" autocomplete="current-password" />

            <x-m3.button icon="login" variant="danger" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="login">Iniciar sesión</span>
                <span wire:loading wire:target="login">Verificando…</span>
            </x-m3.button>
        </form>

        <a href="{{ route('acopio.password.request') }}" class="block text-center text-sm font-medium text-m3-primary hover:underline">
            ¿Olvidó su contraseña?
        </a>
    </div>
</div>

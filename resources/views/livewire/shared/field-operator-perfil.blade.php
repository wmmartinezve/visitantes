<div class="space-y-6">
    @if (session('profile_status'))
        <x-m3.banner type="success">{{ session('profile_status') }}</x-m3.banner>
    @endif

    <x-m3.section-header title="Mi perfil" />

    <div class="m3-card space-y-4 !p-5">
        <p class="text-sm text-m3-on-surface-variant">
            {{ $contextLabel }}: <strong>{{ $contextValue ?? '—' }}</strong>
        </p>

        <form wire:submit="updateProfile" class="space-y-4">
            <x-m3.text-field label="Nombre" icon="person"
                wire:model="profileName"
                :error="$errors->first('profileName')" />

            <x-m3.text-field label="Correo electrónico" icon="mail" type="email"
                wire:model="profileEmail"
                :error="$errors->first('profileEmail')" />

            <x-m3.button icon="save" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="updateProfile">Guardar perfil</span>
                <span wire:loading wire:target="updateProfile">Guardando…</span>
            </x-m3.button>
        </form>
    </div>

    <x-m3.section-header title="Cambiar contraseña" />

    <div class="m3-card space-y-4 !p-5">
        <form wire:submit="updatePassword" class="space-y-4">
            <x-m3.text-field label="Contraseña actual" icon="lock" type="password"
                wire:model="currentPassword"
                :error="$errors->first('currentPassword')" />

            <x-m3.text-field label="Nueva contraseña" icon="lock" type="password"
                wire:model="newPassword"
                :error="$errors->first('newPassword')" />

            <x-m3.text-field label="Confirmar nueva contraseña" icon="lock" type="password"
                wire:model="new_password_confirmation" />

            <x-m3.button icon="key" variant="danger" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="updatePassword">Actualizar contraseña</span>
                <span wire:loading wire:target="updatePassword">Actualizando…</span>
            </x-m3.button>
        </form>
    </div>
</div>

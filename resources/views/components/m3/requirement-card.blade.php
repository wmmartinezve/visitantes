@props(['item', 'cantidad', 'invitado', 'estatus', 'estatusLabel', 'centro' => null, 'icon' => 'inventory_2'])

<div {{ $attributes->merge(['class' => 'm3-requirement-card']) }}>
    <div class="m3-guest-card-accent !w-[3px]" aria-hidden="true">
        <span class="ve-yellow"></span>
        <span class="ve-blue"></span>
        <span class="ve-red"></span>
    </div>
    <div class="min-w-0 flex-1 p-3.5">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0 flex-1">
                <p class="font-semibold text-m3-on-surface">{{ $item }}</p>
                <p class="mt-1 text-sm text-m3-on-surface-variant">{{ $cantidad }} u. · {{ $invitado }}</p>
                @if($centro)
                    <p class="mt-1 flex items-center gap-1 text-xs text-m3-on-surface-variant">
                        <span class="material-symbols-outlined text-sm text-m3-primary">warehouse</span>
                        Centro: {{ $centro }}
                    </p>
                @endif
            </div>
            <x-m3.status-chip :estatus="$estatus" :label="$estatusLabel" />
        </div>
    </div>
</div>

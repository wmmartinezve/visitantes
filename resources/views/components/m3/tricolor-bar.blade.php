@props(['class' => ''])

<div {{ $attributes->merge(['class' => "ve-tricolor-bar {$class}"]) }} aria-hidden="true">
    <span class="ve-yellow"></span>
    <span class="ve-blue"></span>
    <span class="ve-red"></span>
</div>

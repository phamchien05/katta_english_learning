@props(['name' => '', 'size' => 'w-9 h-9 text-sm'])

{{-- Avatar tròn: chữ cái đầu của tên, nền tím --}}
<div {{ $attributes->merge(['class' => "$size rounded-full bg-katta-primary text-white flex items-center justify-center font-semibold uppercase shrink-0"]) }}>
    {{ mb_substr(trim($name), 0, 1) }}
</div>

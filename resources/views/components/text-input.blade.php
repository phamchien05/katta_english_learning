@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-katta-primary focus:ring-katta-primary rounded-xl shadow-sm']) }}>

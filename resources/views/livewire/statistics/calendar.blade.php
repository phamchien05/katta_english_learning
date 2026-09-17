<div class="bg-white rounded-2xl shadow-sm p-5">
    <div class="flex items-center justify-between mb-4">
        <button type="button" wire:click="prevMonth" class="text-gray-400 hover:text-katta-primary transition">
            <i data-lucide="chevron-left" class="w-5 h-5"></i>
        </button>
        <p class="font-semibold text-gray-800">{{ ucfirst($monthLabel) }}</p>
        <button type="button" wire:click="nextMonth" class="text-gray-400 hover:text-katta-primary transition">
            <i data-lucide="chevron-right" class="w-5 h-5"></i>
        </button>
    </div>

    <div class="grid grid-cols-7 gap-y-2 text-center">
        @foreach (__('statistics.weekdays') as $day)
            <span class="text-xs font-semibold text-gray-400">{{ $day }}</span>
        @endforeach

        @for ($i = 0; $i < $leadingBlanks; $i++)
            <span></span>
        @endfor

        @for ($day = 1; $day <= $daysInMonth; $day++)
            @php
                $isActive = in_array($day, $this->activeDays, true);
                $isToday = $isCurrentMonth && $day === $todayDay;
            @endphp
            <div class="flex items-center justify-center">
                <span class="w-8 h-8 flex items-center justify-center rounded-full text-sm transition
                             {{ $isActive ? 'bg-katta-primary text-white font-semibold' : ($isToday ? 'ring-2 ring-katta-primary text-katta-primary font-semibold' : 'text-gray-600') }}">
                    {{ $day }}
                </span>
            </div>
        @endfor
    </div>
</div>

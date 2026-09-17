{{--
    Component câu hỏi quiz dùng chung (mục 17) - tái sử dụng cho Từ vựng / Nghe / Đọc / Ngữ pháp luyện tập.
    Props:
      - type: 'fill' (điền đáp án) | 'mcq' (trắc nghiệm 1 đáp án) | 'boolean' (đúng/sai) | 'multi' (chọn nhiều đáp án)
      - model: tên property Livewire để wire:model. Với 'multi', nhiều checkbox cùng dùng 1 model
        và Livewire tự gom thành mảng (không cần code JS thủ công).
      - options: mảng đáp án cho type=mcq/multi (giá trị mỗi lựa chọn dùng chính text của nó)
      - placeholder: placeholder cho type=fill
--}}
@props([
    'type' => 'fill',
    'model',
    'options' => [],
    'placeholder' => '',
    'trueLabel' => 'True',
    'falseLabel' => 'False',
    'enterAction' => null, // tên method Livewire gọi khi nhấn Enter (vd: 'submitAnswer'), null = không làm gì
    'selected' => [], // cho type=multi: mảng giá trị đã chọn (server tự tính checked, không dựa wire:model tự động)
    'questionId' => null, // cho type=multi: id câu hỏi, truyền vào toggleAction
    'toggleAction' => null, // cho type=multi: tên method Livewire nhận (questionId, option)
])

@if ($type === 'fill')
    <input type="text" wire:model.live.debounce.150ms="{{ $model }}" placeholder="{{ $placeholder }}"
           @if ($enterAction) wire:keydown.enter.prevent="{{ $enterAction }}" @endif
           {{ $attributes->merge(['class' => 'w-full rounded-xl border-gray-300 focus:border-katta-primary focus:ring-katta-primary text-lg']) }}>
@elseif ($type === 'mcq')
    <div {{ $attributes->merge(['class' => 'space-y-2']) }}>
        @foreach ($options as $i => $option)
            <label wire:key="{{ $model }}-opt-{{ $i }}" class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 cursor-pointer transition
                           has-[:checked]:border-katta-primary has-[:checked]:bg-katta-bg hover:bg-gray-50">
                <input type="radio" wire:model.live="{{ $model }}" value="{{ $option }}" class="text-katta-primary focus:ring-katta-primary">
                <span class="text-sm text-gray-700">{{ $option }}</span>
            </label>
        @endforeach
    </div>
@elseif ($type === 'multi')
    {{--
        Không dùng wire:model tự động gom checkbox thành mảng (gặp lỗi chọn 1 thành chọn hết trên thực tế) -
        tự quản lý rõ ràng: server tính checked từ $selected, bấm gọi thẳng $toggleAction($questionId, $option).
    --}}
    <div {{ $attributes->merge(['class' => 'space-y-2']) }}>
        @foreach ($options as $i => $option)
            <label wire:key="{{ $model }}-opt-{{ $i }}" wire:click="{{ $toggleAction }}({{ $questionId }}, @js($option))"
                   class="flex items-center gap-3 p-3 rounded-xl border cursor-pointer transition
                          {{ in_array($option, $selected) ? 'border-katta-primary bg-katta-bg' : 'border-gray-200 hover:bg-gray-50' }}">
                <input type="checkbox" @checked(in_array($option, $selected)) class="rounded text-katta-primary focus:ring-katta-primary pointer-events-none">
                <span class="text-sm text-gray-700">{{ $option }}</span>
            </label>
        @endforeach
    </div>
@elseif ($type === 'boolean')
    <div {{ $attributes->merge(['class' => 'flex gap-3']) }}>
        <label class="flex-1 text-center py-3 rounded-xl border border-gray-200 cursor-pointer font-semibold text-sm transition
                       has-[:checked]:bg-katta-primary has-[:checked]:text-white has-[:checked]:border-katta-primary">
            {{-- value cố định "True"/"False" để khớp correct_answer đã lưu; trueLabel/falseLabel chỉ để hiển thị --}}
            <input type="radio" wire:model.live="{{ $model }}" value="True" class="hidden">
            {{ $trueLabel }}
        </label>
        <label class="flex-1 text-center py-3 rounded-xl border border-gray-200 cursor-pointer font-semibold text-sm transition
                       has-[:checked]:bg-katta-accent has-[:checked]:text-white has-[:checked]:border-katta-accent">
            <input type="radio" wire:model.live="{{ $model }}" value="False" class="hidden">
            {{ $falseLabel }}
        </label>
    </div>
@endif

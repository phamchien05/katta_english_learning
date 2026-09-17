<div x-data
     x-on:passage-picked.window="
        fetch('{{ route('translate.replenish') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            },
            body: JSON.stringify({ level: $event.detail.level, direction: $event.detail.direction }),
        });
     ">
    {{-- Bù kho: mỗi lần lấy 1 đoạn ra làm, âm thầm gọi Gemini sinh 1 đoạn mới thêm vào kho ở trên (không chặn UI) --}}

    {{-- Header phụ: quay lại + nút nộp bài --}}
    <div class="flex items-center justify-between mb-4">
        <a href="{{ route('translate.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-400 hover:text-katta-primary">
            {{ __('translate.back_to_levels') }}
        </a>

        <div class="flex items-center gap-3">
            @if ($graded)
                <button type="button" wire:click="shuffle"
                    class="px-4 py-2 rounded-xl text-sm font-semibold bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 transition">
                    {{ __('translate.try_again') }}
                </button>
            @else
                <button type="button" wire:click="submitForGrading" @disabled(trim($userTranslation) === '')
                    class="px-5 py-2.5 rounded-xl text-sm font-semibold transition
                        {{ trim($userTranslation) === '' ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-katta-primary text-white hover:bg-purple-700' }}">
                    {{ __('translate.submit_for_grading') }}
                </button>
            @endif
        </div>
    </div>

    @if ($this->passage)
        {{-- 2 cột: văn bản nguồn / bản dịch của bạn --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-2xl shadow-sm p-6">
                <p class="text-xs font-bold tracking-widest text-gray-400 uppercase mb-3">{{ __('translate.source_text') }}</p>
                <p class="text-gray-800 leading-relaxed whitespace-pre-line">{{ $this->passage->source_text }}</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm p-6">
                <p class="text-xs font-bold tracking-widest text-gray-400 uppercase mb-3">{{ __('translate.your_translation') }}</p>
                <textarea wire:model.live.debounce.400ms="userTranslation" @disabled($graded) rows="10"
                    placeholder="{{ __('translate.input_placeholder') }}"
                    class="w-full h-full min-h-[220px] border-0 focus:ring-0 resize-none text-gray-800 placeholder:text-gray-300 disabled:bg-transparent disabled:text-gray-500"></textarea>
            </div>
        </div>

        {{-- Kết quả chấm điểm --}}
        @if ($graded)
            <div class="mt-6 bg-white rounded-2xl shadow-sm p-6">
                <div class="flex items-center justify-between mb-3">
                    <p class="font-semibold text-gray-700">{{ __('translate.result_title') }}</p>
                    <span class="text-2xl font-extrabold {{ $score >= 70 ? 'text-emerald-600' : ($score >= 40 ? 'text-amber-500' : 'text-katta-accent') }}">
                        {{ $score }}<span class="text-sm text-gray-400 font-medium">/100</span>
                    </span>
                </div>
                @if ($feedback)
                    <p class="text-sm text-gray-600 leading-relaxed">{{ $feedback }}</p>
                @endif

                @if ($referenceTranslation)
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-xs font-bold tracking-widest text-gray-400 uppercase mb-2">{{ __('translate.reference_translation_label') }}</p>
                        <p class="text-sm text-gray-700 leading-relaxed bg-katta-bg rounded-xl p-3">{{ $referenceTranslation }}</p>
                    </div>
                @endif

                @if (! empty($issues))
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <p class="text-xs font-bold tracking-widest text-gray-400 uppercase mb-2">{{ __('translate.issues_label') }}</p>
                        <ul class="space-y-2">
                            @foreach ($issues as $issue)
                                <li class="flex items-start gap-2 text-sm text-gray-700 bg-rose-50 border-l-4 border-katta-accent rounded-lg p-3">
                                    <i data-lucide="x-circle" class="w-4 h-4 text-katta-accent shrink-0 mt-0.5"></i>
                                    <span>{{ $issue }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif
    @else
        <div class="bg-white rounded-2xl shadow-sm p-10 text-center text-gray-400">
            {{ __('Chưa có đoạn văn nào cho cấp độ này.') }}
        </div>
    @endif
</div>

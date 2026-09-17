<div
     x-data="{
        answers: @js($graded ? $answers : (object) []),
        answeredCount() {
            return Object.values(this.answers).filter(a =>
                Array.isArray(a) ? a.length > 0 : (a !== undefined && a !== null && String(a).trim() !== '')
            ).length;
        },
     }"
     x-on:set-picked.window="
        fetch('{{ route('grammar.practice.replenish') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            },
            body: JSON.stringify({ topic: $event.detail.topic }),
        });
     ">
    {{-- Bù kho: ngay khi lấy 1 bộ đề ra làm, âm thầm gọi Gemini sinh 1 bộ mới thêm vào kho (không chặn UI) --}}

    @if ($this->set)
        @unless ($graded)
            <div class="flex justify-end mb-3">
                <span class="text-xs text-gray-400" x-text="answeredCount() + '/{{ $this->totalQuestions }} answered'"></span>
            </div>
        @endunless

        <div class="space-y-4">
            @foreach ($this->set->questions as $i => $q)
                <div wire:key="q-{{ $q->id }}"
                     class="bg-white rounded-2xl shadow-sm p-5
                            {{ $graded ? ($correctness[$q->id] ? 'border-l-4 border-emerald-500' : 'border-l-4 border-katta-accent') : '' }}">
                    <p class="font-semibold text-gray-800 mb-3">{{ $i + 1 }}. {{ $q->question }}</p>

                    {{-- Toàn bộ input dùng Alpine (x-model) giữ trạng thái ở trình duyệt, KHÔNG gửi lên
                         server cho tới khi nộp bài - tránh nhiều request rời rạc đụng độ ghi đè nhau
                         (đúng pattern đã ổn định ở Đọc hiểu). --}}
                    @if ($q->type === 'fill')
                        <input type="text" x-model="answers[{{ $q->id }}]" :disabled="@js($graded)"
                               placeholder="{{ __('grammar.fill_placeholder') }}"
                               class="w-full rounded-xl border-gray-300 focus:border-katta-primary focus:ring-katta-primary text-lg disabled:bg-gray-50">
                    @elseif ($q->type === 'mcq')
                        <div class="space-y-2">
                            @foreach ($q->options as $option)
                                <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 cursor-pointer transition"
                                       :class="answers[{{ $q->id }}] === @js($option) ? 'border-katta-primary bg-katta-bg' : 'hover:bg-gray-50'">
                                    <input type="radio" x-model="answers[{{ $q->id }}]" value="{{ $option }}"
                                           class="text-katta-primary focus:ring-katta-primary" :disabled="@js($graded)">
                                    <span class="text-sm text-gray-700">{{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                    @elseif ($q->type === 'multi')
                        <p class="text-xs text-gray-400 mb-2">{{ __('grammar.select_all_that_apply') }}</p>
                        <div class="space-y-2" x-init="answers[{{ $q->id }}] = answers[{{ $q->id }}] || []">
                            @foreach ($q->options as $option)
                                <label class="flex items-center gap-3 p-3 rounded-xl border border-gray-200 cursor-pointer transition"
                                       :class="(answers[{{ $q->id }}] || []).includes(@js($option)) ? 'border-katta-primary bg-katta-bg' : 'hover:bg-gray-50'">
                                    <input type="checkbox" x-model="answers[{{ $q->id }}]" value="{{ $option }}"
                                           class="rounded text-katta-primary focus:ring-katta-primary" :disabled="@js($graded)">
                                    <span class="text-sm text-gray-700">{{ $option }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif

                    @if ($graded)
                        <div class="mt-3 pt-3 border-t border-gray-100 text-sm">
                            @if ($correctness[$q->id])
                                <p class="text-emerald-600 font-semibold flex items-center gap-1">
                                    <i data-lucide="check-circle" class="w-4 h-4"></i> {{ __('grammar.correct') }}!
                                </p>
                            @else
                                <p class="text-katta-accent font-semibold flex items-center gap-1 mb-1">
                                    <i data-lucide="x-circle" class="w-4 h-4"></i> {{ __('grammar.incorrect') }}
                                </p>
                                <p class="text-gray-500">
                                    {{ __('grammar.correct_answer_label') }}
                                    <span class="text-gray-700 font-medium">{{ implode(', ', $q->correct_answer) }}</span>
                                </p>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach

            {{-- Nút nộp bài / kết quả --}}
            @if (! $graded)
                <button type="button" @click="$wire.submitAnswers(answers)" :disabled="answeredCount() < {{ $this->totalQuestions }}"
                    class="w-full py-3 rounded-xl text-sm font-semibold transition sticky bottom-4"
                    :class="answeredCount() < {{ $this->totalQuestions }} ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-katta-accent text-white hover:bg-rose-700'">
                    {{ __('grammar.submit_button') }} (<span x-text="answeredCount()"></span>/{{ $this->totalQuestions }})
                </button>
            @else
                <div class="bg-white rounded-2xl shadow-sm p-4 flex items-center justify-between sticky bottom-4">
                    <p class="font-bold text-gray-800">{{ $score }}/{{ $this->totalQuestions }}</p>
                    <a href="{{ route('grammar.practice') }}"
                       class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-emerald-600 text-white hover:bg-emerald-700 transition">
                        {{ __('grammar.back_to_topics') }}
                    </a>
                </div>
            @endif
        </div>
    @endif
</div>

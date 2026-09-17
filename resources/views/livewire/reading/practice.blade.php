<div
     x-data="{
        answers: {},
        answeredCount() {
            return Object.values(this.answers).filter(a =>
                Array.isArray(a) ? a.length > 0 : (a !== undefined && a !== null && String(a).trim() !== '')
            ).length;
        },
     }"
     x-on:passage-picked.window="
        fetch('{{ route('reading.replenish') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            },
            body: JSON.stringify({ topic: $event.detail.topic, level: $event.detail.level }),
        });
     ">
    {{-- Bù kho: mỗi lần vào làm 1 bài, âm thầm gọi Gemini sinh 1 bài mới thêm vào kho (không chặn UI) --}}

    @if ($this->passage)
        {{-- Header phụ --}}
        <div class="flex items-center justify-between mb-4">
            <a href="{{ route('reading.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-400 hover:text-katta-primary">
                {{ __('reading.back_to_passages') }}
            </a>
            @unless ($graded)
                <span class="text-xs text-gray-400" x-text="answeredCount() + '/{{ $this->totalQuestions }} answered'"></span>
            @endunless
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
            {{-- Cột trái: bài đọc --}}
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden lg:sticky lg:top-6">
                <div class="bg-gradient-to-br from-indigo-900 to-katta-primary px-6 py-5">
                    <p class="text-[11px] font-bold tracking-widest text-white/70 uppercase">
                        {{ __('reading.topics.' . $this->passage->topic) }}
                    </p>
                    <h1 class="text-xl font-bold text-white mt-1">{{ $this->passage->title }}</h1>
                </div>
                <div class="p-6">
                    <p class="text-gray-700 leading-relaxed whitespace-pre-line">{{ $this->passage->content }}</p>
                </div>
            </div>

            {{-- Cột phải: câu hỏi --}}
            <div class="space-y-3">
                @foreach ($this->passage->questions as $i => $q)
                    <div wire:key="q-{{ $q->id }}"
                         class="bg-white rounded-2xl shadow-sm p-5
                                {{ $graded ? ($correctness[$q->id] ? 'border-l-4 border-emerald-500' : 'border-l-4 border-katta-accent') : '' }}">
                        <p class="font-semibold text-gray-800 mb-3">{{ $i + 1 }}. {{ $q->question }}</p>

                        {{-- Toàn bộ input dùng Alpine (x-model) giữ trạng thái ở trình duyệt, KHÔNG gửi lên
                             server cho tới khi nộp bài - tránh nhiều request rời rạc đụng độ ghi đè nhau. --}}
                        @if ($q->type === 'fill')
                            <input type="text" x-model="answers[{{ $q->id }}]" :disabled="@js($graded)"
                                   placeholder="{{ __('reading.fill_placeholder') }}"
                                   class="w-full rounded-xl border-gray-300 focus:border-katta-primary focus:ring-katta-primary text-lg disabled:bg-gray-50">
                        @elseif ($q->type === 'boolean')
                            <div class="flex gap-3">
                                <label class="flex-1 text-center py-3 rounded-xl border border-gray-200 cursor-pointer font-semibold text-sm transition"
                                       :class="answers[{{ $q->id }}] === 'True' ? 'bg-katta-primary text-white border-katta-primary' : ''">
                                    <input type="radio" x-model="answers[{{ $q->id }}]" value="True" class="hidden" :disabled="@js($graded)">
                                    {{ __('reading.true') }}
                                </label>
                                <label class="flex-1 text-center py-3 rounded-xl border border-gray-200 cursor-pointer font-semibold text-sm transition"
                                       :class="answers[{{ $q->id }}] === 'False' ? 'bg-katta-accent text-white border-katta-accent' : ''">
                                    <input type="radio" x-model="answers[{{ $q->id }}]" value="False" class="hidden" :disabled="@js($graded)">
                                    {{ __('reading.false') }}
                                </label>
                            </div>
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
                                        <i data-lucide="check-circle" class="w-4 h-4"></i> {{ __('reading.correct') }}!
                                    </p>
                                @else
                                    <p class="text-katta-accent font-semibold flex items-center gap-1 mb-1">
                                        <i data-lucide="x-circle" class="w-4 h-4"></i> {{ __('reading.incorrect') }}
                                    </p>
                                    <p class="text-gray-500">
                                        {{ __('reading.correct_answer_label') }}
                                        <span class="text-gray-700 font-medium">{{ implode(', ', $q->correct_answer) }}</span>
                                    </p>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach

                {{-- Nút nộp bài / bài tiếp theo --}}
                @if (! $graded)
                    <button type="button" @click="$wire.submitAnswers(answers)" :disabled="answeredCount() < {{ $this->totalQuestions }}"
                        class="w-full py-3 rounded-xl text-sm font-semibold transition sticky bottom-4"
                        :class="answeredCount() < {{ $this->totalQuestions }} ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-katta-accent text-white hover:bg-rose-700'">
                        {{ __('reading.submit_button') }} (<span x-text="answeredCount()"></span>/{{ $this->totalQuestions }})
                    </button>
                @else
                    <div class="bg-white rounded-2xl shadow-sm p-4 flex items-center justify-between sticky bottom-4">
                        <p class="font-bold text-gray-800">{{ $score }}/{{ $this->totalQuestions }}</p>
                        <button type="button" wire:click="nextArticle"
                            class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-emerald-600 text-white hover:bg-emerald-700 transition">
                            {{ __('reading.next_article') }}
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

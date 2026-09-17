<div
     x-data="{
        answers: {},
        playing: false,
        loading: false,
        rate: 1,
        answeredCount() {
            return Object.values(this.answers).filter(a =>
                Array.isArray(a) ? a.length > 0 : (a !== undefined && a !== null && String(a).trim() !== '')
            ).length;
        },
        // Transcript CHỈ được lấy về đúng lúc bấm Play (không nhúng sẵn vào HTML ban đầu) - giữ đúng
        // tinh thần 'chỉ nghe, không đọc được chữ trước khi nộp bài'. Server đếm lượt nghe, trả về
        // allowed=false khi đã hết lượt (khó gian lận hơn đếm ở client).
        async play() {
            if (this.playing || this.loading) return;
            if (!window.speechSynthesis) { alert(@js(__('listening.unsupported_browser'))); return; }
            this.loading = true;
            const result = await $wire.getTranscript();
            this.loading = false;
            if (!result.allowed) return;
            window.speechSynthesis.cancel();
            // Bỏ nhãn 'Tên người nói:' đầu mỗi dòng để giọng đọc không đọc luôn cả nhãn đó ra
            const spoken = result.text.replace(/^[A-Za-z][A-Za-z '.-]*:\s*/gm, '');
            const utter = new SpeechSynthesisUtterance(spoken);
            utter.lang = 'en-US';
            utter.rate = this.rate;
            utter.onstart = () => { this.playing = true; };
            utter.onend = () => { this.playing = false; };
            utter.onerror = () => { this.playing = false; };
            window.speechSynthesis.speak(utter);
        },
        stop() {
            window.speechSynthesis.cancel();
            this.playing = false;
        },
     }"
     x-on:passage-picked.window="
        fetch('{{ route('listening.replenish') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            },
            body: JSON.stringify({ topic: $event.detail.topic, level: $event.detail.level }),
        });
     "
     x-cloak
>
    {{-- Bù kho: mỗi lần vào làm 1 bài, âm thầm gọi Gemini sinh 1 bài mới thêm vào kho (không chặn UI) --}}

    @if ($this->passage)
        {{-- Header phụ --}}
        <div class="flex items-center justify-between mb-4">
            <a href="{{ route('listening.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-400 hover:text-katta-primary">
                {{ __('listening.back_to_passages') }}
            </a>
            @unless ($graded)
                <span class="text-xs text-gray-400" x-text="answeredCount() + '/{{ $this->totalQuestions }} answered'"></span>
            @endunless
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
            {{-- Cột trái: trình phát audio --}}
            <div class="bg-white rounded-2xl shadow-sm overflow-hidden lg:sticky lg:top-6">
                <div class="bg-gradient-to-br from-indigo-900 to-katta-primary px-6 py-5">
                    <p class="text-[11px] font-bold tracking-widest text-white/70 uppercase">
                        {{ __('listening.topics.' . $this->passage->topic) }}
                    </p>
                    <h1 class="text-xl font-bold text-white mt-1">{{ $this->passage->title }}</h1>
                </div>

                <div class="p-6">
                    {{-- Nút play/pause lớn ở giữa - server (không phải Alpine) đếm số lượt nghe còn lại --}}
                    @php $canPlay = $graded || $playCount < \App\Livewire\Listening\Practice::MAX_PLAYS; @endphp
                    <div class="flex flex-col items-center py-4">
                        <button type="button" @click="playing ? stop() : play()" :disabled="{{ $canPlay ? 'false' : '!playing' }}"
                                class="w-20 h-20 rounded-full flex items-center justify-center transition shrink-0"
                                :class="loading ? 'bg-gray-100 text-gray-400' : '{{ $canPlay ? 'bg-katta-primary text-white hover:bg-indigo-700' : 'bg-gray-100 text-gray-300 cursor-not-allowed' }}'">
                            {{-- 2 icon tĩnh, ẩn/hiện qua x-show - lucide.createIcons() thay <i> bằng <svg> ngay khi
                                 load nên không thể đổi data-lucide động sau đó (phần tử <i> đã bị thay thế) --}}
                            <i data-lucide="play" class="w-8 h-8" x-show="!playing && !loading"></i>
                            <i data-lucide="pause" class="w-8 h-8" x-show="playing" x-cloak></i>
                            <i data-lucide="loader" class="w-8 h-8 animate-spin" x-show="loading" x-cloak></i>
                        </button>
                        @if (! $canPlay)
                            <p class="text-sm text-gray-500 mt-3">{{ __('listening.plays_used_up') }}</p>
                        @else
                            <p class="text-sm text-gray-500 mt-3">
                                {{ __('listening.plays_remaining_label') }} {{ \App\Livewire\Listening\Practice::MAX_PLAYS - $playCount }}
                            </p>
                        @endif
                    </div>

                    {{-- Chỉnh tốc độ đọc --}}
                    <div class="flex items-center justify-center gap-2 mt-2">
                        <span class="text-xs text-gray-400 mr-1">{{ __('listening.speed_label') }}:</span>
                        @foreach ([0.75, 1, 1.25] as $speed)
                            <button type="button" @click="rate = {{ $speed }}"
                                    class="px-3 py-1 rounded-full text-xs font-semibold transition"
                                    :class="rate === {{ $speed }} ? 'bg-katta-primary text-white' : 'bg-gray-100 text-gray-500 hover:bg-gray-200'">
                                {{ $speed }}x
                            </button>
                        @endforeach
                    </div>

                    @if (! $graded)
                        <p class="text-xs text-gray-400 text-center mt-4">{{ __('listening.transcript_hint') }}</p>
                    @else
                        <div class="mt-6 pt-6 border-t border-gray-100">
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wide mb-2">{{ __('listening.transcript_heading') }}</p>
                            <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-line">{{ $this->passage->transcript }}</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Cột phải: câu hỏi --}}
            <div class="space-y-3">
                @foreach ($this->passage->questions as $i => $q)
                    <div wire:key="q-{{ $q->id }}"
                         class="bg-white rounded-2xl shadow-sm p-5
                                {{ $graded ? ($correctness[$q->id] ? 'border-l-4 border-emerald-500' : 'border-l-4 border-katta-accent') : '' }}">
                        <p class="font-semibold text-gray-800 mb-3">{{ $i + 1 }}. {{ $q->question }}</p>

                        @if ($q->type === 'fill')
                            <input type="text" x-model="answers[{{ $q->id }}]" :disabled="@js($graded)"
                                   placeholder="{{ __('listening.fill_placeholder') }}"
                                   class="w-full rounded-xl border-gray-300 focus:border-katta-primary focus:ring-katta-primary text-lg disabled:bg-gray-50">
                        @elseif ($q->type === 'boolean')
                            <div class="flex gap-3">
                                <label class="flex-1 text-center py-3 rounded-xl border border-gray-200 cursor-pointer font-semibold text-sm transition"
                                       :class="answers[{{ $q->id }}] === 'True' ? 'bg-katta-primary text-white border-katta-primary' : ''">
                                    <input type="radio" x-model="answers[{{ $q->id }}]" value="True" class="hidden" :disabled="@js($graded)">
                                    {{ __('listening.true') }}
                                </label>
                                <label class="flex-1 text-center py-3 rounded-xl border border-gray-200 cursor-pointer font-semibold text-sm transition"
                                       :class="answers[{{ $q->id }}] === 'False' ? 'bg-katta-accent text-white border-katta-accent' : ''">
                                    <input type="radio" x-model="answers[{{ $q->id }}]" value="False" class="hidden" :disabled="@js($graded)">
                                    {{ __('listening.false') }}
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
                                        <i data-lucide="check-circle" class="w-4 h-4"></i> {{ __('listening.correct') }}!
                                    </p>
                                @else
                                    <p class="text-katta-accent font-semibold flex items-center gap-1 mb-1">
                                        <i data-lucide="x-circle" class="w-4 h-4"></i> {{ __('listening.incorrect') }}
                                    </p>
                                    <p class="text-gray-500">
                                        {{ __('listening.correct_answer_label') }}
                                        <span class="text-gray-700 font-medium">{{ implode(', ', $q->correct_answer) }}</span>
                                    </p>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach

                {{-- Nút nộp bài / bài tiếp theo --}}
                @if (! $graded)
                    <button type="button" @click="stop(); $wire.submitAnswers(answers)" :disabled="answeredCount() < {{ $this->totalQuestions }}"
                        class="w-full py-3 rounded-xl text-sm font-semibold transition sticky bottom-4"
                        :class="answeredCount() < {{ $this->totalQuestions }} ? 'bg-gray-200 text-gray-400 cursor-not-allowed' : 'bg-katta-accent text-white hover:bg-rose-700'">
                        {{ __('listening.submit_button') }} (<span x-text="answeredCount()"></span>/{{ $this->totalQuestions }})
                    </button>
                @else
                    <div class="bg-white rounded-2xl shadow-sm p-4 flex items-center justify-between sticky bottom-4">
                        <p class="font-bold text-gray-800">{{ $score }}/{{ $this->totalQuestions }}</p>
                        <button type="button" wire:click="nextArticle"
                            class="px-5 py-2.5 rounded-xl text-sm font-semibold bg-emerald-600 text-white hover:bg-emerald-700 transition">
                            {{ __('listening.next_article') }}
                        </button>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

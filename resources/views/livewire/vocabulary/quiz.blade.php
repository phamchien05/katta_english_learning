<div>
@if (! $finished)
    {{-- ===== Đang làm bài kiểm tra ===== --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Cột trái: lưới 50 câu hỏi --}}
        <div class="lg:col-span-1 bg-white rounded-2xl shadow-sm p-5">
            <div class="grid grid-cols-5 gap-2">
                @foreach ($questionIds as $i => $vocabId)
                    <button type="button" wire:click="selectQuestion({{ $i }})" wire:key="cell-{{ $i }}"
                        class="aspect-square rounded-xl flex items-center justify-center text-sm font-semibold transition
                            {{ $i === $currentIndex
                                ? 'ring-2 ring-katta-primary text-katta-primary bg-white'
                                : (isset($answers[$vocabId])
                                    ? 'bg-blue-700 text-white'
                                    : 'bg-gray-100 text-gray-400 hover:bg-gray-200') }}">
                        {{ $i + 1 }}
                    </button>
                @endforeach
            </div>

            <button type="button" wire:click="finishTest" @disabled(! $this->allAnswered)
                class="w-full mt-4 py-3 rounded-xl text-sm font-semibold transition
                    {{ $this->allAnswered ? 'bg-katta-primary text-white hover:bg-purple-700' : 'bg-gray-200 text-gray-400 cursor-not-allowed' }}">
                {{ $this->allAnswered ? __('vocabulary.submit_test') : __('vocabulary.please_answer_all') }}
            </button>
        </div>

        {{-- Cột phải: câu hỏi hiện tại --}}
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm p-8">
            <div class="text-center">
                <h2 class="text-xl font-bold text-gray-800">{{ __('vocabulary.quiz_title', ['level' => $level]) }}</h2>
                <p class="text-gray-400 text-sm mt-1">
                    {{ __('vocabulary.question_progress', ['current' => $currentIndex + 1, 'total' => count($questionIds)]) }}
                </p>

                @if ($this->currentVocabulary)
                    <h3 class="text-4xl font-extrabold text-gray-900 mt-6">{{ $this->currentVocabulary->word }}</h3>

                    <div class="flex items-center justify-center gap-3 mt-3">
                        @if ($this->currentVocabulary->part_of_speech)
                            <span class="px-3 py-1 rounded-full bg-katta-bg text-katta-primary text-xs font-semibold">
                                {{ $this->currentVocabulary->part_of_speech }}
                            </span>
                        @endif
                        @if ($this->currentVocabulary->ipa)
                            <span class="text-gray-400 italic">{{ $this->currentVocabulary->ipa }}</span>
                        @endif
                    </div>

                    <div class="max-w-md mx-auto mt-6" wire:key="input-wrap-{{ $currentIndex }}">
                        <x-quiz-question type="fill" model="currentInput" enterAction="submitAnswer"
                                         :placeholder="__('vocabulary.input_placeholder')" />
                    </div>

                    <button type="button" wire:click="submitAnswer"
                        class="max-w-md w-full mx-auto mt-6 py-3 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 transition block">
                        {{ __('vocabulary.submit_next') }}
                    </button>
                @endif
            </div>
        </div>
    </div>
@else
    {{-- ===== Kết quả bài kiểm tra ===== --}}
    <div class="text-center">
        <h2 class="text-xl font-bold text-gray-800">{{ __('vocabulary.result_title', ['level' => $level]) }}</h2>
        <p class="text-5xl font-extrabold text-gray-900 mt-3">{{ $score }}/{{ count($questionIds) }}</p>

        <div class="max-w-xl mx-auto mt-6 bg-white rounded-2xl shadow-sm p-5">
            <div class="grid grid-cols-5 sm:grid-cols-10 gap-2">
                @foreach ($results as $i => $r)
                    <div wire:key="result-cell-{{ $i }}"
                        class="aspect-square rounded-xl flex items-center justify-center text-sm font-semibold text-white
                            {{ $r['is_correct'] ? 'bg-emerald-500' : 'bg-rose-500' }}">
                        {{ $i + 1 }}
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Danh sách chi tiết từng câu --}}
    <div class="max-w-2xl mx-auto mt-6 space-y-3">
        @foreach ($results as $i => $r)
            <div wire:key="result-detail-{{ $i }}" class="rounded-xl p-4 border-l-4 bg-rose-50 border-katta-accent">
                <p class="font-semibold text-gray-800">{{ $i + 1 }}. {{ $r['word'] }}</p>
                @if ($r['ipa'])
                    <p class="text-sm text-gray-400 italic">{{ $r['ipa'] }}</p>
                @endif
                <p class="text-sm mt-1">
                    <span class="font-medium text-gray-600">{{ __('vocabulary.your_answer') }}</span>
                    <span class="{{ $r['is_correct'] ? 'text-emerald-600 font-semibold' : 'text-katta-accent font-semibold' }}">
                        {{ $r['user_answer'] !== '' ? $r['user_answer'] : '—' }}
                    </span>
                </p>
                <p class="text-sm text-gray-700">{{ $r['correct_answer'] }}</p>
            </div>
        @endforeach
    </div>
@endif
</div>

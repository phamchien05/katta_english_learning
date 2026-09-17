<x-app-layout :title="$title" :icon="$icon">
    <div class="max-w-3xl mx-auto mt-6">
        <a href="{{ route('home') }}" class="text-sm text-gray-400 hover:text-katta-primary transition">
            {{ __('grammar.theory_back_breadcrumb') }}
        </a>

        <h1 class="text-2xl font-bold text-gray-800 text-center mt-4 mb-6">
            {{ __('grammar.quiz_title', ['topic' => __('grammar.practice_topics.' . $topicKey . '.title')]) }}
        </h1>

        @livewire('grammar.quiz', ['setId' => $setId])
    </div>
</x-app-layout>

<x-app-layout :title="$title" :icon="$icon">
    <div class="mt-4">
        <a href="{{ route('vocabulary.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-400 hover:text-katta-primary mb-4">
            {{ __('vocabulary.back_to_levels') }}
        </a>

        <livewire:vocabulary.quiz :level="$level" :key="'vocab-quiz-'.$level" />
    </div>
</x-app-layout>

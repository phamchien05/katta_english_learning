<x-app-layout :title="$title" :icon="$icon">
    <div class="max-w-2xl mx-auto mt-6">
        <h1 class="text-2xl font-bold text-gray-800">{{ __('settings.page_title') }}</h1>
        <p class="text-gray-400 mt-1">{{ __('settings.page_subtitle') }}</p>
        <p class="text-sm text-gray-400 mt-2">
            {!! __('settings.profile_hint', ['link' => '<a href="' . route('profile') . '" class="text-katta-primary font-semibold hover:underline">' . __('settings.profile_link_text') . '</a>']) !!}
        </p>

        <div class="mt-6">
            <livewire:settings.form />
        </div>
    </div>
</x-app-layout>

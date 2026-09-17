<form wire:submit="save" class="space-y-6">
    {{-- Ngôn ngữ hiển thị --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <h2 class="font-semibold text-gray-800">{{ __('settings.section_language') }}</h2>
        <p class="text-xs text-gray-400 mt-1 mb-4">{{ __('settings.language_hint') }}</p>

        <div class="grid grid-cols-2 gap-3 max-w-sm">
            @foreach (['en' => 'settings.lang_en', 'vi' => 'settings.lang_vi'] as $value => $labelKey)
                <label class="flex items-center justify-center gap-2 p-3 rounded-xl border cursor-pointer transition font-semibold text-sm
                              {{ $locale === $value ? 'border-katta-primary bg-katta-bg text-katta-primary' : 'border-gray-200 text-gray-500 hover:bg-gray-50' }}">
                    <input type="radio" wire:model="locale" value="{{ $value }}" class="hidden">
                    {{ __($labelKey) }}
                </label>
            @endforeach
        </div>
    </div>

    {{-- API key Gemini riêng --}}
    <div class="bg-white rounded-2xl shadow-sm p-6">
        <div class="flex items-center justify-between flex-wrap gap-2">
            <h2 class="font-semibold text-gray-800">{{ __('settings.section_api_key') }}</h2>
            <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $hasSavedKey ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500' }}">
                {{ $hasSavedKey ? __('settings.api_key_saved_badge') : __('settings.api_key_none_badge') }}
            </span>
        </div>
        <p class="text-xs text-gray-400 mt-1 mb-4">{{ __('settings.api_key_hint') }}</p>

        <label class="block text-xs font-bold text-gray-400 uppercase tracking-wide mb-1.5">
            {{ __('settings.api_key_label') }}
        </label>
        <div class="flex flex-col sm:flex-row gap-2">
            <input type="password" wire:model="geminiApiKey" autocomplete="off"
                   placeholder="{{ $hasSavedKey ? __('settings.api_key_placeholder_saved') : __('settings.api_key_placeholder_new') }}"
                   class="flex-1 rounded-xl border-gray-300 focus:border-katta-primary focus:ring-katta-primary text-sm">
            @if ($hasSavedKey)
                <button type="button" wire:click="removeKey" wire:confirm="{{ __('settings.remove_key_button') }}?"
                        class="shrink-0 px-4 py-2.5 rounded-xl text-sm font-semibold border border-katta-accent text-katta-accent hover:bg-rose-50 transition">
                    {{ __('settings.remove_key_button') }}
                </button>
            @endif
        </div>
        @error('geminiApiKey') <p class="text-xs text-katta-accent mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-center gap-4">
        <x-primary-button>{{ __('settings.save_button') }}</x-primary-button>
        <x-action-message on="settings-updated">{{ __('settings.saved') }}</x-action-message>
    </div>
</form>

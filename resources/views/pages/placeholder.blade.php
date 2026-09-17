<x-app-layout :title="$title" :icon="$icon">
    <div class="bg-white rounded-2xl shadow-sm p-10 text-center mt-4">
        <i data-lucide="{{ $icon }}" class="w-12 h-12 text-katta-primary/40 mx-auto mb-4"></i>
        <h2 class="text-xl font-semibold text-gray-700 mb-2">{{ $title }}</h2>
        <p class="text-gray-400">
            {{ __('Module này đang được xây dựng, sẽ hoàn thiện ở bước tiếp theo.') }}
        </p>
    </div>
</x-app-layout>

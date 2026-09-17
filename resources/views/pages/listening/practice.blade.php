<x-app-layout :title="$title" :icon="$icon">
    <div class="mt-4">
        <livewire:listening.practice :passageId="$passageId" :key="'listening-'.$passageId" />
    </div>
</x-app-layout>

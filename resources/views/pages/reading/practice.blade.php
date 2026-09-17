<x-app-layout :title="$title" :icon="$icon">
    <div class="mt-4">
        <livewire:reading.practice :passageId="$passageId" :key="'reading-'.$passageId" />
    </div>
</x-app-layout>

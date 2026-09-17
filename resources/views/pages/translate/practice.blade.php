<x-app-layout :title="$title" :icon="$icon">
    <div class="mt-4">
        <livewire:translate.practice :level="$level" :passageId="$passageId" :key="'translate-'.$level.'-'.$passageId" />
    </div>
</x-app-layout>

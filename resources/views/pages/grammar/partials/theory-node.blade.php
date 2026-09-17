{{-- Một nút trong cây điều hướng lý thuyết ngữ pháp - đệ quy cho mọi tầng sâu bất kỳ.
     $node = ['topic' => GrammarTopic, 'children' => array], $activePath = id các tổ tiên (+ chính nó)
     của bài đang xem (để tự mở accordion đúng đường dẫn), $active = GrammarTopic đang xem (có thể null) --}}
@php
    $topic = $node['topic'];
    $hasChildren = count($node['children']) > 0;
    $isOpen = in_array($topic->id, $activePath, true) ? 'true' : 'false';
    $isActiveLeaf = $active && $active->id === $topic->id;
@endphp

<li>
    @if ($hasChildren)
        <div x-data="{ open: {{ $isOpen }} }">
            <button type="button" @click="open = !open"
                class="w-full flex items-center justify-between gap-2 px-3 py-2 rounded-lg text-sm font-semibold text-gray-600 hover:bg-gray-50 transition text-left">
                <span>{{ $topic->title }}</span>
                <i data-lucide="chevron-down" class="w-4 h-4 text-gray-300 transition-transform shrink-0" :class="open ? 'rotate-180' : ''"></i>
            </button>
            <ul x-show="open" x-cloak class="pl-3 border-l border-gray-100 ml-3 mt-0.5 space-y-0.5">
                @foreach ($node['children'] as $child)
                    @include('pages.grammar.partials.theory-node', ['node' => $child, 'activePath' => $activePath, 'active' => $active])
                @endforeach
            </ul>
        </div>
    @else
        <a href="{{ route('grammar.theory', $topic->slug) }}"
           class="block px-3 py-2 rounded-lg text-sm transition {{ $isActiveLeaf ? 'bg-katta-bg text-katta-primary font-semibold' : 'text-gray-500 hover:bg-gray-50' }}">
            {{ $topic->title }}
        </a>
    @endif
</li>

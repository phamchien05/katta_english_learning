<x-app-layout :title="$title" :icon="$icon">
    <div class="max-w-6xl mx-auto mt-6">
        <a href="{{ route('home') }}" class="text-sm text-gray-400 hover:text-katta-primary transition">
            {{ __('grammar.theory_back_breadcrumb') }}
        </a>

        <div class="flex flex-col lg:flex-row gap-6 mt-4">
            {{-- Cột trái: sidebar con - accordion phân cấp nhiều tầng --}}
            <aside class="lg:w-[300px] shrink-0">
                <div class="bg-white rounded-2xl shadow-sm p-3 lg:sticky lg:top-6">
                    <ul class="space-y-0.5">
                        @foreach ($tree as $node)
                            @include('pages.grammar.partials.theory-node', ['node' => $node, 'activePath' => $activePath, 'active' => $active])
                        @endforeach
                    </ul>
                </div>
            </aside>

            {{-- Cột phải: nội dung bài học --}}
            <div class="flex-1 min-w-0 bg-white rounded-2xl shadow-sm p-6 lg:p-8">
                @if ($active)
                    <h1 class="text-2xl font-bold text-gray-800">{{ $active->title }}</h1>
                    <div class="w-16 h-1.5 rounded-full bg-katta-primary -rotate-2 mt-3 mb-6"></div>

                    <div class="text-[15px] text-gray-600
                                [&_p]:mb-3 [&_p:last-child]:mb-0
                                [&_h3]:text-sm [&_h3]:font-bold [&_h3]:uppercase [&_h3]:tracking-wide [&_h3]:text-katta-primary [&_h3]:mt-6 [&_h3]:mb-2 [&_h3]:first:mt-0
                                [&_strong]:text-gray-800 [&_strong]:font-semibold
                                [&_em]:italic [&_em]:text-gray-500">
                        {!! $active->localized_content !!}
                    </div>
                @else
                    <div class="text-center text-gray-400 py-16">
                        {{ __('grammar.empty_content') }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

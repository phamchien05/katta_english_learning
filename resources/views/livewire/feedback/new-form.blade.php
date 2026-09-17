<div class="bg-white rounded-2xl shadow-sm p-6">
    @if ($submitted)
        <div class="text-center py-10">
            <div class="w-14 h-14 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto mb-4">
                <i data-lucide="check" class="w-7 h-7"></i>
            </div>
            <p class="font-bold text-lg text-gray-800">{{ __('feedback.submitted_title') }}</p>
            <p class="text-sm text-gray-400 mt-1">{{ __('feedback.submitted_message') }}</p>
            <button type="button" wire:click="another"
                    class="mt-5 px-5 py-2.5 rounded-xl text-sm font-semibold bg-katta-bg text-katta-primary hover:bg-katta-primary hover:text-white transition">
                {{ __('feedback.send_another') }}
            </button>
        </div>
    @else
        <form wire:submit="submit" class="space-y-5">
            {{-- Loại phản hồi --}}
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wide mb-1.5">
                    {{ __('feedback.label_category') }}
                </label>
                <div class="grid grid-cols-3 gap-2">
                    @foreach (['bug' => 'bug', 'suggestion' => 'lightbulb', 'other' => 'message-circle'] as $value => $icon)
                        <label class="flex flex-col items-center gap-1.5 p-3 rounded-xl border cursor-pointer transition text-center
                                      {{ $category === $value ? 'border-katta-primary bg-katta-bg' : 'border-gray-200 hover:bg-gray-50' }}">
                            <input type="radio" wire:model.live="category" value="{{ $value }}" class="hidden">
                            <i data-lucide="{{ $icon }}" class="w-5 h-5 {{ $category === $value ? 'text-katta-primary' : 'text-gray-400' }}"></i>
                            <span class="text-xs font-semibold {{ $category === $value ? 'text-katta-primary' : 'text-gray-500' }}">
                                {{ __('feedback.category_' . $value) }}
                            </span>
                        </label>
                    @endforeach
                </div>
                @error('category') <p class="text-xs text-katta-accent mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Tiêu đề --}}
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wide mb-1.5">
                    {{ __('feedback.label_title') }}
                </label>
                <input type="text" wire:model="title" placeholder="{{ __('feedback.title_placeholder') }}"
                       class="w-full rounded-xl border-gray-300 focus:border-katta-primary focus:ring-katta-primary text-sm">
                @error('title') <p class="text-xs text-katta-accent mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Nội dung --}}
            <div>
                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wide mb-1.5">
                    {{ __('feedback.label_message') }}
                </label>
                <textarea wire:model="message" rows="5" placeholder="{{ __('feedback.message_placeholder') }}"
                          class="w-full rounded-xl border-gray-300 focus:border-katta-primary focus:ring-katta-primary text-sm"></textarea>
                @error('message') <p class="text-xs text-katta-accent mt-1">{{ $message }}</p> @enderror
            </div>

            {{-- Ảnh chụp màn hình: dán (Ctrl+V) hoặc bấm để chọn file - cả 2 cùng đổ vào 1 input ẩn --}}
            <div
                x-data="{
                    onPaste(e) {
                        const items = e.clipboardData?.items;
                        if (!items) return;
                        for (const item of items) {
                            if (item.type.startsWith('image/')) {
                                const file = item.getAsFile();
                                if (file) {
                                    const dt = new DataTransfer();
                                    dt.items.add(file);
                                    this.$refs.fileInput.files = dt.files;
                                    this.$refs.fileInput.dispatchEvent(new Event('change'));
                                    e.preventDefault();
                                }
                                break;
                            }
                        }
                    },
                }"
                x-on:paste.window="onPaste($event)"
            >
                <label class="block text-xs font-bold text-gray-400 uppercase tracking-wide mb-1.5">
                    {{ __('feedback.label_screenshot') }}
                </label>

                <input type="file" x-ref="fileInput" wire:model="screenshot" accept="image/*" class="hidden">

                @if ($screenshot)
                    {{-- isPreviewable() phòng trường hợp user chọn nhầm file không phải ảnh (vd .pdf) -
                         temporaryUrl() sẽ crash thẳng nếu gọi trên file không xem trước được, phải chờ
                         validate() ở submit() báo lỗi "screenshot" thay vì vỡ trang ngay khi chọn file. --}}
                    <div class="relative inline-block">
                        @if ($screenshot->isPreviewable())
                            <img src="{{ $screenshot->temporaryUrl() }}" class="max-h-48 rounded-xl border border-gray-200">
                        @else
                            <div class="flex items-center gap-2 px-4 py-3 rounded-xl border border-gray-200 text-sm text-gray-500">
                                <i data-lucide="file" class="w-4 h-4"></i>
                                {{ $screenshot->getClientOriginalName() }}
                            </div>
                        @endif
                        <button type="button" wire:click="$set('screenshot', null)"
                                class="absolute -top-2 -right-2 w-6 h-6 rounded-full bg-katta-accent text-white flex items-center justify-center shadow hover:bg-rose-700 transition">
                            <i data-lucide="x" class="w-3.5 h-3.5"></i>
                        </button>
                    </div>
                @else
                    <button type="button" @click="$refs.fileInput.click()"
                            class="w-full rounded-xl border-2 border-dashed border-gray-200 hover:border-katta-primary/50 hover:bg-katta-bg/50 transition py-8 flex flex-col items-center gap-2 text-gray-400">
                        <i data-lucide="image-plus" class="w-6 h-6"></i>
                        <span class="text-xs">{{ __('feedback.screenshot_hint') }}</span>
                    </button>
                @endif

                <div wire:loading wire:target="screenshot" class="text-xs text-gray-400 mt-2 flex items-center gap-1.5">
                    <i data-lucide="loader" class="w-3.5 h-3.5 animate-spin"></i>
                    {{ __('feedback.screenshot_uploading') }}
                </div>
                @error('screenshot') <p class="text-xs text-katta-accent mt-1">{{ $message }}</p> @enderror
            </div>

            <button type="submit" wire:loading.attr="disabled" wire:target="submit,screenshot"
                    class="w-full py-3 rounded-xl text-sm font-semibold bg-katta-primary text-white hover:bg-indigo-700 transition disabled:opacity-60 flex items-center justify-center gap-2">
                <span wire:loading.remove wire:target="submit" class="flex items-center gap-2">
                    <i data-lucide="send" class="w-4 h-4"></i> {{ __('feedback.submit_button') }}
                </span>
                <span wire:loading wire:target="submit">{{ __('feedback.submitting') }}</span>
            </button>
        </form>
    @endif
</div>

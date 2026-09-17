<?php

namespace App\Livewire\Settings;

use Livewire\Component;

// Form Cài đặt (mục 14): ngôn ngữ hiển thị mặc định + API key Gemini riêng của user (khác Profile của
// Breeze, vốn đã có sẵn form đổi tên/email/mật khẩu/xoá tài khoản ở route /profile).
class Form extends Component
{
    public string $locale;

    // Không hiển thị lại key thật đã lưu (bảo mật) - để trống nghĩa là "giữ nguyên key cũ", chỉ ghi đè
    // khi user gõ giá trị mới. hasSavedKey cho view biết đã có key hay chưa để đổi placeholder/nút xoá.
    public string $geminiApiKey = '';

    public bool $hasSavedKey = false;

    public function mount(): void
    {
        $user = auth()->user();
        $this->locale = $user->locale ?? app()->getLocale();
        $this->hasSavedKey = filled($user->gemini_api_key);
    }

    public function save(): void
    {
        $this->validate([
            'locale' => 'required|in:en,vi',
            'geminiApiKey' => 'nullable|string|min:10|max:255',
        ]);

        $user = auth()->user();
        $user->locale = $this->locale;

        // Chỉ ghi đè key khi user thực sự nhập gì đó - để trống = giữ nguyên key đã lưu trước đó
        if (filled($this->geminiApiKey)) {
            $user->gemini_api_key = $this->geminiApiKey;
            $this->hasSavedKey = true;
        }

        $user->save();

        // Áp dụng ngay cho phiên hiện tại (giống nút EN/VI ở header) - sidebar/header sẽ đổi đúng ngôn
        // ngữ ở lần điều hướng tiếp theo (Livewire chỉ re-render trong phạm vi component này)
        session(['locale' => $this->locale]);
        app()->setLocale($this->locale);

        $this->geminiApiKey = '';
        $this->dispatch('settings-updated');
    }

    public function removeKey(): void
    {
        auth()->user()->update(['gemini_api_key' => null]);
        $this->hasSavedKey = false;
        $this->geminiApiKey = '';
        $this->dispatch('settings-updated');
    }

    public function render()
    {
        return view('livewire.settings.form');
    }
}

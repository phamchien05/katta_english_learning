<?php

namespace App\Livewire\Feedback;

use App\Models\Feedback;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

// Form gửi phản hồi/báo lỗi (mục 13) - có thể đính kèm ảnh chụp màn hình bằng cách dán (Ctrl+V) hoặc
// bấm chọn file, đều đi qua cùng 1 input ẩn wire:model="screenshot" (xem new-form.blade.php).
class NewForm extends Component
{
    use WithFileUploads;

    // Trang user đang đứng trước khi bấm vào Nhận xét (mục context để biết lỗi xảy ra ở đâu) - do
    // FeedbackController truyền vào từ header Referer, không cần user tự gõ lại.
    public ?string $contextUrl = null;

    #[Validate('required|in:bug,suggestion,other')]
    public string $category = 'bug';

    #[Validate('required|string|min:3|max:150')]
    public string $title = '';

    #[Validate('required|string|min:10|max:5000')]
    public string $message = '';

    #[Validate('nullable|image|max:5120')] // tối đa 5MB
    public $screenshot = null;

    public bool $submitted = false;

    public function mount(?string $contextUrl = null): void
    {
        $this->contextUrl = $contextUrl;
    }

    public function submit(): void
    {
        $this->validate();

        Feedback::create([
            'user_id' => auth()->id(),
            'category' => $this->category,
            'title' => $this->title,
            'message' => $this->message,
            'screenshot_path' => $this->screenshot?->store('feedback-screenshots', 'public'),
            'context_url' => $this->contextUrl,
            'status' => 'pending',
        ]);

        $this->reset(['category', 'title', 'message', 'screenshot']);
        $this->category = 'bug';
        $this->submitted = true;
    }

    // Gửi thêm 1 phản hồi khác - quay lại form trống thay vì giữ mãi màn hình "đã gửi"
    public function another(): void
    {
        $this->submitted = false;
    }

    public function render()
    {
        return view('livewire.feedback.new-form');
    }
}

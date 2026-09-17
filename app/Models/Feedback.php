<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    use HasFactory;

    // "feedback" là danh từ không đếm được trong tiếng Anh - đặt tên bảng rõ ràng thay vì để Laravel
    // tự đoán số nhiều ("feedbacks" nghe sai ngữ pháp)
    protected $table = 'feedback';

    protected $fillable = ['user_id', 'category', 'title', 'message', 'screenshot_path', 'context_url', 'status'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

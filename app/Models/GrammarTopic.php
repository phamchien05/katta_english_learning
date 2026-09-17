<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GrammarTopic extends Model
{
    use HasFactory;

    protected $fillable = ['parent_id', 'title', 'slug', 'content_vi', 'content_en', 'order'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(GrammarTopic::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(GrammarTopic::class, 'parent_id')->orderBy('order');
    }

    public function hasContent(): bool
    {
        return ! empty($this->content_en) || ! empty($this->content_vi);
    }

    // Nội dung bài học theo đúng ngôn ngữ đang hiển thị của app (mặc định tiếng Anh - mục 'nav.grammar'
    // vẫn tôn trọng nút chuyển EN/VI ở header như mọi nhãn khác, chỉ khác là nội dung dài nên lưu sẵn
    // 2 bản thay vì dịch qua lang file). Fallback sang bản còn lại nếu bản theo locale hiện tại trống.
    public function getLocalizedContentAttribute(): ?string
    {
        $primary = app()->getLocale() === 'vi' ? $this->content_vi : $this->content_en;

        return $primary ?: ($this->content_en ?: $this->content_vi);
    }
}

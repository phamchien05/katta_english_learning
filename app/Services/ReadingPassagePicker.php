<?php

namespace App\Services;

use App\Models\ReadingPassage;
use App\Models\ReadingSubmission;

// Chọn ngẫu nhiên 1 bài đọc trong kho, tránh trùng lại bài user VỪA LẤY RA XEM (kể cả chưa nộp bài) -
// không chỉ tránh bài đã nộp. Dùng chung cho ReadingController::general() và Reading\Practice::nextArticle().
class ReadingPassagePicker
{
    /**
     * Chọn ngẫu nhiên 1 bài của topic (+ level nếu có) mà user chưa từng lấy ra xem trong phiên này
     * và chưa nộp bài trước đó. Rơi về random toàn bộ nếu đã xem hết.
     */
    public function pickUnseen(int $userId, string $topic, ?string $level = null, ?int $excludeId = null): ?int
    {
        $attemptedIds = ReadingSubmission::where('user_id', $userId)->pluck('passage_id');
        $seenIds = session('reading_seen.' . $this->sessionKeySuffix($topic, $level), []);
        $excludeIds = $attemptedIds->merge($seenIds)->unique();
        if ($excludeId) {
            $excludeIds->push($excludeId);
        }

        $base = ReadingPassage::where('topic', $topic);
        if ($level) {
            $base->where('level', $level);
        }

        return (clone $base)->whereNotIn('id', $excludeIds)->inRandomOrder()->value('id')
            ?? (clone $base)->where('id', '!=', $excludeId)->inRandomOrder()->value('id')
            ?? $base->inRandomOrder()->value('id');
    }

    // Ghi nhớ đoạn vừa lấy ra (dù làm xong hay chưa) để không random trúng lại trong phiên này
    public function markSeen(ReadingPassage $passage): void
    {
        $key = 'reading_seen.' . $this->sessionKeySuffix($passage->topic, $passage->level);
        $seen = session($key, []);
        $seen[] = $passage->id;
        session([$key => array_slice(array_unique($seen), -50)]); // giới hạn để tránh session phình to
    }

    protected function sessionKeySuffix(string $topic, ?string $level): string
    {
        return $level ? "{$topic}.{$level}" : $topic;
    }
}

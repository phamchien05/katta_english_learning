<?php

namespace App\Services;

use App\Models\ListeningPassage;
use App\Models\ListeningSubmission;

// Chọn ngẫu nhiên 1 bài nghe trong kho, tránh trùng lại bài user VỪA LẤY RA XEM (kể cả chưa nộp bài) -
// không chỉ tránh bài đã nộp. Y hệt ReadingPassagePicker.
class ListeningPassagePicker
{
    public function pickUnseen(int $userId, string $topic, ?string $level = null, ?int $excludeId = null): ?int
    {
        $attemptedIds = ListeningSubmission::where('user_id', $userId)->pluck('passage_id');
        $seenIds = session('listening_seen.' . $this->sessionKeySuffix($topic, $level), []);
        $excludeIds = $attemptedIds->merge($seenIds)->unique();
        if ($excludeId) {
            $excludeIds->push($excludeId);
        }

        $base = ListeningPassage::where('topic', $topic);
        if ($level) {
            $base->where('level', $level);
        }

        return (clone $base)->whereNotIn('id', $excludeIds)->inRandomOrder()->value('id')
            ?? (clone $base)->where('id', '!=', $excludeId)->inRandomOrder()->value('id')
            ?? $base->inRandomOrder()->value('id');
    }

    public function markSeen(ListeningPassage $passage): void
    {
        $key = 'listening_seen.' . $this->sessionKeySuffix($passage->topic, $passage->level);
        $seen = session($key, []);
        $seen[] = $passage->id;
        session([$key => array_slice(array_unique($seen), -50)]);
    }

    protected function sessionKeySuffix(string $topic, ?string $level): string
    {
        return $level ? "{$topic}.{$level}" : $topic;
    }
}

<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

// Service gọi Gemini API dùng chung cho toàn app: sinh dữ liệu hàng loạt (artisan command)
// và các tính năng AI thời gian thực (chấm điểm dịch, AI Chat...). Tự retry khi bị rate limit (429).
class GeminiService
{
    public function __construct(
        protected string $apiKey,
        protected string $model = 'gemini-3.5-flash-lite',
        // Sinh 30-50 câu hỏi/lần (bộ đề Ngữ pháp) mất lâu hơn nhiều so với sinh 5 câu (Đọc hiểu) -
        // các nơi gọi cần request output lớn có thể truyền timeout dài hơn mặc định 30s.
        protected int $timeoutSeconds = 30,
    ) {
    }

    /**
     * Gọi Gemini, ép trả về mảng JSON chuỗi theo đúng thứ tự yêu cầu trong prompt.
     */
    public function generateStringArray(string $prompt, int $maxRetries = 3, ?\Closure $onRetry = null): ?array
    {
        $schema = ['type' => 'ARRAY', 'items' => ['type' => 'STRING']];

        return $this->generate($prompt, $schema, $maxRetries, $onRetry);
    }

    /**
     * Gọi Gemini với schema JSON tuỳ ý (vd object {score, feedback}).
     */
    public function generate(string $prompt, array $schema, int $maxRetries = 3, ?\Closure $onRetry = null): mixed
    {
        for ($attempt = 0; $attempt <= $maxRetries; $attempt++) {
            [$result, $status, $body] = $this->request($prompt, $schema);

            if ($result !== null) {
                return $result;
            }

            if ($status === 429 && $attempt < $maxRetries) {
                $retryDelay = $this->parseRetryDelay($body) ?? 8;
                $onRetry?->__invoke($retryDelay, $attempt + 1, $maxRetries);
                sleep((int) ceil($retryDelay));
                continue;
            }

            return null;
        }

        return null;
    }

    protected function request(string $prompt, array $schema): array
    {
        // Không dùng Http::retry() ở đây - nó tự throw exception sau khi hết lượt thử và làm mất
        // status code thật (429) khiến generate() không nhận diện được để tự backoff đúng cách.
        // Việc retry đã có logic riêng ở generate() (chờ theo retryDelay Gemini trả về), nên chỉ
        // gọi 1 lần ở đây, để generate() lo phần lặp lại.
        try {
            $response = Http::timeout($this->timeoutSeconds)->post(
                "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$this->apiKey}",
                [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]],
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'responseSchema' => $schema,
                    ],
                ]
            );
        } catch (\Throwable $e) {
            return [null, 0, $e->getMessage()];
        }

        if (! $response->ok()) {
            return [null, $response->status(), $response->body()];
        }

        $text = $response->json('candidates.0.content.parts.0.text');
        $decoded = json_decode($text ?? '', true);

        return [$decoded, $response->status(), $response->body()];
    }

    protected function parseRetryDelay(?string $body): ?float
    {
        $data = json_decode($body ?? '', true);
        foreach ($data['error']['details'] ?? [] as $detail) {
            if (($detail['@type'] ?? '') === 'type.googleapis.com/google.rpc.RetryInfo') {
                return (float) rtrim($detail['retryDelay'] ?? '', 's');
            }
        }
        return null;
    }
}

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'gemini_api_key',
        'locale',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'gemini_api_key',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            // Mã hoá key Gemini của user khi lưu vào DB (mục 14) - tự động giải mã khi đọc qua Eloquent
            'gemini_api_key' => 'encrypted',
        ];
    }

    // Key Gemini để dùng cho các request thời gian thực của user này: ưu tiên key riêng đã nhập ở Cài
    // đặt, nếu chưa nhập thì dùng key chung mặc định của app (config/services.php).
    public function geminiApiKey(): ?string
    {
        return $this->gemini_api_key ?: config('services.gemini.key');
    }

    // Các quan hệ dùng để tính thống kê ở Trang chủ / Thống kê / Tiến trình
    public function studySessions(): HasMany
    {
        return $this->hasMany(StudySession::class);
    }

    public function vocabProgress(): HasMany
    {
        return $this->hasMany(UserVocabProgress::class);
    }

    public function translationSubmissions(): HasMany
    {
        return $this->hasMany(TranslationSubmission::class);
    }
}

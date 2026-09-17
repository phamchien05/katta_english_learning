# Katta

Katta là ứng dụng học tiếng Anh kiểu Duolingo/IELTS trainer dành cho người Việt, xây bằng
**Laravel 12 + Livewire 3 + Tailwind CSS**, chạy trên **XAMPP (Apache + MariaDB)**. Nội dung học (bài
đọc, bài nghe, bộ đề ngữ pháp, đoạn văn dịch...) được sinh bằng **Google Gemini API**, tự động bù kho
mỗi khi người dùng làm hết một mục để luôn có nội dung mới, không lặp lại.

## Các module đã có

| Module | Nội dung |
|---|---|
| Trang chủ | Tổng quan tiến độ học, streak, hoạt động gần đây |
| Từ vựng | Trắc nghiệm theo cấp độ CEFR (A1-C2), dữ liệu từ bộ CEFR-J Wordlist |
| Dịch | Luyện dịch Anh-Việt/Việt-Anh theo cấp độ, AI chấm điểm + nhận xét |
| Đọc hiểu | Bài đọc IELTS-style theo chủ đề + cấp độ, 4 dạng câu hỏi |
| Ngữ pháp | Lý thuyết đầy đủ (135 chủ đề, song ngữ) + luyện tập với "kho đề" tự bù |
| Nghe | Bài nghe IELTS-style (giọng đọc bằng Web Speech API, không cần file audio) |
| Tiến trình | Lịch sử làm bài gộp từ tất cả module |
| Thống kê | Số liệu tổng quan, lịch học tập, độ chính xác theo module |
| Nhận xét | Gửi báo lỗi/đề xuất kèm ảnh chụp màn hình |
| Cài đặt | Ngôn ngữ hiển thị, API key Gemini riêng |

Các module còn lại (Thành ngữ, Trò chuyện AI, Nói) đang được tiếp tục xây dựng.

## Cài đặt

**Yêu cầu**: PHP 8.2+, Composer, Node.js, MySQL/MariaDB (khuyến khích chạy qua XAMPP).

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate
```

Sửa `.env`: điền `DB_DATABASE`/`DB_USERNAME`/`DB_PASSWORD` khớp với MySQL của bạn, và (không bắt buộc)
điền `GEMINI_API_KEY` lấy miễn phí tại [Google AI Studio](https://aistudio.google.com/apikey) nếu muốn
dùng các tính năng sinh nội dung bằng AI.

```bash
php artisan migrate --seed
php artisan storage:link
npm run build
```

Trỏ Apache của XAMPP vào thư mục `public/` (qua VirtualHost hoặc đặt project trong `htdocs`), hoặc chạy
nhanh bằng:

```bash
php artisan serve
```

### Sinh nội dung bằng AI (tuỳ chọn)

Sau khi có `GEMINI_API_KEY`, có thể nạp sẵn kho nội dung bằng các lệnh artisan:

```bash
php artisan reading:generate-passages --per-topic=5
php artisan listening:generate-passages --per-topic=3
php artisan translate:generate-passages --en-vi=10 --vi-en=7
php artisan grammar:generate-sets --per-topic=3
```

## Test

```bash
php artisan test
```

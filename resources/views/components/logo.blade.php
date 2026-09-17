@props(['size' => 'w-10 h-10'])

{{--
    Logo Katta.
    - Nếu đã có file public/images/logo.png (logo thật) thì hiển thị ảnh đó. Logo thật là 1 khối hoàn
      chỉnh (icon bút lông + chữ "Katta" + tagline "English Learning"), KHÔNG phải icon vuông/tròn đơn
      lẻ - nên chỉ lấy chiều cao từ $size, để chiều rộng tự co giãn đúng tỉ lệ gốc (không crop, không
      bóp méo, không bo tròn). Nơi gọi component KHÔNG cần thêm chữ "Katta" riêng nữa vì đã có trong ảnh.
    - Nếu chưa có, dùng placeholder: hình tròn nền tím, chữ "K" trắng ở giữa.
    Khi có logo thật, chỉ cần thay file public/images/logo.png, KHÔNG cần sửa code này.
--}}
@php
    $heightOnly = trim(preg_replace('/\bw-\S+/', '', $size));
@endphp

@if (file_exists(public_path('images/logo.png')))
    <img src="{{ asset('images/logo.png') }}" alt="Katta"
         {{ $attributes->merge(['class' => "$heightOnly w-auto object-contain shrink-0"]) }}>
@else
    <div {{ $attributes->merge(['class' => "$size rounded-full bg-katta-primary text-white flex items-center justify-center font-bold text-lg shrink-0"]) }}>
        K
    </div>
@endif

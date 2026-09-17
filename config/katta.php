<?php

// Cấu hình riêng của app Katta: menu sidebar, câu nhắn ủng hộ ngẫu nhiên...
return [

    // Danh sách menu điều hướng ở sidebar trái (mục 2 trong spec)
    // 'route' phải khớp tên route đã đăng ký trong routes/web.php
    'nav' => [
        ['label' => 'nav.home', 'icon' => 'home', 'route' => 'home'],
        ['label' => 'nav.vocabulary', 'icon' => 'book', 'route' => 'vocabulary.index'],
        ['label' => 'nav.translate', 'icon' => 'globe', 'route' => 'translate.index'],
        ['label' => 'nav.reading', 'icon' => 'bookmark', 'route' => 'reading.index'],
        ['label' => 'nav.grammar', 'icon' => 'pencil', 'route' => 'grammar.index'],
        ['label' => 'nav.idioms', 'icon' => 'lightbulb', 'route' => 'idioms.index'],
        ['label' => 'nav.listening', 'icon' => 'headphones', 'route' => 'listening.index'],
        ['label' => 'nav.speaking', 'icon' => 'mic', 'route' => 'speaking.index'],
        ['label' => 'nav.ai_chat', 'icon' => 'message-circle', 'route' => 'ai-chat.index'],
        ['label' => 'nav.progress', 'icon' => 'trending-up', 'route' => 'progress.index'],
        ['label' => 'nav.statistics', 'icon' => 'bar-chart-2', 'route' => 'statistics.index'],
        ['label' => 'nav.feedback', 'icon' => 'message-square', 'route' => 'feedback.index'],
        ['label' => 'nav.settings', 'icon' => 'settings', 'route' => 'settings.index'],
    ],

    // Lưới "Tất cả các tính năng" ở Trang chủ (mục 3). Mỗi thẻ: icon lucide + màu + route.
    'home_features' => [
        ['label' => 'nav.vocabulary', 'desc' => 'home.feature_vocabulary_desc', 'icon' => 'book', 'route' => 'vocabulary.index', 'color' => 'indigo'],
        ['label' => 'nav.translate', 'desc' => 'home.feature_translate_desc', 'icon' => 'globe', 'route' => 'translate.index', 'color' => 'blue'],
        ['label' => 'nav.reading', 'desc' => 'home.feature_reading_desc', 'icon' => 'bookmark', 'route' => 'reading.index', 'color' => 'amber'],
        ['label' => 'nav.grammar', 'desc' => 'home.feature_grammar_desc', 'icon' => 'pencil', 'route' => 'grammar.index', 'color' => 'rose'],
        ['label' => 'nav.idioms', 'desc' => 'home.feature_idioms_desc', 'icon' => 'lightbulb', 'route' => 'idioms.index', 'color' => 'yellow'],
        ['label' => 'nav.ai_chat', 'desc' => 'home.feature_ai_chat_desc', 'icon' => 'message-circle', 'route' => 'ai-chat.index', 'color' => 'purple'],
        ['label' => 'nav.listening', 'desc' => 'home.feature_listening_desc', 'icon' => 'headphones', 'route' => 'listening.index', 'color' => 'pink'],
        ['label' => 'nav.speaking', 'desc' => 'home.feature_speaking_desc', 'icon' => 'mic', 'route' => 'speaking.index', 'color' => 'gray'],
    ],

    // Câu nhắn ủng hộ hiển thị luân phiên ngẫu nhiên ở giữa header (mục 2)
    'header_taglines' => [
        'Hãy quyên góp giúp tôi 🙏',
        'Nhà phát triển nghèo cần cơm 🍚',
        'Katta - học tiếng Anh mỗi ngày cùng bạn',
        'Một ly cà phê cho dev cũng được ☕',
        'Ủng hộ để Katta có thêm nhiều bài học mới',
        'Code bằng cả trái tim (và mì gói) 🍜',
    ],
];

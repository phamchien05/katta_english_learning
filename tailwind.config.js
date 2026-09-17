import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['"Be Vietnam Pro"', ...defaultTheme.fontFamily.sans],
            },
            // Bảng màu riêng của Katta (mục 1 - theme màu)
            colors: {
                'katta-sidebar': '#1E1B3A', // nền sidebar tím than đậm
                'katta-bg': '#F5F3FF',      // nền nội dung chính (tím rất nhạt)
                'katta-primary': '#7C3AED', // tím điểm nhấn
                'katta-accent': '#E11D48',  // đỏ hồng điểm nhấn
            },
            borderRadius: {
                '2xl': '1rem',
                '3xl': '1.5rem',
            },
        },
    },

    plugins: [forms],
};

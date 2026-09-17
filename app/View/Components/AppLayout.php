<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    /**
     * @param string $title Tên trang hiện tại hiển thị ở header (mục 2)
     * @param string $icon Icon lucide tương ứng hiển thị cạnh tên trang
     */
    public function __construct(
        public string $title = '',
        public string $icon = 'home',
    ) {
    }

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.app');
    }
}

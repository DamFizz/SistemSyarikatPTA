<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class GuestLayout extends Component
{
    /**
     * @param  array<string, mixed>|null  $reminder  Clock-in reminder for the employee who last used this device.
     */
    public function __construct(public ?array $reminder = null) {}

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.guest');
    }
}

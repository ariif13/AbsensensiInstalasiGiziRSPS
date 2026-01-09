<?php

namespace App\Livewire;

use App\Models\Announcement;
use Livewire\Component;

class AnnouncementWidget extends Component
{
    public function render()
    {
        $announcements = Announcement::visible()
            ->orderBy('priority', 'desc')
            ->orderBy('publish_date', 'desc')
            ->take(5)
            ->get();

        return view('livewire.announcement-widget', [
            'announcements' => $announcements,
        ]);
    }
}

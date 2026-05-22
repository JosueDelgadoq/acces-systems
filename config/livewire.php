<?php

return array_replace_recursive(
    require base_path('vendor/livewire/livewire/config/livewire.php'),
    [
        'temporary_file_upload' => [
            'disk' => 'public',
            'rules' => ['required', 'file', 'image', 'max:51200'],
            'directory' => 'livewire-tmp',
        ],
    ],
);

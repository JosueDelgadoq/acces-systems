<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class SectionHeader extends Widget
{
    protected string $view = 'filament.widgets.section-header';

    public string $title = '';

    protected int | string | array $columnSpan = 'full';
}

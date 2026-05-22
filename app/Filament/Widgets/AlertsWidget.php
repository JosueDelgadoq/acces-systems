<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Lead;
use Carbon\Carbon;

class AlertsWidget extends Widget
{
    protected string $view = 'filament.widgets.alerts-widget';

    protected int|string|array $columnSpan = 'full';

    public function getAlerts()
    {
        return [
            'sin_contacto' => $this->leadsSinContacto(),
            'trabados' => $this->leadsTrabados(),
            'calientes' => $this->leadsCalientes(),
        ];
    }

    private function leadsSinContacto()
    {
        return Lead::where('estado', 'Ingresado')
            ->where('created_at', '<', now()->subHours(24))
            ->count();
    }

    private function leadsTrabados()
    {
        return Lead::whereIn('estado', [
                'Cotizacion enviada',
                'Presupuesto definitivo enviado'
            ])
            ->where('updated_at', '<', now()->subDays(3))
            ->count();
    }

    private function leadsCalientes()
    {
        return Lead::where('estado', 'Presupuesto definitivo enviado')
            ->where('updated_at', '>=', now()->subDays(2))
            ->count();
    }
}
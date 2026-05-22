<x-filament-widgets::widget>
    <x-filament::section>
        <div class="erp-alert-grid">
            <a href="{{ route('filament.admin.resources.leads.index', ['tableFilters[sin_contacto][isActive]' => true]) }}" class="erp-alert-card erp-alert-danger">
                <p class="erp-alert-card-label">Sin contacto</p>
                <p class="erp-alert-card-value">{{ $this->getAlerts()['sin_contacto'] }}</p>
            </a>

            <a href="{{ route('filament.admin.resources.leads.index', ['tableFilters[trabados][isActive]' => true]) }}" class="erp-alert-card erp-alert-warning">
                <p class="erp-alert-card-label">Leads trabados</p>
                <p class="erp-alert-card-value">{{ $this->getAlerts()['trabados'] }}</p>
            </a>

            <div class="erp-alert-card erp-alert-success">
                <p class="erp-alert-card-label">Oportunidades</p>
                <p class="erp-alert-card-value">{{ $this->getAlerts()['calientes'] }}</p>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

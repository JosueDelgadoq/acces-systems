<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="h-64">
            @livewire(\App\Filament\Widgets\Charts\MonthlyConservationsChart::class)
        </div>
        
        <div class="h-64">
            @livewire(\App\Filament\Widgets\Charts\ClaimsChart::class)
        </div>
        
        <div class="col-span-1 md:col-span-2 h-64">
            @livewire(\App\Filament\Widgets\Charts\BillingChart::class)
        </div>
</x-filament-panels::page>

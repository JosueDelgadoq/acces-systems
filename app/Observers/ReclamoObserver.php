<?php

namespace App\Observers;

use App\Models\Claim;
use App\Models\Pendiente;
use Filament\Notifications\Notification;
use App\Models\User;



class ReclamoObserver
{
    /**
     * Handle the Reclamo "created" event.
     */
    public function created(Claim $reclamo)
{
    Pendiente::create([
        'client_id' => $reclamo->client_id,
        'type' => 'reclamo',
        'description' => $reclamo->title . ' - ' . $reclamo->description,        'status' => 'pending',
        'priority' => 'alta', // reclamos suelen ser urgentes
        'due_date' => now()->addDay(),
        'source' => 'reclamo',
        User::all()->each(function ($user) use ($reclamo) {
    Notification::make()
        ->title('Nuevo reclamo')
        ->body($reclamo->title)
        ->warning()
        ->sendToDatabase($user);
})
    ]);
    
    }

    /**
     * Handle the Reclamo "updated" event.
     */
    public function updated(Claim $reclamo): void
    {
        //
    }

    /**
     * Handle the Reclamo "deleted" event.
     */
    public function deleted(Claim $reclamo): void
    {
        //
    }

    /**
     * Handle the Reclamo "restored" event.
     */
    public function restored(Claim $reclamo): void
    {
        //
    }

    /**
     * Handle the Reclamo "force deleted" event.
     */
    public function forceDeleted(Claim $reclamo): void
    {
        //
    }

}

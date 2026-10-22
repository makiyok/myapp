<?php

use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::visits.index')->name('home');

Route::livewire('visits/{visit}', 'pages::visits.show')->name('visits.show');

Route::post('stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');

Route::middleware('auth')->group(function () {
    Route::livewire('visits/{visit}/purchase', 'pages::visits.purchase')->name('visits.purchase');
    Route::livewire('entries', 'pages::entries.index')->name('entries.index');
    Route::livewire('entries/{entry}/complete', 'pages::entries.complete')->name('entries.complete');
    Route::livewire('entries/{entry}/cancel', 'pages::entries.cancel')->name('entries.cancel');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        $user = auth()->user();

        return redirect(match (true) {
            $user->isAdmin() => '/admin/visits',
            $user->isFacility() => '/facility/visits',
            default => '/entries',
        });
    })->name('dashboard');

    Route::middleware('role:facility')->prefix('facility/visits')->name('facility.visits.')->group(function () {
        Route::livewire('/', 'pages::facility.visits.index')->name('index');
        Route::livewire('create', 'pages::facility.visits.create')->name('create');
        Route::livewire('{visit}/edit', 'pages::facility.visits.edit')->name('edit');
    });
});

require __DIR__.'/settings.php';

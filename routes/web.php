<?php

use App\Http\Controllers\Auth\InviteController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\AvatarController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('kanban', 'kanban')
    ->middleware(['auth', 'verified'])
    ->name('kanban');

Route::view('backlog', 'backlog')
    ->middleware(['auth', 'verified'])
    ->name('backlog');

Route::view('sprints', 'sprints')
    ->middleware(['auth', 'verified'])
    ->name('sprints');

Route::view('epics', 'epics')
    ->middleware(['auth', 'verified'])
    ->name('epics');

Route::get('tasks/{key}', fn (string $key) => view('tasks.show', ['key' => $key]))
    ->middleware(['auth', 'verified'])
    ->where('key', '[A-Z]+-\d+')
    ->name('tasks.show');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
});

Route::get('auth/{provider}/redirect', [SocialiteController::class, 'redirect'])
    ->whereIn('provider', ['google', 'discord', 'steam'])
    ->name('socialite.redirect');

Route::get('auth/{provider}/callback', [SocialiteController::class, 'callback'])
    ->whereIn('provider', ['google', 'discord', 'steam'])
    ->name('socialite.callback');

Route::get('invite/{token}', [InviteController::class, 'show'])->name('invite.show');
Route::post('invite/{token}', [InviteController::class, 'accept'])->name('invite.accept');

Route::get('avatars/{initials}.svg', [AvatarController::class, 'default'])->name('avatars.default');

require __DIR__.'/auth.php';

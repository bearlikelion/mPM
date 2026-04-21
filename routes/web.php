<?php

use App\Http\Controllers\Auth\InviteController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\AvatarController;
use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('projects', 'projects.index')
    ->middleware(['auth', 'verified'])
    ->name('projects.index');

Route::get('manager', function () {
    $user = auth()->user();
    $currentOrg = $user->defaultOrganization ?? $user->organizations()->first();

    abort_unless($currentOrg && $user->can('update', $currentOrg), 403);

    return view('manager', [
        'currentOrg' => $currentOrg,
    ]);
})->middleware(['auth', 'verified'])
    ->name('manager');

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

Route::get('users/{user}', function (User $user) {
    $viewer = auth()->user();

    $sharedOrganizationIds = $viewer->organizations()
        ->whereIn('organizations.id', $user->organizations()->pluck('organizations.id'))
        ->pluck('organizations.id');

    abort_unless($viewer->hasRole('site_admin') || $sharedOrganizationIds->isNotEmpty(), 403);

    $currentOrg = $viewer->defaultOrganization && $sharedOrganizationIds->contains($viewer->defaultOrganization->id)
        ? $viewer->defaultOrganization
        : $user->organizations()->whereIn('organizations.id', $sharedOrganizationIds)->first();

    $projectIds = Project::query()
        ->whereIn('organization_id', $sharedOrganizationIds)
        ->pluck('id');

    $openTasksCount = $user->assignedTasks()
        ->whereIn('tasks.project_id', $projectIds)
        ->where('tasks.status', '!=', 'done')
        ->count();

    $completedTasksCount = $user->assignedTasks()
        ->whereIn('tasks.project_id', $projectIds)
        ->where('tasks.status', 'done')
        ->where('tasks.updated_at', '>=', now()->subDays(30))
        ->count();

    $recentCommentsCount = Comment::query()
        ->where('comments.user_id', $user->id)
        ->where('comments.created_at', '>=', now()->subDays(7))
        ->whereHas('task', fn ($query) => $query->whereIn('tasks.project_id', $projectIds))
        ->count();

    $recentTasks = Task::query()
        ->with('project')
        ->whereIn('project_id', $projectIds)
        ->whereHas('assignees', fn ($query) => $query->whereKey($user->id))
        ->latest('updated_at')
        ->limit(6)
        ->get();

    return view('users.show', [
        'profileUser' => $user,
        'currentOrg' => $currentOrg,
        'sharedOrganizationIds' => $sharedOrganizationIds,
        'openTasksCount' => $openTasksCount,
        'completedTasksCount' => $completedTasksCount,
        'recentCommentsCount' => $recentCommentsCount,
        'recentTasks' => $recentTasks,
    ]);
})->middleware(['auth', 'verified'])
    ->name('users.show');

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

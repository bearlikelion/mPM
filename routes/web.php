<?php

use App\Http\Controllers\Auth\InviteController;
use App\Http\Controllers\Auth\SocialiteController;
use App\Http\Controllers\AvatarController;
use App\Http\Controllers\PublicDemoController;
use App\Http\Controllers\SwitchOrganizationController;
use App\Models\Comment;
use App\Models\Epic;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\User;
use App\Support\SiteTenant;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('demo', PublicDemoController::class)
    ->name('demo');

Route::get('dashboard', function (SiteTenant $siteTenant) {
    return view('dashboard', [
        'currentOrg' => $siteTenant->currentOrganization(auth()->user()),
    ]);
})->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('projects', function (SiteTenant $siteTenant) {
    return view('projects.index', [
        'currentOrg' => $siteTenant->currentOrganization(auth()->user()),
    ]);
})->middleware(['auth', 'verified'])
    ->name('projects.index');

Route::get('manager', function (SiteTenant $siteTenant) {
    $user = auth()->user();
    $currentOrg = $siteTenant->currentOrganization($user);

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

Route::get('epics/{epic}', function (Epic $epic) {
    abort_unless(auth()->user()->can('view', $epic->project), 403);

    $epic->load('project.organization');
    $epic->loadCount([
        'tasks',
        'tasks as completed_tasks_count' => fn ($q) => $q->where('status', 'done'),
    ]);

    $sprints = Sprint::query()
        ->where('project_id', $epic->project_id)
        ->whereHas('tasks', fn ($q) => $q->where('epic_id', $epic->id))
        ->withCount([
            'tasks as epic_tasks_count' => fn ($q) => $q->where('epic_id', $epic->id),
            'tasks as epic_completed_count' => fn ($q) => $q->where('epic_id', $epic->id)->where('status', 'done'),
        ])
        ->with(['tasks' => fn ($q) => $q->where('epic_id', $epic->id)->with('assignees')->orderBy('status')->orderBy('key')])
        ->orderByRaw('COALESCE(started_at, starts_at) DESC NULLS LAST')
        ->get();

    $unscheduledTasks = Task::query()
        ->where('epic_id', $epic->id)
        ->whereNull('sprint_id')
        ->with('assignees')
        ->orderBy('status')
        ->orderBy('key')
        ->get();

    return view('epics.show', [
        'epic' => $epic,
        'sprints' => $sprints,
        'unscheduledTasks' => $unscheduledTasks,
    ]);
})->middleware(['auth', 'verified'])->name('epics.show');

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
Route::post('organizations/{organization}/switch', SwitchOrganizationController::class)
    ->middleware(['auth', 'verified'])
    ->name('organizations.switch');

require __DIR__.'/auth.php';

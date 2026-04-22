<?php

namespace App\Filament\App\Widgets;

use App\Filament\App\Widgets\Concerns\InteractsWithOrganizationDashboard;
use App\Models\Organization;
use Filament\Widgets\Widget;

class OrganizationTeamLoadWidget extends Widget
{
    use InteractsWithOrganizationDashboard;

    protected string $view = 'filament.app.widgets.organization-team-load';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 4;

    protected function getViewData(): array
    {
        /** @var Organization|null $organization */
        $organization = $this->getDashboardOrganization();
        $windowDays = $this->getDashboardWindowDays();
        $projectIds = $this->getFilteredProjectIds();
        $members = [];

        if ($organization) {
            $members = $organization->users()
                ->select('users.*', 'organization_user.role')
                ->withCount([
                    'assignedTasks as open_tasks_count' => fn ($query) => $query
                        ->whereIn('tasks.project_id', $projectIds)
                        ->where('tasks.status', '!=', 'done'),
                    'assignedTasks as completed_tasks_count' => fn ($query) => $query
                        ->whereIn('tasks.project_id', $projectIds)
                        ->where('tasks.status', 'done')
                        ->where('tasks.updated_at', '>=', now()->subDays($windowDays)),
                    'comments as recent_comments_count' => fn ($query) => $query
                        ->where('comments.created_at', '>=', now()->subDays($windowDays))
                        ->whereHas('task', fn ($taskQuery) => $taskQuery->whereIn('tasks.project_id', $projectIds)),
                ])
                ->orderByDesc('completed_tasks_count')
                ->orderByDesc('open_tasks_count')
                ->orderBy('name')
                ->limit(8)
                ->get()
                ->map(fn ($member): array => [
                    'name' => $member->name,
                    'email' => $member->email,
                    'role' => $member->role,
                    'open_tasks_count' => $member->open_tasks_count,
                    'completed_tasks_count' => $member->completed_tasks_count,
                    'recent_comments_count' => $member->recent_comments_count,
                ])
                ->all();
        }

        return [
            'members' => $members,
            'windowDays' => $windowDays,
        ];
    }
}

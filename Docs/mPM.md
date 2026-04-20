I want to create an open-source, self hosted, JIRA clone called mPM (Mark's Project Management) using Laravel 13's livewire starter kit + filament 5.0. The stack should be focusd on PHP + Blade with livewire components for everything, and Seeders / Factories for all models to help people get setup when self hosting. Self hosting has a limitation of only 1 organization unless you purchase an API Unlock key from me directly.

It should be focused on a multi-tennant architecture with the idea of opening as a SASS platform down the line.

We should have multiple Filament panels depending on user role:
Site Admin = All Organizations / overall site 
Org Admin = My organization and projects (like Nerdibear or Mark Makes Games)
Project Admin = Admin panel for my project within an organization

Logins should leverage laravel/socialite to allow for auth through external providers (Steam / Discord / Google, etc.), users should have uploadable avatars with generated defaults.

When creating a user account you can create an organization, or be invited to an organization, site admins can also disable registration but allow invited members to sign up to an org.

Organizations have projects, these projects have ACLs / Visibility, not every org member needs to see every org project, the org admin defines which users can access which projects in the org admin panel.

The core features of the project are:
    - Projects (Overall container, like SurfsUp)
    - Epics / Milestones (like SurfsUp v2)
        - Have completion / due date
        - Tasks can be assigned to epics
    - Tasks like, "Refactor leaderboard to support submitted dates"
        - Tasks can be assigned to an org user or multiple users
        - Tasks have priority (low/med/high/crit)
        - Tasks have story points using the fibonacci sequence
        - Tasks can have comments between org members
        - Tasks can have attachments uploaded to them (Markdown files, images, PDFs, etc)
        - Tasks can have colored and searchable tags (bug, ui, marketing, feature)
        - Tasks should have a status (to do, in progress, for review, complete)
    Sprints - Collections of assigned tasks to complete within a time period (2 weeks)
        - Sprints should have a date from -> to
        - Sprints can be started / ended
        - Unfinished tasks move to backlog
    - Dashboards - Who is doing the most? What am I assigned? What has been completed by others? New comments / updates?
    - GitHub integration - create feature branch, submit review pull requests
    - MCP Integration through laravel/boost so I can submit a Markdown file of Projects/Epics/Tasks
    - Kanban board / swim lanes filterable by all projects in org, assigned to me, and current / past sprint with a separate kanban for backlog
    - A public roadmap url showcasing Epics / Tasks the org admin can configure and display to anyone

Generate High fidelity mockups for the site using Claude Design

Laravel Livewire Starter Kit: https://laravel.com/docs/13.x/starter-kits#livewire

Filament PHP Docs: https://filamentphp.com/docs/5.x/introduction/overview

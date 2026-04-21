# mPM Implementation Review

Reviewed against `Docs/mPM.md` and the current codebase on 2026-04-20.

## What Is Already Implemented

- Core domain schema exists for organizations, organization invites, projects, epics, sprints, tasks, tags, comments, task assignees, project members, and Media Library attachments.
- Factories and seeders exist for the main domain models, and `DatabaseSeeder` builds a usable sample org with projects, epics, sprints, tasks, tags, and comments.
- There is a working site admin Filament panel at `/admin` with organization management.
- There is a working tenant-aware Filament app panel at `/app/{organization:slug}` with resources for projects, epics, sprints, tasks, and tags.
- User roles are seeded with Spatie Permission: `site_admin`, `org_admin`, `project_admin`, and `member`.
- Organizations, projects, and epics support uploaded logos / avatars plus generated fallback SVG avatars.
- Social login plumbing exists for `google`, `discord`, and `steam`, including provider registration and redirect / callback routes.
- Invite acceptance exists via tokenized invite URLs, including guest account creation and adding existing signed-in users to an organization.
- The non-Filament app has working authenticated pages for dashboard, kanban, backlog, sprints, epics, and task detail.
- Kanban supports drag/drop status updates and filtering by project, sprint, assignee, epic, and tag.
- Backlog supports assigning unscheduled tasks into a sprint.
- Sprint planning supports creating, starting, and ending sprints.
- Task detail supports comments and file attachments on comments through Spatie Media Library.
- Basic ACL policy classes exist for organizations, projects, epics, sprints, and tasks.

## What Is Only Partially Implemented

- Multi-tenancy is partially implemented. Filament uses tenant organizations and tenant-scoped global scopes, but the custom Livewire pages mostly scope by organization membership, not by the active tenant.
- Project visibility and ACL are partially implemented. The `projects.visibility` field exists and `ProjectPolicy` distinguishes `org`, `restricted`, and `public`, but the custom boards and dashboards do not consistently enforce restricted-project membership.
- Role separation is partial. There are only two actual panels today: `admin` and `app`. The spec calls for separate Site Admin, Org Admin, and Project Admin experiences.
- Social auth is partial. The provider flow exists, but there is no linking UI, no provider management screen, and no tests covering the OAuth flow.
- Registration controls are partial. Organizations have a `registration_enabled` flag and Filament settings expose it, but the public registration flow does not use it and still creates a plain user account only.
- Attachments are partial. Tasks implement `HasMedia`, but the visible upload UX is attached to comments, not directly to the task itself.
- Dashboarding is partial. There is a useful activity dashboard, but not the deeper workload / throughput reporting described in the product doc.

## What Is Not Implemented Yet

- Creating an organization during signup.
- Any enforcement of the self-hosted single-organization limitation or API unlock key model.
- Public roadmap pages or public-facing project / epic / task views.
- GitHub integration for branches or pull requests.
- MCP / Boost workflow for importing markdown project data.
- A dedicated project admin panel or project-level management workspace.
- Org admin tooling for invites, membership management, and project membership assignment inside the product UI.
- Task due-date workflows beyond storing the field.
- Automatic epic completion logic.
- Sprint closeout behavior that moves unfinished tasks back to backlog.
- Any explicit “assigned to me”, “current sprint”, and “past sprint” kanban variants beyond manual filtering.
- Avatar upload UX for users.
- Any “Claude Design” mockups or design artifacts.

## Important Gaps And Risks

- The product doc targets Laravel 13, but this repository is currently Laravel 12.56.
- `SocialiteController` redirects to `/app`, while the app panel is tenant-based at `/app/{organization:slug}`. That flow likely needs a tenant-aware destination.
- The starter-kit registration page creates only a `User`; it does not create an organization or assign default membership.
- `registration_enabled` appears to be configuration only right now. There is no registration gate using it.
- Custom Livewire pages query directly from models in component classes and Blade, and they do not appear to enforce `ProjectPolicy` for restricted projects.
- There is almost no test coverage for the mPM-specific features. Current tests are mostly starter-kit auth tests plus a basic dashboard access test.

## Recommended Next Work

1. Finish the onboarding model: registration should either create an organization or consume a valid invite, and it should respect `registration_enabled`.
2. Decide the tenancy boundary for the custom app pages and enforce it consistently, including restricted project membership checks.
3. Build org admin workflows for invites, members, and project membership assignment before adding more feature surface.
4. Fix sprint completion behavior so ending a sprint handles unfinished tasks correctly.
5. Add feature tests for invite acceptance, tenant scoping, project visibility, task key generation, kanban status changes, and sprint transitions.
6. After the tenancy and ACL model is solid, implement public roadmap, GitHub integration, and MCP import/export work.

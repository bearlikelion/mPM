# mPM

mPM, short for Mark's Project Management, is an open-source, self-hosted project management app for small teams that want a focused alternative to heavyweight issue trackers.

The project is in active development. It is usable for local testing and early self-hosting experiments, but the feature set, schema, UI, and deployment story are still moving.

## Current Status

mPM is being built as a Laravel application with Livewire, Filament, Reverb, PostgreSQL, Redis, and Sail. The current direction is a practical project management workspace with organizations, projects, epics, tasks, blockers, kanban, sprint planning, notifications, and YAML-based organization scaffolding.

Expect rough edges. Breaking changes are possible until the first stable release.

## License And Commercial Use

mPM is intended to be a copyleft, self-hosted open-source project.

You may not use this code to create, operate, resell, or host a paid project-management service or SaaS offering. A paid self-hosted and/or cloud-hosted version may exist in the future, but that commercial product is not implemented in this repository today.

Until the repository contains a complete `LICENSE` file, do not assume any additional commercial rights beyond the restriction above. If package metadata conflicts with this README, treat the project license terms as the source of truth once the license file is added.

## Features In Progress

- Organization and project workspaces
- Task, epic, sprint, and kanban workflows
- Task assignment, comments, tags, attachments, and blockers
- Sprint planning meetings with realtime attendance, story-point voting, tie resolution, and sprint creation
- Automatic `split-up` tagging for sprint planning estimates of 13 or 21 points
- Notifications for relevant task activity
- Organization admin tools, sprint defaults, dashboards, and member management
- YAML organization scaffolding to import/export projects, tasks, sprints, tags, assignees, and blockers
- Local Docker development with Laravel Sail, queue worker, and Reverb websocket services

## Tech Stack

- PHP 8.5
- Laravel 12
- Livewire 4
- Filament 5
- Laravel Reverb
- PostgreSQL
- Redis
- Tailwind CSS 4
- Pest
- Laravel Sail

## Local Development

This project is developed through Laravel Sail.

```bash
cp .env.example .env
vendor/bin/sail up -d
vendor/bin/sail composer install
vendor/bin/sail npm install
vendor/bin/sail artisan key:generate
vendor/bin/sail artisan migrate --seed
vendor/bin/sail npm run dev
```

Run the queue worker and Reverb through the Sail services:

```bash
vendor/bin/sail up -d queue reverb
```

Run tests:

```bash
vendor/bin/sail artisan test --compact
```

Format PHP changes:

```bash
vendor/bin/sail bin pint --dirty --format agent
```

Build frontend assets:

```bash
vendor/bin/sail npm run build
```

## Production Docker

A production-oriented compose template exists at `compose.production.yaml`. It separates the application, queue worker, and Reverb websocket server into distinct services.

This is still early. Review environment variables, secrets, storage, backups, queues, websocket exposure, TLS, and database persistence before using it anywhere important.

## Contributing

Contributions are welcome while the project is active, but expect fast iteration and incomplete surfaces. Keep changes small, tested, and aligned with the self-hosted direction of the project.

Before opening a change:

- Run the relevant Pest tests.
- Run Pint on PHP changes.
- Avoid introducing paid-service or hosted-SaaS assumptions.
- Keep setup and deployment paths friendly to self-hosters.

## Security

Do not use mPM for sensitive production data yet. The project is still under active development and has not had a dedicated security review.

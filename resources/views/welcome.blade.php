<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head', ['title' => config('app.name', 'mPM').' | Self-hosted project operations'])
    </head>
    <body>
        <main class="mx-auto flex min-h-screen w-full max-w-7xl flex-col gap-8 px-4 py-4 sm:px-6 lg:px-8 lg:py-8">
            <header class="app-panel flex flex-col gap-5 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-7">
                <div class="flex items-center gap-4">
                    <span class="app-brand-mark">mPM</span>
                    <div>
                        <div class="app-eyebrow">Open Source Project Ops</div>
                        <div class="mt-1 text-2xl font-semibold tracking-tight text-neutral-50">{{ config('app.name', 'mPM') }}</div>
                    </div>
                </div>
                <nav class="flex flex-wrap items-center gap-3 text-sm">
                    @auth
                        <a href="{{ route('dashboard') }}" class="app-link font-medium" wire:navigate>Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-neutral-300 transition hover:text-neutral-50">Log in</a>
                        <a href="{{ route('register') }}" class="rounded-full bg-amber-300 px-4 py-2 font-medium text-neutral-950 transition hover:bg-amber-400">Start building</a>
                    @endauth
                </nav>
            </header>

            <section class="app-panel app-hero overflow-hidden px-5 py-8 sm:px-8 sm:py-10">
                <div class="grid gap-8 xl:grid-cols-[1.2fr_0.8fr] xl:items-end">
                    <div class="space-y-6">
                        <div class="app-eyebrow">Built For Orgs, Projects, And Shipping Teams</div>
                        <div class="space-y-4">
                            <h1 class="app-title">A self-hosted project room that feels like your team actually lives there.</h1>
                            <p class="app-subtitle">
                                {{ config('app.name', 'mPM') }} brings kanban, backlog planning, epics, comments, and sprint rhythm into one gruvbox-inspired workspace designed for engineering teams, studios, and multi-project organizations.
                            </p>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            @auth
                                <a href="{{ route('dashboard') }}" wire:navigate class="rounded-full bg-amber-300 px-5 py-3 font-semibold text-neutral-950 transition hover:bg-amber-400">Open dashboard</a>
                            @else
                                <a href="{{ route('register') }}" class="rounded-full bg-amber-300 px-5 py-3 font-semibold text-neutral-950 transition hover:bg-amber-400">Create organization</a>
                                <a href="{{ route('login') }}" class="rounded-full border border-neutral-700 bg-neutral-950/50 px-5 py-3 font-semibold text-neutral-100 transition hover:border-neutral-500">Sign in</a>
                            @endauth
                        </div>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="app-panel-muted rounded-3xl p-5">
                            <div class="app-eyebrow">Projects</div>
                            <div class="mt-3 text-3xl font-semibold text-neutral-50">Multi-org</div>
                            <p class="mt-2 text-sm leading-7 text-neutral-400">Separate organizations, scoped projects, and room to grow into a hosted product later.</p>
                        </div>
                        <div class="app-panel-muted rounded-3xl p-5">
                            <div class="app-eyebrow">Execution</div>
                            <div class="mt-3 text-3xl font-semibold text-neutral-50">Kanban first</div>
                            <p class="mt-2 text-sm leading-7 text-neutral-400">Priorities, story points, epics, sprint assignment, and task comments on one continuous path.</p>
                        </div>
                        <div class="app-panel-muted rounded-3xl p-5 sm:col-span-2">
                            <div class="app-eyebrow">Identity</div>
                            <div class="mt-3 text-3xl font-semibold text-neutral-50">Made for teams like Nerdibear and Mark Makes Games</div>
                            <p class="mt-2 text-sm leading-7 text-neutral-400">The product language centers organizations and projects instead of flattening everything into a generic admin tool.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 lg:grid-cols-3">
                <article class="app-panel px-5 py-5">
                    <div class="app-eyebrow">01</div>
                    <h2 class="mt-3 text-2xl font-semibold text-neutral-50">Backlog to sprint</h2>
                    <p class="mt-3 text-sm leading-7 text-neutral-400">Pull work from backlog into a sprint, then watch it move through the board with clear priorities and ownership.</p>
                </article>
                <article class="app-panel px-5 py-5">
                    <div class="app-eyebrow">02</div>
                    <h2 class="mt-3 text-2xl font-semibold text-neutral-50">Epics with real progress</h2>
                    <p class="mt-3 text-sm leading-7 text-neutral-400">Milestones stay visible with due dates, completion percentage, and direct drill-in links to the active board.</p>
                </article>
                <article class="app-panel px-5 py-5">
                    <div class="app-eyebrow">03</div>
                    <h2 class="mt-3 text-2xl font-semibold text-neutral-50">Social login and self-hosting</h2>
                    <p class="mt-3 text-sm leading-7 text-neutral-400">Laravel-first foundations, Livewire surfaces, and self-hosted control with room for managed features later.</p>
                </article>
            </section>

            <section class="grid gap-4 lg:grid-cols-[0.9fr_1.1fr]">
                <div class="app-panel px-5 py-5 sm:px-6">
                    <div class="app-eyebrow">Feature Set</div>
                    <ul class="mt-4 space-y-4 text-sm text-neutral-300">
                        <li class="rounded-2xl border border-neutral-700/60 bg-neutral-950/30 px-4 py-4">Organizations, projects, and role-aware admin surfaces</li>
                        <li class="rounded-2xl border border-neutral-700/60 bg-neutral-950/30 px-4 py-4">Task priorities, story points, attachments, comments, and tags</li>
                        <li class="rounded-2xl border border-neutral-700/60 bg-neutral-950/30 px-4 py-4">Kanban, backlog views, sprint planning, and dashboard pulse</li>
                    </ul>
                </div>

                <div class="app-panel px-5 py-5 sm:px-6">
                    <div class="app-eyebrow">Why This Direction</div>
                    <div class="mt-3 text-2xl font-semibold text-neutral-50">Less dashboard chrome. More operational character.</div>
                    <p class="mt-4 text-sm leading-8 text-neutral-400">
                        The redesign leans into a warm, gruvbox-style control room instead of a flat dark admin theme. That gives the product a stronger point of view while keeping the UI grounded in the core workflow: plan, prioritize, execute, and review work across real organizations and projects.
                    </p>
                </div>
            </section>
        </main>
    </body>
</html>

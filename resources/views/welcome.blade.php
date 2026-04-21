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
                        <div class="app-eyebrow">Mini Project Manager</div>
                        <div class="mt-1 text-2xl font-semibold tracking-tight text-neutral-50">{{ config('app.name', 'mPM') }}</div>
                    </div>
                </div>
                <nav class="flex flex-wrap items-center gap-3 text-sm">
                    @auth
                        <a href="{{ route('dashboard') }}" class="app-link font-medium" wire:navigate>Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="text-neutral-300 transition hover:text-neutral-50">Log in</a>
                        <a href="{{ route('register') }}" class="rounded-full bg-amber-300 px-4 py-2 font-medium text-neutral-950 transition hover:bg-amber-400">Get started</a>
                    @endauth
                </nav>
            </header>

            <section class="app-panel app-hero overflow-hidden px-5 py-8 sm:px-8 sm:py-10">
                <div class="grid gap-8 xl:grid-cols-[1.2fr_0.8fr] xl:items-end">
                    <div class="space-y-6">
                        <div class="app-eyebrow">Project Ops, Without The Bloat</div>
                        <div class="space-y-4">
                            <h1 class="app-title">A project manager built for small teams, solo devs, and multi-project studios.</h1>
                            <p class="app-subtitle">
                                {{ config('app.name', 'mPM') }} is a gruvbox-flavored workspace for organizations, projects, epics, sprints, kanban, and comments — without the enterprise checklist of a Jira install. Designed to be opinionated, fast, and boring in the best way.
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
                            <div class="app-eyebrow">Open Source</div>
                            <div class="mt-3 text-3xl font-semibold text-neutral-50">MIT-spirited</div>
                            <p class="mt-2 text-sm leading-7 text-neutral-400">The codebase is heading to a public repository so you can audit it, fork it, and run it yourself.</p>
                        </div>
                        <div class="app-panel-muted rounded-3xl p-5">
                            <div class="app-eyebrow">Self-Hosted</div>
                            <div class="mt-3 text-3xl font-semibold text-neutral-50">Your server, your data</div>
                            <p class="mt-2 text-sm leading-7 text-neutral-400">Ship it on Laravel Cloud, a VPS, or your homelab. No SaaS lock-in required.</p>
                        </div>
                        <div class="app-panel-muted rounded-3xl p-5">
                            <div class="app-eyebrow">Hosted SaaS</div>
                            <div class="mt-3 text-3xl font-semibold text-neutral-50">Managed tier — soon</div>
                            <p class="mt-2 text-sm leading-7 text-neutral-400">A hosted plan is coming for teams who want the product without running the infrastructure.</p>
                        </div>
                        <div class="app-panel-muted rounded-3xl p-5">
                            <div class="app-eyebrow">Native Apps</div>
                            <div class="mt-3 text-3xl font-semibold text-neutral-50">Desktop & mobile</div>
                            <p class="mt-2 text-sm leading-7 text-neutral-400">First-class macOS, Windows, Linux, iOS, and Android clients are on the roadmap.</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 lg:grid-cols-3">
                <article class="app-panel px-5 py-5">
                    <div class="app-eyebrow">Why It Exists</div>
                    <h2 class="mt-3 text-2xl font-semibold text-neutral-50">Every tool is too much.</h2>
                    <p class="mt-3 text-sm leading-7 text-neutral-400">Linear, Jira, Asana, Notion — each one wants to be the whole company. mPM is the opposite: plan, prioritize, ship, comment. That's the loop.</p>
                </article>
                <article class="app-panel px-5 py-5">
                    <div class="app-eyebrow">Who It's For</div>
                    <h2 class="mt-3 text-2xl font-semibold text-neutral-50">Small teams and studios.</h2>
                    <p class="mt-3 text-sm leading-7 text-neutral-400">Indie game studios, side projects, two-person shops, and internal tooling teams who want multi-org structure without enterprise overhead.</p>
                </article>
                <article class="app-panel px-5 py-5">
                    <div class="app-eyebrow">The Shape</div>
                    <h2 class="mt-3 text-2xl font-semibold text-neutral-50">Opinionated by design.</h2>
                    <p class="mt-3 text-sm leading-7 text-neutral-400">Gruvbox palette, amber accents, keyboard-forward surfaces. Less configuration, more character — but still yours to theme.</p>
                </article>
            </section>

            <section class="grid gap-4 lg:grid-cols-[0.9fr_1.1fr]">
                <div class="app-panel px-5 py-5 sm:px-6">
                    <div class="app-eyebrow">Feature Set</div>
                    <ul class="mt-4 space-y-3 text-sm text-neutral-300">
                        <li class="rounded-2xl border border-neutral-700/60 bg-neutral-950/30 px-4 py-3">Organizations, projects, and role-aware admin surfaces</li>
                        <li class="rounded-2xl border border-neutral-700/60 bg-neutral-950/30 px-4 py-3">Tasks with priorities, story points, attachments, comments, and tags</li>
                        <li class="rounded-2xl border border-neutral-700/60 bg-neutral-950/30 px-4 py-3">Kanban, backlog, sprint planning, and an at-a-glance dashboard</li>
                        <li class="rounded-2xl border border-neutral-700/60 bg-neutral-950/30 px-4 py-3">Invites, social login, and per-org permissions</li>
                        <li class="rounded-2xl border border-neutral-700/60 bg-neutral-950/30 px-4 py-3">Deep site-admin analytics for orgs, users, tasks, and storage</li>
                    </ul>
                </div>

                <div class="app-panel px-5 py-5 sm:px-6">
                    <div class="app-eyebrow">Tech Stack</div>
                    <div class="mt-3 text-2xl font-semibold text-neutral-50">Boring Laravel. Sharp UI.</div>
                    <p class="mt-4 text-sm leading-8 text-neutral-400">
                        Built on a proven server-rendered stack so the product stays fast, debuggable, and easy to self-host.
                    </p>
                    <dl class="mt-5 grid gap-3 sm:grid-cols-2 text-sm">
                        <div class="rounded-2xl border border-neutral-700/60 bg-neutral-950/30 px-4 py-3">
                            <dt class="app-eyebrow">Backend</dt>
                            <dd class="mt-1 text-neutral-200">PHP 8.5, Laravel 12</dd>
                        </div>
                        <div class="rounded-2xl border border-neutral-700/60 bg-neutral-950/30 px-4 py-3">
                            <dt class="app-eyebrow">Frontend</dt>
                            <dd class="mt-1 text-neutral-200">Livewire 4, Volt, Alpine</dd>
                        </div>
                        <div class="rounded-2xl border border-neutral-700/60 bg-neutral-950/30 px-4 py-3">
                            <dt class="app-eyebrow">UI</dt>
                            <dd class="mt-1 text-neutral-200">Tailwind 4, MaryUI, Filament 5</dd>
                        </div>
                        <div class="rounded-2xl border border-neutral-700/60 bg-neutral-950/30 px-4 py-3">
                            <dt class="app-eyebrow">Platform</dt>
                            <dd class="mt-1 text-neutral-200">Sail / Docker + Postgres</dd>
                        </div>
                        <div class="rounded-2xl border border-neutral-700/60 bg-neutral-950/30 px-4 py-3">
                            <dt class="app-eyebrow">Auth</dt>
                            <dd class="mt-1 text-neutral-200">Socialite, Spatie Permission</dd>
                        </div>
                        <div class="rounded-2xl border border-neutral-700/60 bg-neutral-950/30 px-4 py-3">
                            <dt class="app-eyebrow">Testing</dt>
                            <dd class="mt-1 text-neutral-200">Pest 3, PHPUnit 11</dd>
                        </div>
                    </dl>
                </div>
            </section>

            <section class="app-panel px-5 py-6 sm:px-7">
                <div class="grid gap-6 lg:grid-cols-[1fr_auto] lg:items-center">
                    <div>
                        <div class="app-eyebrow">Made By</div>
                        <h2 class="mt-2 text-2xl font-semibold text-neutral-50">Mark Arneman</h2>
                        <p class="mt-3 text-sm leading-7 text-neutral-400">
                            mPM is a solo project by Mark — a developer building tools he actually wants to use. It's the coordination layer behind his indie studios and side projects, shared with anyone who wants it.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <a href="https://arneman.me" target="_blank" rel="noopener noreferrer"
                           class="rounded-full border border-neutral-700 bg-neutral-950/40 px-5 py-2.5 text-sm font-medium text-neutral-100 transition hover:border-amber-300 hover:text-amber-300">
                            arneman.me
                        </a>
                        <a href="https://bearlikelion.com" target="_blank" rel="noopener noreferrer"
                           class="rounded-full border border-neutral-700 bg-neutral-950/40 px-5 py-2.5 text-sm font-medium text-neutral-100 transition hover:border-amber-300 hover:text-amber-300">
                            bearlikelion.com
                        </a>
                    </div>
                </div>
            </section>

            <footer class="app-panel flex flex-col gap-2 px-5 py-4 text-xs text-neutral-500 sm:flex-row sm:items-center sm:justify-between sm:px-7">
                <span>&copy; {{ date('Y') }} Mark Arneman. Self-hosted project ops.</span>
                <span>Open source &middot; Self-hosted &middot; Hosted SaaS &middot; Native apps</span>
            </footer>
        </main>
    </body>
</html>

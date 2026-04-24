<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head', ['title' => config('app.name', 'mPM').' | Self-hosted project operations'])
        <style>[x-cloak] { display: none !important; }</style>
    </head>
    <body>
        @php($registrationOpen = \App\Support\RegistrationGate::allowsRegistration())
        <main class="mx-auto flex min-h-screen w-full max-w-7xl flex-col gap-8 px-4 py-4 sm:px-6 lg:px-8 lg:py-8">
            <header class="app-panel flex flex-col gap-5 px-5 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-7">
                <div class="flex items-center gap-4">
                    <span class="app-brand-mark">mPM</span>
                    <div>
                        <div class="app-eyebrow">Mark's Project Manager</div>
                        <div class="mt-1 text-2xl font-semibold tracking-tight text-neutral-50">{{ config('app.name', 'mPM') }}</div>
                    </div>
                </div>
                <nav class="flex flex-wrap items-center gap-3 text-sm">
                    @auth
                        <a href="{{ route('dashboard') }}" class="app-link font-medium" wire:navigate>Dashboard</a>
                    @else
                        <a href="https://github.com/bearlikelion/mPM" target="_blank" rel="noopener noreferrer" class="text-neutral-300 transition hover:text-neutral-50">GitHub</a>
                        <a href="{{ route('login') }}" class="text-neutral-300 transition hover:text-neutral-50">Log in</a>
                        @if($registrationOpen)
                            <a href="{{ route('register') }}" class="rounded-full bg-amber-300 px-4 py-2 font-medium text-neutral-950 transition hover:bg-amber-400">Get started</a>
                        @endif
                    @endauth
                </nav>
            </header>

            <section class="app-panel app-hero overflow-hidden px-5 py-8 sm:px-8 sm:py-10">
                <div class="grid gap-10 lg:grid-cols-[1.1fr_0.9fr] lg:items-center">
                    <div class="space-y-6">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="app-eyebrow">Project Ops, Without The Bloat</span>
                            <span class="rounded-full border border-amber-400/40 bg-amber-400/10 px-2.5 py-0.5 text-[11px] font-semibold uppercase tracking-wider text-amber-300">Under Active Development</span>
                        </div>
                        <div class="space-y-4">
                            <h1 class="app-title">A project manager built for small teams, solo devs, and multi-project studios.</h1>
                            <p class="app-subtitle">
                                {{ config('app.name', 'mPM') }} is a gruvbox-flavored workspace for organizations, projects, epics, sprints, kanban, and comments - without the enterprise checklist of a Jira install. Designed to be opinionated, fast, and boring in the best way.
                            </p>
                            <p class="app-subtitle">A self-hosted project room that feels like your team actually lives there.</p>
                        </div>
                        <div class="flex flex-wrap gap-3">
                            @auth
                                <a href="{{ route('dashboard') }}" wire:navigate class="rounded-full bg-amber-300 px-5 py-3 font-semibold text-neutral-950 transition hover:bg-amber-400">Open dashboard</a>
                            @else
                                @if($registrationOpen)
                                    <a href="{{ route('register') }}" class="rounded-full bg-amber-300 px-5 py-3 font-semibold text-neutral-950 transition hover:bg-amber-400">Create organization</a>
                                    <a href="{{ route('login') }}" class="rounded-full border border-neutral-700 bg-neutral-950/50 px-5 py-3 font-semibold text-neutral-100 transition hover:border-neutral-500">Sign in</a>
                                @else
                                    <a href="{{ route('login') }}" class="rounded-full bg-amber-300 px-5 py-3 font-semibold text-neutral-950 transition hover:bg-amber-400">Sign in</a>
                                    <a href="https://github.com/bearlikelion/mPM" target="_blank" rel="noopener noreferrer" class="rounded-full border border-neutral-700 bg-neutral-950/50 px-5 py-3 font-semibold text-neutral-100 transition hover:border-neutral-500">View on GitHub</a>
                                @endif
                            @endauth
                        </div>
                        @unless($registrationOpen)
                            <p class="text-xs text-neutral-500">Public registration is disabled on this instance. You'll need an invite from an admin to join.</p>
                        @endunless
                    </div>

                    <div class="relative" x-data="{ open: false }">
                        <div class="absolute -inset-4 rounded-3xl bg-gradient-to-br from-amber-400/20 via-transparent to-transparent blur-2xl"></div>
                        <figure class="relative overflow-hidden rounded-2xl border border-neutral-700/70 bg-neutral-950/60 shadow-2xl">
                            <button
                                type="button"
                                x-on:click="open = true"
                                class="group block w-full cursor-zoom-in"
                                aria-label="Open screenshot fullscreen"
                            >
                                <img
                                    src="{{ asset('img/example_board.png') }}"
                                    alt="mPM kanban board screenshot"
                                    class="block w-full transition group-hover:opacity-90"
                                    loading="lazy"
                                />
                            </button>
                            <figcaption class="flex items-center justify-between border-t border-neutral-800 bg-neutral-950/80 px-4 py-2.5 text-xs text-neutral-400">
                                <span>Kanban board - drag, prioritize, and ship.</span>
                                <span class="text-neutral-500">Click to expand</span>
                            </figcaption>
                        </figure>

                        <div
                            x-show="open"
                            x-cloak
                            x-transition.opacity
                            x-on:keydown.escape.window="open = false"
                            x-on:click="open = false"
                            class="fixed inset-0 z-50 flex items-center justify-center bg-neutral-950/90 p-4 backdrop-blur-sm sm:p-8"
                            role="dialog"
                            aria-modal="true"
                        >
                            <button
                                type="button"
                                x-on:click.stop="open = false"
                                class="absolute right-4 top-4 z-10 rounded-full border border-neutral-700 bg-neutral-950/80 p-2 text-neutral-200 transition hover:border-amber-300 hover:text-amber-300 sm:right-6 sm:top-6"
                                aria-label="Close"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
                                    <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                                </svg>
                            </button>
                            <img
                                src="{{ asset('img/example_board.png') }}"
                                alt="mPM kanban board screenshot"
                                x-on:click.stop
                                class="max-h-full max-w-full rounded-xl border border-neutral-700/70 shadow-2xl"
                            />
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="app-panel-muted rounded-3xl px-5 py-5">
                    <div class="app-eyebrow">Open Source</div>
                    <div class="mt-3 text-xl font-semibold text-neutral-50">Source-available, copyleft</div>
                    <p class="mt-2 text-sm leading-7 text-neutral-400">Audit it, fork it, and run it yourself. Just don't repackage it as a paid SaaS.</p>
                </div>
                <div class="app-panel-muted rounded-3xl px-5 py-5">
                    <div class="app-eyebrow">Self-Hosted</div>
                    <div class="mt-3 text-xl font-semibold text-neutral-50">Your server, your data</div>
                    <p class="mt-2 text-sm leading-7 text-neutral-400">Ship it on Laravel Cloud, a VPS, CapRover, or your homelab. No SaaS lock-in.</p>
                </div>
                <div class="app-panel-muted rounded-3xl px-5 py-5">
                    <div class="app-eyebrow">Hosted SaaS</div>
                    <div class="mt-3 text-xl font-semibold text-neutral-50">Managed tier - soon</div>
                    <p class="mt-2 text-sm leading-7 text-neutral-400">A hosted plan is coming for teams who want the product without running infrastructure.</p>
                </div>
                <div class="app-panel-muted rounded-3xl px-5 py-5">
                    <div class="app-eyebrow">Native Apps</div>
                    <div class="mt-3 text-xl font-semibold text-neutral-50">Desktop & mobile</div>
                    <p class="mt-2 text-sm leading-7 text-neutral-400">First-class macOS, Windows, Linux, iOS, and Android clients are on the roadmap.</p>
                </div>
            </section>

            <section class="grid gap-4 lg:grid-cols-3">
                <article class="app-panel px-5 py-5">
                    <div class="app-eyebrow">Why It Exists</div>
                    <h2 class="mt-3 text-2xl font-semibold text-neutral-50">Every tool is too much.</h2>
                    <p class="mt-3 text-sm leading-7 text-neutral-400">Linear, Jira, Asana, Notion - each one wants to be the whole company. mPM is the opposite: plan, prioritize, ship, comment. That's the loop.</p>
                </article>
                <article class="app-panel px-5 py-5">
                    <div class="app-eyebrow">Who It's For</div>
                    <h2 class="mt-3 text-2xl font-semibold text-neutral-50">Small teams and studios.</h2>
                    <p class="mt-3 text-sm leading-7 text-neutral-400">Indie game studios, side projects, two-person shops, and internal tooling teams who want multi-org structure without enterprise overhead.</p>
                </article>
                <article class="app-panel px-5 py-5">
                    <div class="app-eyebrow">The Shape</div>
                    <h2 class="mt-3 text-2xl font-semibold text-neutral-50">Opinionated by design.</h2>
                    <p class="mt-3 text-sm leading-7 text-neutral-400">Gruvbox palette, amber accents, keyboard-forward surfaces. Less configuration, more character - but still yours to theme.</p>
                </article>
            </section>

            <section class="grid gap-4 lg:grid-cols-[0.95fr_1.05fr]">
                <div class="app-panel px-5 py-5 sm:px-6">
                    <div class="app-eyebrow">Feature Set</div>
                    <h2 class="mt-3 text-2xl font-semibold text-neutral-50">Everything a planning room needs.</h2>
                    <ul class="mt-5 space-y-3 text-sm text-neutral-300">
                        <li class="flex gap-3 rounded-2xl border border-neutral-700/60 bg-neutral-950/30 px-4 py-3">
                            <span class="mt-0.5 text-amber-300">→</span>
                            <span><strong class="text-neutral-100">Organizations &amp; projects</strong> with member management, roles, and admin tooling</span>
                        </li>
                        <li class="flex gap-3 rounded-2xl border border-neutral-700/60 bg-neutral-950/30 px-4 py-3">
                            <span class="mt-0.5 text-amber-300">→</span>
                            <span><strong class="text-neutral-100">Tasks &amp; epics</strong> - assignments, comments, tags, attachments, and blockers</span>
                        </li>
                        <li class="flex gap-3 rounded-2xl border border-neutral-700/60 bg-neutral-950/30 px-4 py-3">
                            <span class="mt-0.5 text-amber-300">→</span>
                            <span><strong class="text-neutral-100">Kanban &amp; sprints</strong> - board view, sprint creation, defaults, and dashboards</span>
                        </li>
                        <li class="flex gap-3 rounded-2xl border border-neutral-700/60 bg-neutral-950/30 px-4 py-3">
                            <span class="mt-0.5 text-amber-300">→</span>
                            <span><strong class="text-neutral-100">Sprint planning</strong> with realtime attendance, story-point voting, tie resolution, and auto <code class="rounded bg-neutral-800 px-1 text-amber-200">split-up</code> tagging at 13/21 points</span>
                        </li>
                        <li class="flex gap-3 rounded-2xl border border-neutral-700/60 bg-neutral-950/30 px-4 py-3">
                            <span class="mt-0.5 text-amber-300">→</span>
                            <span><strong class="text-neutral-100">Realtime notifications</strong> for activity on the work that's actually yours</span>
                        </li>
                        <li class="flex gap-3 rounded-2xl border border-neutral-700/60 bg-neutral-950/30 px-4 py-3">
                            <span class="mt-0.5 text-amber-300">→</span>
                            <span><strong class="text-neutral-100">YAML scaffolding</strong> - import/export projects, tasks, sprints, tags, assignees, and blockers</span>
                        </li>
                        <li class="flex gap-3 rounded-2xl border border-neutral-700/60 bg-neutral-950/30 px-4 py-3">
                            <span class="mt-0.5 text-amber-300">→</span>
                            <span><strong class="text-neutral-100">OAuth login</strong> via Discord and Steam, plus per-org invites and permissions</span>
                        </li>
                        <li class="flex gap-3 rounded-2xl border border-neutral-700/60 bg-neutral-950/30 px-4 py-3">
                            <span class="mt-0.5 text-amber-300">→</span>
                            <span><strong class="text-neutral-100">Site-admin analytics</strong> for orgs, users, tasks, and storage</span>
                        </li>
                    </ul>
                </div>

                <div class="app-panel px-5 py-5 sm:px-6">
                    <div class="app-eyebrow">Tech Stack</div>
                    <h2 class="mt-3 text-2xl font-semibold text-neutral-50">Boring Laravel. Sharp UI.</h2>
                    <p class="mt-4 text-sm leading-7 text-neutral-400">
                        Built on a proven server-rendered stack so the product stays fast, debuggable, and easy to self-host. No SPA hydration tax, no microservice sprawl.
                    </p>
                    <dl class="mt-5 grid gap-3 sm:grid-cols-2 text-sm">
                        <div class="rounded-2xl border border-neutral-700/60 bg-neutral-950/30 px-4 py-3">
                            <dt class="app-eyebrow">Backend</dt>
                            <dd class="mt-1 flex flex-wrap gap-x-2 gap-y-1">
                                <a href="https://www.php.net/" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-md px-1 -mx-1 text-neutral-200 transition hover:text-amber-300 hover:bg-amber-300/5">PHP 8.5</a>
                                <span class="text-neutral-600">·</span>
                                <a href="https://laravel.com/" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-md px-1 -mx-1 text-neutral-200 transition hover:text-amber-300 hover:bg-amber-300/5">Laravel 13</a>
                            </dd>
                        </div>
                        <div class="rounded-2xl border border-neutral-700/60 bg-neutral-950/30 px-4 py-3">
                            <dt class="app-eyebrow">Frontend</dt>
                            <dd class="mt-1 flex flex-wrap gap-x-2 gap-y-1">
                                <a href="https://livewire.laravel.com/" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-md px-1 -mx-1 text-neutral-200 transition hover:text-amber-300 hover:bg-amber-300/5">Livewire 4</a>
                                <span class="text-neutral-600">·</span>
                                <a href="https://livewire.laravel.com/docs/volt" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-md px-1 -mx-1 text-neutral-200 transition hover:text-amber-300 hover:bg-amber-300/5">Volt</a>
                                <span class="text-neutral-600">·</span>
                                <a href="https://alpinejs.dev/" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-md px-1 -mx-1 text-neutral-200 transition hover:text-amber-300 hover:bg-amber-300/5">Alpine</a>
                            </dd>
                        </div>
                        <div class="rounded-2xl border border-neutral-700/60 bg-neutral-950/30 px-4 py-3">
                            <dt class="app-eyebrow">UI</dt>
                            <dd class="mt-1 flex flex-wrap gap-x-2 gap-y-1">
                                <a href="https://mary-ui.com/" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-md px-1 -mx-1 text-neutral-200 transition hover:text-amber-300 hover:bg-amber-300/5">Mary UI 2</a>
                                <span class="text-neutral-600">·</span>
                                <a href="https://filamentphp.com/" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-md px-1 -mx-1 text-neutral-200 transition hover:text-amber-300 hover:bg-amber-300/5">Filament 5</a>
                                <span class="text-neutral-600">·</span>
                                <a href="https://tailwindcss.com/" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-md px-1 -mx-1 text-neutral-200 transition hover:text-amber-300 hover:bg-amber-300/5">Tailwind 4</a>
                            </dd>
                        </div>
                        <div class="rounded-2xl border border-neutral-700/60 bg-neutral-950/30 px-4 py-3">
                            <dt class="app-eyebrow">Realtime</dt>
                            <dd class="mt-1 flex flex-wrap gap-x-2 gap-y-1">
                                <a href="https://reverb.laravel.com/" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-md px-1 -mx-1 text-neutral-200 transition hover:text-amber-300 hover:bg-amber-300/5">Laravel Reverb</a>
                                <span class="text-neutral-600">·</span>
                                <a href="https://redis.io/" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-md px-1 -mx-1 text-neutral-200 transition hover:text-amber-300 hover:bg-amber-300/5">Redis</a>
                            </dd>
                        </div>
                        <div class="rounded-2xl border border-neutral-700/60 bg-neutral-950/30 px-4 py-3">
                            <dt class="app-eyebrow">Platform</dt>
                            <dd class="mt-1 flex flex-wrap gap-x-2 gap-y-1">
                                <a href="https://laravel.com/docs/sail" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-md px-1 -mx-1 text-neutral-200 transition hover:text-amber-300 hover:bg-amber-300/5">Sail</a>
                                <span class="text-neutral-600">·</span>
                                <a href="https://www.docker.com/" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-md px-1 -mx-1 text-neutral-200 transition hover:text-amber-300 hover:bg-amber-300/5">Docker</a>
                                <span class="text-neutral-600">·</span>
                                <a href="https://www.postgresql.org/" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-md px-1 -mx-1 text-neutral-200 transition hover:text-amber-300 hover:bg-amber-300/5">PostgreSQL</a>
                            </dd>
                        </div>
                        <div class="rounded-2xl border border-neutral-700/60 bg-neutral-950/30 px-4 py-3">
                            <dt class="app-eyebrow">Auth</dt>
                            <dd class="mt-1 flex flex-wrap gap-x-2 gap-y-1">
                                <a href="https://laravel.com/docs/socialite" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-md px-1 -mx-1 text-neutral-200 transition hover:text-amber-300 hover:bg-amber-300/5">Socialite</a>
                                <span class="text-neutral-600">·</span>
                                <a href="https://spatie.be/docs/laravel-permission" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-md px-1 -mx-1 text-neutral-200 transition hover:text-amber-300 hover:bg-amber-300/5">Spatie Permission</a>
                            </dd>
                        </div>
                        <div class="rounded-2xl border border-neutral-700/60 bg-neutral-950/30 px-4 py-3">
                            <dt class="app-eyebrow">Media</dt>
                            <dd class="mt-1 flex flex-wrap gap-x-2 gap-y-1">
                                <a href="https://spatie.be/docs/laravel-medialibrary" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-md px-1 -mx-1 text-neutral-200 transition hover:text-amber-300 hover:bg-amber-300/5">Spatie Media Library</a>
                            </dd>
                        </div>
                        <div class="rounded-2xl border border-neutral-700/60 bg-neutral-950/30 px-4 py-3">
                            <dt class="app-eyebrow">Testing</dt>
                            <dd class="mt-1 flex flex-wrap gap-x-2 gap-y-1">
                                <a href="https://pestphp.com/" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-md px-1 -mx-1 text-neutral-200 transition hover:text-amber-300 hover:bg-amber-300/5">Pest 4</a>
                                <span class="text-neutral-600">·</span>
                                <a href="https://phpunit.de/" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-md px-1 -mx-1 text-neutral-200 transition hover:text-amber-300 hover:bg-amber-300/5">PHPUnit 12</a>
                            </dd>
                        </div>
                    </dl>
                </div>
            </section>

            <section class="app-panel px-5 py-6 sm:px-7">
                <div class="grid gap-6 lg:grid-cols-[1fr_1fr] lg:items-start">
                    <div>
                        <div class="app-eyebrow">Self-Host It</div>
                        <h2 class="mt-2 text-2xl font-semibold text-neutral-50">Deploy in minutes.</h2>
                        <p class="mt-3 text-sm leading-7 text-neutral-400">
                            mPM ships with a production <code class="rounded bg-neutral-800 px-1.5 py-0.5 text-amber-200">Dockerfile</code> and CapRover configuration. Push to <code class="rounded bg-neutral-800 px-1.5 py-0.5 text-amber-200">main</code>, mount a persistent <code class="rounded bg-neutral-800 px-1.5 py-0.5 text-amber-200">/config</code> volume for your env, and let the entrypoint handle migrations, queue workers, the scheduler, and Reverb via Supervisor.
                        </p>
                        <div class="mt-4 flex flex-wrap gap-2 text-xs">
                            <span class="rounded-full border border-neutral-700/70 bg-neutral-950/40 px-3 py-1 text-neutral-300">CapRover</span>
                            <span class="rounded-full border border-neutral-700/70 bg-neutral-950/40 px-3 py-1 text-neutral-300">Docker Compose</span>
                            <span class="rounded-full border border-neutral-700/70 bg-neutral-950/40 px-3 py-1 text-neutral-300">Laravel Cloud</span>
                            <span class="rounded-full border border-neutral-700/70 bg-neutral-950/40 px-3 py-1 text-neutral-300">VPS</span>
                            <span class="rounded-full border border-neutral-700/70 bg-neutral-950/40 px-3 py-1 text-neutral-300">Homelab</span>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-neutral-700/60 bg-neutral-950/60 p-4 font-mono text-xs leading-6 text-neutral-300">
                        <div class="text-neutral-500"># Local development</div>
                        <div><span class="text-amber-300">$</span> cp .env.example .env</div>
                        <div><span class="text-amber-300">$</span> vendor/bin/sail up -d</div>
                        <div><span class="text-amber-300">$</span> vendor/bin/sail artisan key:generate</div>
                        <div><span class="text-amber-300">$</span> vendor/bin/sail artisan migrate --seed</div>
                        <div><span class="text-amber-300">$</span> vendor/bin/sail npm install &amp;&amp; npm run dev</div>
                        <div class="mt-3 text-neutral-500"># Run queue + websockets</div>
                        <div><span class="text-amber-300">$</span> vendor/bin/sail up -d queue reverb</div>
                    </div>
                </div>
            </section>

            <section class="app-panel px-5 py-6 sm:px-7">
                <div class="grid gap-6 lg:grid-cols-[1fr_auto] lg:items-center">
                    <div>
                        <div class="app-eyebrow">Made By</div>
                        <h2 class="mt-2 text-2xl font-semibold text-neutral-50">Mark Arneman</h2>
                        <p class="mt-3 text-sm leading-7 text-neutral-400">
                            mPM is a solo project by Mark - a developer building tools he actually wants to use. It's the coordination layer behind his indie studios and side projects, shared with anyone who wants it.
                        </p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <a href="https://github.com/bearlikelion/mPM" target="_blank" rel="noopener noreferrer"
                           class="rounded-full border border-neutral-700 bg-neutral-950/40 px-5 py-2.5 text-sm font-medium text-neutral-100 transition hover:border-amber-300 hover:text-amber-300">
                            GitHub
                        </a>
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
                <span class="flex flex-wrap gap-3">
                    <a href="https://github.com/bearlikelion/mPM" target="_blank" rel="noopener noreferrer" class="transition hover:text-amber-300">Source</a>
                    <a href="https://github.com/bearlikelion/mPM/issues" target="_blank" rel="noopener noreferrer" class="transition hover:text-amber-300">Issues</a>
                    <span>Open source &middot; Self-hosted</span>
                </span>
            </footer>
        </main>

        @fluxScripts
    </body>
</html>

<x-filament-panels::page>
    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold">YAML scaffold</h2>
                    <p class="text-sm text-gray-500">Download, edit, paste, or upload one file to create projects, epics, sprints, tasks, tags, assignees, and blockers.</p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <x-filament::button wire:click="downloadTemplate" color="gray">Download template</x-filament::button>
                    <x-filament::button wire:click="exportOrg" color="gray">Export current org</x-filament::button>
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium">Upload YAML</label>
                <input type="file" wire:model="scaffoldUpload" accept=".yml,.yaml,text/yaml,text/plain" class="mt-1 block w-full rounded-lg border border-gray-300 text-sm dark:border-gray-700" />
                @error('scaffoldUpload') <div class="mt-1 text-sm text-danger-600">{{ $message }}</div> @enderror
            </div>

            <div class="mt-4">
                <label class="block text-sm font-medium">Scaffold contents</label>
                <textarea wire:model="yaml" rows="28" class="mt-1 w-full rounded-xl border-gray-300 font-mono text-xs dark:border-gray-700 dark:bg-gray-950"></textarea>
            </div>
        </section>

        <aside class="grid content-start gap-4">
            <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                <h3 class="font-semibold">Import controls</h3>
                <p class="mt-1 text-sm text-gray-500">Preview validates references before writing. Import updates or creates by stable keys.</p>

                <div class="mt-4 grid gap-2">
                    <x-filament::button wire:click="previewImport">Preview import</x-filament::button>
                    <x-filament::button wire:click="applyImport" color="success">Apply import</x-filament::button>
                </div>
            </section>

            @if($preview)
                <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
                    <h3 class="font-semibold">{{ $preview['valid'] ? 'Preview passed' : 'Preview failed' }}</h3>

                    @if($preview['errors'])
                        <ul class="mt-3 list-disc space-y-1 pl-5 text-sm text-danger-600">
                            @foreach($preview['errors'] as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @else
                        <dl class="mt-3 grid grid-cols-2 gap-2 text-sm">
                            @foreach($preview['counts'] as $label => $count)
                                <div class="rounded-lg border border-gray-200 p-2 dark:border-gray-800">
                                    <dt class="text-gray-500">{{ ucfirst($label) }}</dt>
                                    <dd class="font-mono text-lg font-semibold">{{ $count }}</dd>
                                </div>
                            @endforeach
                        </dl>
                    @endif
                </section>
            @endif

            <section class="rounded-xl border border-danger-300 bg-danger-50 p-4 shadow-sm dark:border-danger-800 dark:bg-danger-950/30">
                <h3 class="font-semibold text-danger-700 dark:text-danger-300">Purge org</h3>
                <p class="mt-1 text-sm text-danger-700/80 dark:text-danger-200/80">Clears project data for this organization while preserving members, invites, and settings.</p>

                <x-filament::button
                    wire:click="purgeOrg"
                    wire:confirm="Purge all project, task, sprint, blocker, tag, and planning data for this organization?"
                    color="danger"
                    class="mt-4"
                >
                    Purge org
                </x-filament::button>
            </section>
        </aside>
    </div>
</x-filament-panels::page>

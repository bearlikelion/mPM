<?php

namespace App\Filament\App\Pages;

use App\Models\Organization;
use App\Support\OrgScaffoldService;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrgScaffolding extends Page
{
    use WithFileUploads;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationLabel = 'Org scaffolding';

    protected static string|\UnitEnum|null $navigationGroup = 'Organization';

    protected static ?int $navigationSort = 92;

    protected string $view = 'filament.app.pages.org-scaffolding';

    public string $yaml = '';

    public ?array $preview = null;

    public ?TemporaryUploadedFile $scaffoldUpload = null;

    public static function canAccess(): bool
    {
        $tenant = Filament::getTenant();

        return $tenant instanceof Organization && (auth()->user()?->can('update', $tenant) ?? false);
    }

    public function mount(OrgScaffoldService $scaffoldService): void
    {
        /** @var Organization|null $tenant */
        $tenant = Filament::getTenant();

        abort_unless($tenant instanceof Organization, 404);
        abort_unless(auth()->user()?->can('update', $tenant), 403);

        $this->yaml = $scaffoldService->template($tenant);
    }

    public function updatedScaffoldUpload(): void
    {
        if (! $this->scaffoldUpload) {
            return;
        }

        $this->yaml = (string) file_get_contents($this->scaffoldUpload->getRealPath());
        $this->preview = null;
    }

    public function previewImport(OrgScaffoldService $scaffoldService): void
    {
        $this->preview = $scaffoldService->preview($this->tenant(), $this->yaml);
    }

    public function applyImport(OrgScaffoldService $scaffoldService): void
    {
        $preview = $scaffoldService->preview($this->tenant(), $this->yaml);

        if (! $preview['valid']) {
            $this->preview = $preview;
            Notification::make()->title('Fix scaffold errors before importing')->danger()->send();

            return;
        }

        $scaffoldService->import($this->tenant(), $this->yaml);
        $this->preview = $preview;

        Notification::make()->title('Scaffold imported')->success()->send();
    }

    public function purgeOrg(OrgScaffoldService $scaffoldService): void
    {
        $scaffoldService->purge($this->tenant());
        $this->preview = null;

        Notification::make()->title('Organization project data purged')->success()->send();
    }

    public function downloadTemplate(OrgScaffoldService $scaffoldService): StreamedResponse
    {
        return response()->streamDownload(
            fn () => print $scaffoldService->template($this->tenant()),
            'mpm-scaffold-template.yml',
            ['Content-Type' => 'text/yaml'],
        );
    }

    public function exportOrg(OrgScaffoldService $scaffoldService): StreamedResponse
    {
        return response()->streamDownload(
            fn () => print $scaffoldService->export($this->tenant()),
            Str::of($this->tenant()->slug)->append('-scaffold.yml')->toString(),
            ['Content-Type' => 'text/yaml'],
        );
    }

    private function tenant(): Organization
    {
        /** @var Organization $tenant */
        $tenant = Filament::getTenant();

        return $tenant;
    }
}

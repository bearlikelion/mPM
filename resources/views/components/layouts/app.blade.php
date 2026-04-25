@php
    $siteTenant = app(\App\Support\SiteTenant::class);
    $siteUser = auth()->user();
    $siteOrganizations = $siteTenant->organizations($siteUser);
    $siteCurrentOrganization = $siteTenant->currentOrganization($siteUser);
@endphp

<x-layouts.app.sidebar
    :current-org="$siteCurrentOrganization"
    :organizations="$siteOrganizations"
    :is-site-admin="$siteUser->hasRole('site_admin')"
    :is-org-admin="$siteCurrentOrganization && $siteUser->can('update', $siteCurrentOrganization)"
    :project-count="$siteCurrentOrganization?->projects()->count() ?? 0"
>
    <div class="app-main-inner">
        {{ $slot }}
    </div>
</x-layouts.app.sidebar>
